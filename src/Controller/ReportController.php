<?php

namespace App\Controller;

use App\Repository\ReportRepository;
use App\Service\ReportManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
#[Route('/reports-list')]
class ReportController extends AbstractController
{
    public function __construct(
        private ReportManager $reportManager,
        private ReportRepository $reportRepository
    ) {
    }

    /**
     * Liste tous les rapports
     */
    #[Route('', name: 'app_reports_list')]
    public function list(): Response
    {
        $reports = $this->reportManager->getAllReports();
        $totalReports = $this->reportManager->countReports();
        $latestReport = $this->reportManager->getLatestReport();

        return $this->render('report/list.html.twig', [
            'reports' => $reports,
            'totalReports' => $totalReports,
            'latestReport' => $latestReport,
        ]);
    }

    /**
     * Affiche un rapport spécifique en iframe
     */
    #[Route('/{period}', name: 'app_report_view', requirements: ['period' => '\d{4}-\d{2}'])]
    public function view(string $period): Response
    {
        $report = $this->reportRepository->findByPeriod($period);

        if (!$report) {
            $this->addFlash('error', 'Rapport non trouvé.');
            return $this->redirectToRoute('app_reports_list');
        }

        if (!$this->reportManager->reportFileExists($report)) {
            $this->addFlash('error', 'Le fichier du rapport est introuvable.');
            return $this->redirectToRoute('app_reports_list');
        }

        $reportUrl = $this->reportManager->getReportWebPath($report);

        return $this->render('report/view.html.twig', [
            'report' => $report,
            'reportUrl' => $reportUrl,
        ]);
    }

    /**
     * Télécharger le PDF original du rapport (fichier stocké sur le serveur)
     */
    #[Route('/{period}/download-pdf', name: 'app_report_download_pdf', requirements: ['period' => '\d{4}-\d{2}'])]
    public function downloadPdf(string $period): Response
    {
        $report = $this->reportRepository->findByPeriod($period);

        if (!$report || !$this->reportManager->pdfFileExists($report)) {
            $this->addFlash('error', 'Le fichier PDF de ce rapport n\'est pas disponible.');
            return $this->redirectToRoute('app_reports_list');
        }

        $pdfPath = $this->reportManager->getPdfFilePath($report);
        $filename = 'Rapport-SEO-' . $report->getPeriod() . '.pdf';

        $response = new BinaryFileResponse($pdfPath);
        $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $filename);

        return $response;
    }

    /**
     * Exporter le rapport HTML en PDF via Dompdf (génération à la volée)
     */
    #[Route('/{period}/export-pdf', name: 'app_report_export_pdf', requirements: ['period' => '\d{4}-\d{2}'])]
    public function exportPdf(string $period): Response
    {
        $report = $this->reportRepository->findByPeriod($period);

        if (!$report) {
            $this->addFlash('error', 'Rapport non trouvé.');
            return $this->redirectToRoute('app_reports_list');
        }

        if (!$this->reportManager->reportFileExists($report)) {
            $this->addFlash('error', 'Le fichier du rapport est introuvable.');
            return $this->redirectToRoute('app_reports_list');
        }

        $htmlContent = $this->reportManager->getReportContent($report);
        $htmlContent = $this->cleanHtmlForPdf($htmlContent);

        $options = new \Dompdf\Options();
        $options->set('isRemoteEnabled', true);
        $options->set('isHtml5ParserEnabled', true);
        $options->set('defaultFont', 'sans-serif');

        $dompdf = new \Dompdf\Dompdf($options);
        $dompdf->loadHtml($htmlContent);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $pdfContent = $dompdf->output();
        $filename = 'Rapport-SEO-' . $report->getPeriod() . '-export.pdf';

        return new Response($pdfContent, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    /**
     * Nettoie le HTML d'un rapport pour l'export PDF.
     */
    private function cleanHtmlForPdf(string $html): string
    {
        $dom = new \DOMDocument();

        libxml_use_internal_errors(true);
        $dom->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'), LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();

        $xpath = new \DOMXPath($dom);

        $selectorsToRemove = [
            '//nav',
            '//footer',
            '//script',
            '//*[contains(@class, "scroll-indicator")]',
            '//*[contains(@class, "back-to-top")]',
        ];

        foreach ($selectorsToRemove as $selector) {
            $nodes = $xpath->query($selector);
            if ($nodes) {
                for ($i = $nodes->length - 1; $i >= 0; $i--) {
                    $node = $nodes->item($i);
                    $node->parentNode->removeChild($node);
                }
            }
        }

        $printStyles = $dom->createElement('style');
        $printStyles->textContent = '
            body {
                padding-top: 0 !important;
                margin-top: 0 !important;
                background: white !important;
                overflow: visible !important;
            }
            nav, footer, .scroll-indicator, .back-to-top {
                display: none !important;
            }
            .hero, section:first-of-type {
                margin-top: 0 !important;
                padding-top: 2rem !important;
            }
            section { page-break-inside: avoid; }
            .kpi-card, .card, .table-container, .chart-container { page-break-inside: avoid; }
            * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
            canvas { display: none !important; }
        ';

        $head = $xpath->query('//head')->item(0);
        if ($head) {
            $head->appendChild($printStyles);
        }

        return $dom->saveHTML();
    }

    /**
     * Synchronise les rapports (admin uniquement)
     */
    #[Route('/admin/sync', name: 'app_reports_sync')]
    #[IsGranted('ROLE_ADMIN')]
    public function sync(): Response
    {
        $result = $this->reportManager->scanAndSyncReports();

        if (!empty($result['synced'])) {
            $this->addFlash('success', count($result['synced']) . ' nouveau(x) rapport(s) synchronisé(s).');
        }

        if (!empty($result['updated'])) {
            $this->addFlash('success', count($result['updated']) . ' PDF détecté(s) et associé(s).');
        }

        if (empty($result['synced']) && empty($result['updated'])) {
            $this->addFlash('info', 'Aucun nouveau rapport à synchroniser.');
        }

        if (!empty($result['errors'])) {
            foreach ($result['errors'] as $error) {
                $this->addFlash('error', $error);
            }
        }

        return $this->redirectToRoute('app_reports_list');
    }
}