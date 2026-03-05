<?php

namespace App\Controller;

use App\Entity\Document;
use App\Entity\DocumentCategory;
use App\Repository\DocumentCategoryRepository;
use App\Repository\DocumentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;

#[IsGranted('ROLE_ADMIN')]
#[Route('/admin/documents')]
class DocumentController extends AbstractController
{
    private string $uploadDir;

    public function __construct(
        private EntityManagerInterface $em,
        private DocumentCategoryRepository $categoryRepo,
        private DocumentRepository $documentRepo,
        private SluggerInterface $slugger,
        string $projectDir
    ) {
        $this->uploadDir = $projectDir . '/public/uploads/documents';
    }

    // ─── PAGE PRINCIPALE ────────────────────────────────────────────────────

    #[Route('', name: 'app_documents_index')]
    public function index(): Response
    {
        $categories = $this->categoryRepo->findAllOrdered();

        // Initialise les catégories par défaut si aucune n'existe
        if (empty($categories)) {
            $this->initDefaultCategories();
            $categories = $this->categoryRepo->findAllOrdered();
        }

        $documentsByCategory = [];
        foreach ($categories as $cat) {
            $documentsByCategory[$cat->getId()] = $this->documentRepo->findByCategory($cat);
        }

        return $this->render('documents/index.html.twig', [
            'categories' => $categories,
            'documentsByCategory' => $documentsByCategory,
        ]);
    }

    // ─── UPLOAD (AJAX) ──────────────────────────────────────────────────────

    #[Route('/upload', name: 'app_documents_upload', methods: ['POST'])]
    public function upload(Request $request): JsonResponse
    {
        try {
            $file = $request->files->get('file');
            $categoryId = $request->request->get('category_id');
            $description = $request->request->get('description', '');

            if (!$file) {
                return new JsonResponse(['error' => 'Aucun fichier reçu.'], 400);
            }

            $category = $this->categoryRepo->find($categoryId);
            if (!$category) {
                return new JsonResponse(['error' => 'Catégorie introuvable (id: ' . $categoryId . ').'], 400);
            }

            // Taille récupérée AVANT move() car après le fichier tmp n'existe plus
            $fileSize = $file->getSize();

            // Sécurité: limite à 20 Mo
            if ($fileSize > 20 * 1024 * 1024) {
                return new JsonResponse(['error' => 'Fichier trop volumineux (max 20 Mo).'], 400);
            }

            $originalName  = $file->getClientOriginalName();
            $mimeType      = $file->getMimeType() ?? $file->getClientMimeType() ?? 'application/octet-stream';
            $safeBasename  = $this->slugger->slug(pathinfo($originalName, PATHINFO_FILENAME));
            $extension     = strtolower($file->getClientOriginalExtension());
            $storedName    = $safeBasename . '-' . uniqid() . '.' . $extension;

            // Créer le répertoire si besoin
            $categoryDir = $this->uploadDir . '/' . $category->getSlug();
            if (!is_dir($categoryDir)) {
                mkdir($categoryDir, 0755, true);
            }

            // Déplacer le fichier (après ce point $file->getSize() retourne 0)
            $file->move($categoryDir, $storedName);

            // Taille réelle depuis le disque si getSize() avait retourné 0
            if (!$fileSize) {
                $fileSize = filesize($categoryDir . '/' . $storedName) ?: 0;
            }

            // Persister en DB
            $document = new Document();
            $document->setOriginalName($originalName);
            $document->setStoredName($storedName);
            $document->setMimeType($mimeType);
            $document->setFileSize($fileSize);
            $document->setDescription($description ?: null);
            $document->setCategory($category);
            $document->setUploadedBy($this->getUser());

            $this->em->persist($document);
            $this->em->flush();

            return new JsonResponse([
                'success' => true,
                'document' => [
                    'id'           => $document->getId(),
                    'name'         => $document->getOriginalName(),
                    'size'         => $document->getFileSizeFormatted(),
                    'icon'         => $document->getFileIcon(),
                    'extension'    => $document->getExtension(),
                    'description'  => $document->getDescription(),
                    'uploadedBy'   => $document->getUploadedBy()->getFullName(),
                    'uploadedAt'   => $document->getUploadedAt()->format('d/m/Y H:i'),
                    'downloadUrl'  => $this->generateUrl('app_documents_download', ['id' => $document->getId()]),
                    'deleteUrl'    => $this->generateUrl('app_documents_delete', ['id' => $document->getId()]),
                ],
            ]);

        } catch (\Throwable $e) {
            // Toujours retourner du JSON même en cas d'exception
            return new JsonResponse([
                'error' => 'Erreur serveur : ' . $e->getMessage(),
                'file'  => $e->getFile() . ':' . $e->getLine(),
            ], 500);
        }
    }

    // ─── TÉLÉCHARGEMENT ─────────────────────────────────────────────────────

    #[Route('/download/{id}', name: 'app_documents_download')]
    public function download(Document $document): BinaryFileResponse
    {
        $filePath = $this->uploadDir . '/' . $document->getCategory()->getSlug() . '/' . $document->getStoredName();

        if (!file_exists($filePath)) {
            throw $this->createNotFoundException('Fichier introuvable sur le disque.');
        }

        return $this->file($filePath, $document->getOriginalName(), ResponseHeaderBag::DISPOSITION_ATTACHMENT);
    }

    // ─── SUPPRESSION (AJAX) ─────────────────────────────────────────────────

    #[Route('/delete/{id}', name: 'app_documents_delete', methods: ['DELETE', 'POST'])]
    public function delete(Document $document, Request $request): JsonResponse
    {
        // Soft delete : on conserve la trace
        $document->setDeletedAt(new \DateTimeImmutable());
        $document->setDeletedBy($this->getUser());

        // Suppression physique du fichier
        $filePath = $this->uploadDir . '/' . $document->getCategory()->getSlug() . '/' . $document->getStoredName();
        if (file_exists($filePath)) {
            unlink($filePath);
        }

        $this->em->flush();

        return new JsonResponse(['success' => true]);
    }

    // ─── GESTION DES CATÉGORIES ─────────────────────────────────────────────

    #[Route('/categories/create', name: 'app_documents_category_create', methods: ['POST'])]
    public function createCategory(Request $request): JsonResponse
    {
        $name = trim($request->request->get('name', ''));
        $description = trim($request->request->get('description', ''));
        $icon = trim($request->request->get('icon', 'fa-folder'));
        $color = trim($request->request->get('color', '#1E3A8A'));

        if (empty($name)) {
            return new JsonResponse(['error' => 'Le nom est obligatoire.'], 400);
        }

        $slug = strtolower($this->slugger->slug($name));

        // Vérifier unicité du slug
        if ($this->categoryRepo->findBySlug($slug)) {
            return new JsonResponse(['error' => 'Une catégorie avec ce nom existe déjà.'], 400);
        }

        $category = new DocumentCategory();
        $category->setName($name);
        $category->setSlug($slug);
        $category->setDescription($description ?: null);
        $category->setIcon($icon);
        $category->setColor($color);

        $this->em->persist($category);
        $this->em->flush();

        return new JsonResponse([
            'success' => true,
            'category' => [
                'id'          => $category->getId(),
                'name'        => $category->getName(),
                'slug'        => $category->getSlug(),
                'description' => $category->getDescription(),
                'icon'        => $category->getIcon(),
                'color'       => $category->getColor(),
            ],
        ]);
    }

    #[Route('/categories/{id}/delete', name: 'app_documents_category_delete', methods: ['DELETE', 'POST'])]
    public function deleteCategory(DocumentCategory $category): JsonResponse
    {
        if ($category->getDocumentCount() > 0) {
            return new JsonResponse(['error' => 'Impossible de supprimer une catégorie contenant des fichiers.'], 400);
        }

        $this->em->remove($category);
        $this->em->flush();

        return new JsonResponse(['success' => true]);
    }

    // ─── INITIALISATION DES CATÉGORIES PAR DÉFAUT ───────────────────────────

    private function initDefaultCategories(): void
    {
        $defaults = [
            ['Devis',           'devis',           'fa-file-invoice',     '#1E3A8A', 'Devis commerciaux', 0],
            ['Factures',        'factures',        'fa-receipt',          '#059669', 'Factures et avoir', 1],
            ['Rapports SEO',    'rapports-seo',    'fa-chart-line',       '#7C3AED', 'Rapports de monitoring SEO', 2],
            ['Améliorations SEO','ameliorations-seo','fa-rocket',         '#F59E0B', 'Plans et recommandations SEO', 3],
            ['Contrats',        'contrats',        'fa-file-contract',    '#DC2626', 'Contrats et documents légaux', 4],
            ['Divers',          'divers',          'fa-folder-open',      '#64748B', 'Autres documents', 5],
        ];

        foreach ($defaults as [$name, $slug, $icon, $color, $desc, $order]) {
            $cat = new DocumentCategory();
            $cat->setName($name)
                ->setSlug($slug)
                ->setIcon($icon)
                ->setColor($color)
                ->setDescription($desc)
                ->setSortOrder($order);
            $this->em->persist($cat);
        }

        $this->em->flush();
    }
}