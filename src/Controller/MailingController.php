<?php

namespace App\Controller;

use App\Entity\MailingFile;
use App\Repository\MailingFileRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;

#[IsGranted('ROLE_ADMIN')]
#[Route('/admin/mailing')]
class MailingController extends AbstractController
{
    private string $storageDir;

    public function __construct(
        private EntityManagerInterface $em,
        private MailingFileRepository $mailingRepo,
        private SluggerInterface $slugger,
        string $projectDir
    ) {
        // Stocké hors de public/ : ces fichiers html ne doivent pas être servis
        // directement en tant qu'assets statiques (risque XSS / vol de session).
        $this->storageDir = $projectDir . '/var/mailing';
    }

    #[Route('', name: 'app_mailing_index')]
    public function index(): Response
    {
        return $this->render('mailing/index.html.twig', [
            'mailings' => $this->mailingRepo->findAllOrdered(),
        ]);
    }

    #[Route('/upload', name: 'app_mailing_upload', methods: ['POST'])]
    public function upload(Request $request): JsonResponse
    {
        $file = $request->files->get('file');
        $title = trim($request->request->get('title', ''));
        $sentAtRaw = $request->request->get('sent_at', '');
        $trackingUrl = trim($request->request->get('tracking_url', ''));

        if (!$file) {
            return new JsonResponse(['error' => 'Aucun fichier reçu.'], 400);
        }

        if (empty($title)) {
            return new JsonResponse(['error' => 'Le titre est obligatoire.'], 400);
        }

        $extension = strtolower($file->getClientOriginalExtension());
        if (!in_array($extension, ['html', 'htm'], true)) {
            return new JsonResponse(['error' => 'Seuls les fichiers .html sont acceptés.'], 400);
        }

        if ($file->getSize() > 5 * 1024 * 1024) {
            return new JsonResponse(['error' => 'Fichier trop volumineux (max 5 Mo).'], 400);
        }

        $sentAt = null;
        if ($sentAtRaw !== '') {
            try {
                $sentAt = new \DateTimeImmutable($sentAtRaw);
            } catch (\Exception) {
                return new JsonResponse(['error' => 'Date d\'envoi invalide.'], 400);
            }
        }

        if (!is_dir($this->storageDir)) {
            mkdir($this->storageDir, 0755, true);
        }

        $safeBasename = $this->slugger->slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME));
        $storedName = $safeBasename . '-' . uniqid() . '.html';
        $file->move($this->storageDir, $storedName);

        $mailing = new MailingFile();
        $mailing->setTitle($title);
        $mailing->setStoredName($storedName);
        $mailing->setSentAt($sentAt);
        $mailing->setTrackingUrl($trackingUrl ?: null);
        $mailing->setCreatedBy($this->getUser());

        $this->em->persist($mailing);
        $this->em->flush();

        return new JsonResponse(['success' => true, 'mailing' => $this->serializeMailing($mailing)]);
    }

    #[Route('/{id}/edit', name: 'app_mailing_edit', methods: ['POST'])]
    public function edit(MailingFile $mailing, Request $request): JsonResponse
    {
        $title = trim($request->request->get('title', ''));
        $sentAtRaw = trim($request->request->get('sent_at', ''));
        $trackingUrl = trim($request->request->get('tracking_url', ''));

        if (empty($title)) {
            return new JsonResponse(['error' => 'Le titre est obligatoire.'], 400);
        }

        $sentAt = null;
        if ($sentAtRaw !== '') {
            try {
                $sentAt = new \DateTimeImmutable($sentAtRaw);
            } catch (\Exception) {
                return new JsonResponse(['error' => 'Date d\'envoi invalide.'], 400);
            }
        }

        $mailing->setTitle($title);
        $mailing->setSentAt($sentAt);
        $mailing->setTrackingUrl($trackingUrl ?: null);

        $this->em->flush();

        return new JsonResponse(['success' => true, 'mailing' => $this->serializeMailing($mailing)]);
    }

    private function serializeMailing(MailingFile $mailing): array
    {
        return [
            'id' => $mailing->getId(),
            'title' => $mailing->getTitle(),
            'sentAt' => $mailing->getSentAt()?->format('d/m/Y'),
            'sentAtIso' => $mailing->getSentAt()?->format('Y-m-d'),
            'trackingUrl' => $mailing->getTrackingUrl(),
            'createdBy' => $mailing->getCreatedBy()->getFullName(),
            'contentUrl' => $this->generateUrl('app_mailing_content', ['id' => $mailing->getId()]),
            'editUrl' => $this->generateUrl('app_mailing_edit', ['id' => $mailing->getId()]),
            'deleteUrl' => $this->generateUrl('app_mailing_delete', ['id' => $mailing->getId()]),
        ];
    }

    #[Route('/{id}/content', name: 'app_mailing_content')]
    public function content(MailingFile $mailing): Response
    {
        $filePath = $this->storageDir . '/' . $mailing->getStoredName();

        if (!file_exists($filePath)) {
            throw $this->createNotFoundException('Fichier introuvable sur le disque.');
        }

        return new Response(
            file_get_contents($filePath),
            200,
            ['Content-Type' => 'text/html; charset=UTF-8', 'X-Content-Type-Options' => 'nosniff']
        );
    }

    #[Route('/{id}/delete', name: 'app_mailing_delete', methods: ['DELETE', 'POST'])]
    public function delete(MailingFile $mailing): JsonResponse
    {
        $filePath = $this->storageDir . '/' . $mailing->getStoredName();
        if (file_exists($filePath)) {
            unlink($filePath);
        }

        $this->em->remove($mailing);
        $this->em->flush();

        return new JsonResponse(['success' => true]);
    }
}
