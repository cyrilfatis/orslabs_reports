<?php

namespace App\Command;

use App\Entity\PerformanceMetric;
use App\Repository\PerformanceMetricRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:add-linkedin-metrics',
    description: 'Ajoute ou met à jour les métriques LinkedIn pour un mois donné (saisie interactive)',
)]
class AddLinkedinMetricsCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private PerformanceMetricRepository $metricRepository,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('period', InputArgument::REQUIRED, 'Période au format YYYY-MM (ex: 2025-12)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $period = $input->getArgument('period');

        if (!preg_match('/^\d{4}-\d{2}$/', $period)) {
            $io->error('Format de période invalide. Utilisez YYYY-MM (ex: 2025-12)');
            return Command::FAILURE;
        }

        $io->title("Métriques LinkedIn — $period");

        // Chercher ou créer
        $metric = $this->metricRepository->findByPeriodAndSource($period, PerformanceMetric::SOURCE_LINKEDIN);
        $isNew = false;

        if (!$metric) {
            $metric = new PerformanceMetric();
            $metric->setPeriod($period);
            $metric->setSource(PerformanceMetric::SOURCE_LINKEDIN);
            $isNew = true;
            $io->note('Création d\'une nouvelle entrée.');
        } else {
            $io->note('Mise à jour de l\'entrée existante.');
        }

        // Saisie interactive (appuyez sur Entrée pour garder la valeur actuelle)
        $metric->setLinkedinFollowers(
            $this->askInt($io, 'Nombre d\'abonnés', $metric->getLinkedinFollowers())
        );
        $metric->setLinkedinImpressions(
            $this->askInt($io, 'Impressions', $metric->getLinkedinImpressions())
        );
        $metric->setLinkedinReactions(
            $this->askInt($io, 'Réactions (likes, etc.)', $metric->getLinkedinReactions())
        );
        $metric->setLinkedinComments(
            $this->askInt($io, 'Commentaires', $metric->getLinkedinComments())
        );
        $metric->setLinkedinShares(
            $this->askInt($io, 'Partages', $metric->getLinkedinShares())
        );
        $metric->setLinkedinProfileViews(
            $this->askInt($io, 'Vues de profil', $metric->getLinkedinProfileViews())
        );
        $metric->setLinkedinPostsPublished(
            $this->askInt($io, 'Posts publiés ce mois', $metric->getLinkedinPostsPublished())
        );
        $metric->setLinkedinEngagementRate(
            $this->askFloat($io, 'Taux d\'engagement (%)', $metric->getLinkedinEngagementRate())
        );
        $metric->setNotes(
            $io->ask('Notes / commentaires', $metric->getNotes())
        );

        $metric->setUpdatedAt(new \DateTimeImmutable());

        $this->entityManager->persist($metric);
        $this->entityManager->flush();

        $io->success("Métriques LinkedIn pour $period enregistrées !");

        return Command::SUCCESS;
    }

    private function askInt(SymfonyStyle $io, string $label, ?int $current): ?int
    {
        $default = $current !== null ? (string) $current : '';
        $answer = $io->ask($label, $default);
        return $answer !== null && $answer !== '' ? (int) $answer : null;
    }

    private function askFloat(SymfonyStyle $io, string $label, ?float $current): ?float
    {
        $default = $current !== null ? (string) $current : '';
        $answer = $io->ask($label, $default);
        return $answer !== null && $answer !== '' ? (float) $answer : null;
    }
}
