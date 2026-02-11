<?php

namespace App\Service;

use App\Entity\Report;
use App\Repository\ReportRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Service pour gérer les rapports SEO
 */
class ReportManager
{
    private string $reportsDirectory;

    public function __construct(
        private EntityManagerInterface $entityManager,
        private ReportRepository $reportRepository,
        string $projectDir
    ) {
        // Les rapports sont dans public/reports/
        $this->reportsDirectory = $projectDir . '/public/reports';
    }

    /**
     * Scanne le dossier reports/ et synchronise avec la base de données.
     * Détecte les fichiers HTML (rapports) et les PDF associés.
     *
     * Convention de nommage :
     *   HTML : 2025-11.html
     *   PDF  : 2025-11.pdf (optionnel, même nom que le HTML)
     */
    public function scanAndSyncReports(): array
    {
        $synced = [];
        $updated = [];
        $errors = [];

        // Créer le dossier s'il n'existe pas
        if (!is_dir($this->reportsDirectory)) {
            mkdir($this->reportsDirectory, 0755, true);
        }

        // Scanner les fichiers HTML
        $files = glob($this->reportsDirectory . '/*.html');

        foreach ($files as $file) {
            $filename = basename($file);

            try {
                // Extraire la période depuis le nom de fichier
                if (preg_match('/(\d{4})-(\d{2})\.html$/', $filename, $matches)) {
                    $period = $matches[1] . '-' . $matches[2];

                    // Vérifier si le rapport existe déjà
                    $report = $this->reportRepository->findByPeriod($period);

                    if (!$report) {
                        // Créer un nouveau rapport
                        $report = new Report();
                        $report->setPeriod($period);
                        $report->setFilename($filename);

                        // Extraire les métadonnées du fichier HTML
                        $this->extractMetadata($file, $report);

                        $this->entityManager->persist($report);
                        $synced[] = $filename;
                    }

                    // Détecter le PDF associé (à chaque sync, on met à jour)
                    $pdfFilename = $period . '.pdf';
                    $pdfPath = $this->reportsDirectory . '/' . $pdfFilename;

                    if (file_exists($pdfPath) && $report->getPdfFilename() !== $pdfFilename) {
                        $report->setPdfFilename($pdfFilename);
                        $updated[] = $pdfFilename;
                    } elseif (!file_exists($pdfPath) && $report->getPdfFilename() !== null) {
                        // Le PDF a été supprimé
                        $report->setPdfFilename(null);
                    }
                }
            } catch (\Exception $e) {
                $errors[] = $filename . ': ' . $e->getMessage();
            }
        }

        if (!empty($synced) || !empty($updated)) {
            $this->entityManager->flush();
        }

        return [
            'synced' => $synced,
            'updated' => $updated,
            'errors' => $errors,
            'total' => count($files)
        ];
    }

    /**
     * Extrait les métadonnées d'un fichier HTML
     */
    private function extractMetadata(string $filepath, Report $report): void
    {
        $html = file_get_contents($filepath);
        $crawler = new Crawler($html);

        // Extraire le titre
        try {
            $title = $crawler->filter('title')->text();
            $report->setTitle($title);
        } catch (\Exception $e) {
            $report->setTitle('Rapport SEO ' . $report->getPeriod());
        }

        // Extraire la date depuis la période
        $parts = explode('-', $report->getPeriod());
        if (count($parts) === 2) {
            $year = (int)$parts[0];
            $month = (int)$parts[1];
            $report->setReportDate(new \DateTimeImmutable("$year-$month-01"));
        }

        // Extraire les statistiques clés depuis le HTML
        try {
            $crawler->filter('.hero-meta-item')->each(function (Crawler $node) use ($report) {
                $label = $node->filter('.hero-meta-label')->text();
                $value = $node->filter('.hero-meta-value')->text();

                if (stripos($label, 'Impressions') !== false) {
                    $report->setImpressions((int)str_replace([',', ' '], '', $value));
                }
            });
        } catch (\Exception $e) {
        }

        // Extraire depuis les KPI cards
        try {
            $crawler->filter('.kpi-card')->each(function (Crawler $node) use ($report) {
                try {
                    $label = $node->filter('.kpi-label')->text();
                    $value = $node->filter('.kpi-value')->text();

                    if (stripos($label, 'Total Impressions') !== false) {
                        $report->setImpressions((int)str_replace([',', ' '], '', $value));
                    } elseif (stripos($label, 'Total Clicks') !== false) {
                        $report->setClicks((int)str_replace([',', ' '], '', $value));
                    } elseif (stripos($label, 'Average CTR') !== false) {
                        $report->setCtr((float)str_replace(['%', ','], ['', '.'], $value));
                    } elseif (stripos($label, 'Average Position') !== false) {
                        $report->setPosition((int)str_replace([',', ' '], '', $value));
                    } elseif (stripos($label, 'Organic Traffic') !== false || stripos($label, 'Organic Sessions') !== false) {
                        $report->setOrganicSessions((int)str_replace([',', ' '], '', $value));
                    }
                } catch (\Exception $e) {
                }
            });
        } catch (\Exception $e) {
        }

        $monthName = $report->getMonthName();
        $report->setDescription("Rapport SEO mensuel pour $monthName");
    }

    /**
     * Retourne le chemin web d'un rapport HTML
     */
    public function getReportWebPath(Report $report): string
    {
        return '/reports/' . $report->getFilename();
    }

    /**
     * Vérifie si un fichier de rapport HTML existe
     */
    public function reportFileExists(Report $report): bool
    {
        return file_exists($this->reportsDirectory . '/' . $report->getFilename());
    }

    /**
     * Retourne le contenu HTML d'un rapport
     */
    public function getReportContent(Report $report): string
    {
        $filepath = $this->reportsDirectory . '/' . $report->getFilename();

        if (!file_exists($filepath)) {
            throw new \RuntimeException('Fichier rapport introuvable : ' . $filepath);
        }

        return file_get_contents($filepath);
    }

    /**
     * Vérifie si le fichier PDF original existe sur le disque
     */
    public function pdfFileExists(Report $report): bool
    {
        if (!$report->getPdfFilename()) {
            return false;
        }

        return file_exists($this->reportsDirectory . '/' . $report->getPdfFilename());
    }

    /**
     * Retourne le chemin absolu du fichier PDF original
     */
    public function getPdfFilePath(Report $report): ?string
    {
        if (!$report->getPdfFilename()) {
            return null;
        }

        $path = $this->reportsDirectory . '/' . $report->getPdfFilename();

        return file_exists($path) ? $path : null;
    }

    /**
     * Retourne tous les rapports actifs
     */
    public function getAllReports(): array
    {
        return $this->reportRepository->findAllActive();
    }

    /**
     * Retourne le dernier rapport
     */
    public function getLatestReport(): ?Report
    {
        return $this->reportRepository->findLatest();
    }

    /**
     * Compte le nombre de rapports
     */
    public function countReports(): int
    {
        return $this->reportRepository->countActive();
    }
}