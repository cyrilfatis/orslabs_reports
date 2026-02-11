<?php

namespace App\Controller;

use App\Entity\PerformanceMetric;
use App\Repository\PerformanceMetricRepository;
use App\Service\ReportManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
class DashboardController extends AbstractController
{
    public function __construct(
        private ReportManager $reportManager,
        private PerformanceMetricRepository $metricRepository,
    ) {
    }

    #[Route('/', name: 'app_dashboard')]
    public function index(): Response
    {
        $user = $this->getUser();

        // Rapports
        $reports = $this->reportManager->getAllReports();
        $totalReports = $this->reportManager->countReports();
        $latestReport = $this->reportManager->getLatestReport();

        // Période couverte
        $periodCovered = 'N/A';
        if (!empty($reports)) {
            $oldestReport = end($reports);
            $newestReport = reset($reports);
            if ($oldestReport && $newestReport) {
                $oldestDate = $oldestReport->getReportDate();
                $newestDate = $newestReport->getReportDate();
                if ($oldestDate && $newestDate) {
                    $diff = $oldestDate->diff($newestDate);
                    $months = ($diff->y * 12) + $diff->m + 1;
                    $periodCovered = $months . ' mois';
                }
            }
        }

        // Métriques SEO (les 2 dernières pour calculer les tendances)
        $latestSeo = $this->metricRepository->findLatestBySource(PerformanceMetric::SOURCE_SEO);
        $seoHistory = $this->metricRepository->findLastNBySource(PerformanceMetric::SOURCE_SEO, 2);
        $previousSeo = count($seoHistory) >= 2 ? $seoHistory[1] : null;

        // Métriques LinkedIn
        $latestLinkedin = $this->metricRepository->findLatestBySource(PerformanceMetric::SOURCE_LINKEDIN);
        $linkedinHistory = $this->metricRepository->findLastNBySource(PerformanceMetric::SOURCE_LINKEDIN, 2);
        $previousLinkedin = count($linkedinHistory) >= 2 ? $linkedinHistory[1] : null;

        // Historique pour les graphiques (6 derniers mois)
        $seoChartData = $this->metricRepository->findSeoHistory(6);
        $linkedinChartData = $this->metricRepository->findLinkedinHistory(6);

        // Calculer les tendances SEO
        $seoTrends = $this->calculateTrends($latestSeo, $previousSeo, 'seo');
        $linkedinTrends = $this->calculateTrends($latestLinkedin, $previousLinkedin, 'linkedin');

        return $this->render('dashboard/index.html.twig', [
            'user' => $user,
            'reports' => $reports,
            'totalReports' => $totalReports,
            'latestReport' => $latestReport,
            'periodCovered' => $periodCovered,
            // Métriques
            'latestSeo' => $latestSeo,
            'previousSeo' => $previousSeo,
            'latestLinkedin' => $latestLinkedin,
            'previousLinkedin' => $previousLinkedin,
            'seoTrends' => $seoTrends,
            'linkedinTrends' => $linkedinTrends,
            // Graphiques
            'seoChartData' => $this->formatChartData($seoChartData, 'seo'),
            'linkedinChartData' => $this->formatChartData($linkedinChartData, 'linkedin'),
        ]);
    }

    /**
     * Calcule les tendances entre deux périodes
     */
    private function calculateTrends(?PerformanceMetric $current, ?PerformanceMetric $previous, string $type): array
    {
        $trends = [];

        if (!$current || !$previous) {
            return $trends;
        }

        if ($type === 'seo') {
            $trends['impressions'] = $this->trendPercent($current->getImpressions(), $previous->getImpressions());
            $trends['clicks'] = $this->trendPercent($current->getClicks(), $previous->getClicks());
            $trends['ctr'] = $this->trendDiff($current->getCtr(), $previous->getCtr());
            $trends['position'] = $this->trendPosition($current->getAvgPosition(), $previous->getAvgPosition());
            $trends['organicSessions'] = $this->trendPercent($current->getOrganicSessions(), $previous->getOrganicSessions());
            $trends['totalUsers'] = $this->trendPercent($current->getTotalUsers(), $previous->getTotalUsers());
            $trends['bounceRate'] = $this->trendBounce($current->getBounceRate(), $previous->getBounceRate());
            $trends['engagementRate'] = $this->trendDiff($current->getEngagementRate(), $previous->getEngagementRate());
        }

        if ($type === 'linkedin') {
            $trends['followers'] = $this->trendPercent($current->getLinkedinFollowers(), $previous->getLinkedinFollowers());
            $trends['impressions'] = $this->trendPercent($current->getLinkedinImpressions(), $previous->getLinkedinImpressions());
            $trends['reactions'] = $this->trendPercent($current->getLinkedinReactions(), $previous->getLinkedinReactions());
            $trends['engagementRate'] = $this->trendDiff($current->getLinkedinEngagementRate(), $previous->getLinkedinEngagementRate());
        }

        return $trends;
    }

    private function trendPercent(?int $current, ?int $previous): array
    {
        if ($previous === null || $previous === 0 || $current === null) {
            return ['value' => null, 'direction' => 'neutral'];
        }
        $diff = round((($current - $previous) / $previous) * 100, 1);
        return [
            'value' => ($diff >= 0 ? '+' : '') . $diff . '%',
            'direction' => $diff > 0 ? 'up' : ($diff < 0 ? 'down' : 'neutral'),
        ];
    }

    private function trendDiff(?float $current, ?float $previous): array
    {
        if ($previous === null || $current === null) {
            return ['value' => null, 'direction' => 'neutral'];
        }
        $diff = round($current - $previous, 2);
        return [
            'value' => ($diff >= 0 ? '+' : '') . $diff . ' pts',
            'direction' => $diff > 0 ? 'up' : ($diff < 0 ? 'down' : 'neutral'),
        ];
    }

    /**
     * Pour la position, plus bas = mieux
     */
    private function trendPosition(?float $current, ?float $previous): array
    {
        if ($previous === null || $current === null) {
            return ['value' => null, 'direction' => 'neutral'];
        }
        $diff = round($current - $previous, 1);
        return [
            'value' => ($diff >= 0 ? '+' : '') . $diff,
            'direction' => $diff < 0 ? 'up' : ($diff > 0 ? 'down' : 'neutral'), // inverted: lower = better
        ];
    }

    /**
     * Pour le bounce rate, plus bas = mieux
     */
    private function trendBounce(?float $current, ?float $previous): array
    {
        if ($previous === null || $current === null) {
            return ['value' => null, 'direction' => 'neutral'];
        }
        $diff = round($current - $previous, 2);
        return [
            'value' => ($diff >= 0 ? '+' : '') . $diff . ' pts',
            'direction' => $diff < 0 ? 'up' : ($diff > 0 ? 'down' : 'neutral'), // inverted
        ];
    }

    /**
     * Formate les données pour Chart.js
     */
    private function formatChartData(array $metrics, string $type): array
    {
        $labels = [];
        $datasets = [];

        foreach ($metrics as $m) {
            $labels[] = $m->getMonthName();
        }

        if ($type === 'seo') {
            $datasets = [
                'impressions' => array_map(fn($m) => $m->getImpressions(), $metrics),
                'clicks' => array_map(fn($m) => $m->getClicks(), $metrics),
                'organicSessions' => array_map(fn($m) => $m->getOrganicSessions(), $metrics),
                'totalUsers' => array_map(fn($m) => $m->getTotalUsers(), $metrics),
                'ctr' => array_map(fn($m) => $m->getCtr(), $metrics),
                'avgPosition' => array_map(fn($m) => $m->getAvgPosition(), $metrics),
            ];
        }

        if ($type === 'linkedin') {
            $datasets = [
                'followers' => array_map(fn($m) => $m->getLinkedinFollowers(), $metrics),
                'impressions' => array_map(fn($m) => $m->getLinkedinImpressions(), $metrics),
                'reactions' => array_map(fn($m) => $m->getLinkedinReactions(), $metrics),
                'engagementRate' => array_map(fn($m) => $m->getLinkedinEngagementRate(), $metrics),
            ];
        }

        return ['labels' => $labels, 'datasets' => $datasets];
    }
}
