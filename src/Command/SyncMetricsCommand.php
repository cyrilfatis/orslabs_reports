<?php

namespace App\Command;

use App\Entity\PerformanceMetric;
use App\Repository\PerformanceMetricRepository;
use App\Repository\ReportRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DomCrawler\Crawler;

#[AsCommand(
    name: 'app:sync-metrics',
    description: 'Synchronise les métriques de performance depuis les rapports HTML existants',
)]
class SyncMetricsCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ReportRepository $reportRepository,
        private PerformanceMetricRepository $metricRepository,
        private string $projectDir,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Écraser les métriques existantes')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $force = $input->getOption('force');
        $reportsDir = $this->projectDir . '/public/reports';

        $io->title('Synchronisation des métriques de performance');

        $files = glob($reportsDir . '/*.html');

        if (empty($files)) {
            $io->warning('Aucun fichier de rapport trouvé dans ' . $reportsDir);
            return Command::SUCCESS;
        }

        $synced = 0;
        $skipped = 0;

        foreach ($files as $file) {
            $filename = basename($file);

            if (!preg_match('/(\d{4})-(\d{2})\.html$/', $filename, $matches)) {
                continue;
            }

            $period = $matches[1] . '-' . $matches[2];

            // Vérifier si une métrique SEO existe déjà
            $existing = $this->metricRepository->findByPeriodAndSource($period, PerformanceMetric::SOURCE_SEO);
            if ($existing && !$force) {
                $io->text("  ⏭ Métriques SEO pour $period déjà existantes (--force pour écraser)");
                $skipped++;
                continue;
            }

            $metric = $existing ?? new PerformanceMetric();
            $metric->setPeriod($period);
            $metric->setSource(PerformanceMetric::SOURCE_SEO);

            // Parser le HTML
            try {
                $this->extractMetricsFromHtml($file, $metric);
                $this->entityManager->persist($metric);
                $io->text("  ✓ Métriques extraites pour $period");
                $synced++;
            } catch (\Exception $e) {
                $io->error("  ✗ Erreur pour $period: " . $e->getMessage());
            }
        }

        $this->entityManager->flush();

        $io->success("Synchronisation terminée : $synced métriques créées/mises à jour, $skipped ignorées.");

        return Command::SUCCESS;
    }

    private function extractMetricsFromHtml(string $filepath, PerformanceMetric $metric): void
    {
        $html = file_get_contents($filepath);
        $crawler = new Crawler($html);

        // Extraire les KPI depuis les cartes
        $crawler->filter('.kpi-card')->each(function (Crawler $node) use ($metric) {
            try {
                $label = trim($node->filter('.kpi-label')->text());
                $valueText = trim($node->filter('.kpi-value')->text());
                $value = $this->parseNumericValue($valueText);

                match (true) {
                    str_contains(strtolower($label), 'total impressions') => $metric->setImpressions((int) $value),
                    str_contains(strtolower($label), 'total clicks') => $metric->setClicks((int) $value),
                    str_contains(strtolower($label), 'average ctr') => $metric->setCtr($value),
                    str_contains(strtolower($label), 'average position') => $metric->setAvgPosition($value),
                    str_contains(strtolower($label), 'organic traffic') => $metric->setOrganicSessions((int) $value),
                    str_contains(strtolower($label), 'direct traffic') => $metric->setDirectTraffic((int) $value),
                    str_contains(strtolower($label), 'total unique users') => $metric->setTotalUsers((int) $value),
                    str_contains(strtolower($label), 'bounce rate') => $metric->setBounceRate($value),
                    str_contains(strtolower($label), 'engagement rate') => $metric->setEngagementRate($value),
                    str_contains(strtolower($label), 'organic social') => $metric->setOrganicSocialSessions((int) $value),
                    str_contains(strtolower($label), 'avg session') => null, // Traité séparément
                    default => null,
                };
            } catch (\Exception $e) {
                // Ignorer les erreurs de parsing individuelles
            }
        });

        // Extraire la durée de session
        $crawler->filter('.kpi-card')->each(function (Crawler $node) use ($metric) {
            try {
                $label = trim($node->filter('.kpi-label')->text());
                if (str_contains(strtolower($label), 'session duration')) {
                    $valueText = trim($node->filter('.kpi-value')->text());
                    if (preg_match('/(\d+):(\d+)/', $valueText, $m)) {
                        $metric->setAvgSessionDurationSeconds(((int) $m[1] * 60) + (int) $m[2]);
                    }
                }
            } catch (\Exception $e) {
            }
        });

        $metric->setUpdatedAt(new \DateTimeImmutable());
    }

    private function parseNumericValue(string $text): float
    {
        // Retirer %, espaces, virgules
        $cleaned = str_replace(['%', ',', ' ', "\xc2\xa0"], ['', '', '', ''], trim($text));
        return (float) $cleaned;
    }
}
