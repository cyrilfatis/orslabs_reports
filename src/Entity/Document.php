<?php

namespace App\Entity;

use App\Repository\DocumentRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DocumentRepository::class)]
#[ORM\Table(name: 'document')]
class Document
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /** Nom original du fichier affiché */
    #[ORM\Column(length: 255)]
    private ?string $originalName = null;

    /** Nom sur le disque (unique, horodaté) */
    #[ORM\Column(length: 255)]
    private ?string $storedName = null;

    #[ORM\Column(length: 20)]
    private ?string $mimeType = null;

    #[ORM\Column]
    private ?int $fileSize = null;

    #[ORM\Column(length: 1000, nullable: true)]
    private ?string $description = null;

    #[ORM\Column]
    private \DateTimeImmutable $uploadedAt;

    #[ORM\ManyToOne(targetEntity: DocumentCategory::class, inversedBy: 'documents')]
    #[ORM\JoinColumn(nullable: false)]
    private ?DocumentCategory $category = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $uploadedBy = null;

    /** Utilisateur ayant effectué la dernière suppression (soft delete) */
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $deletedBy = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $deletedAt = null;

    public function __construct()
    {
        $this->uploadedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }

    public function getOriginalName(): ?string { return $this->originalName; }
    public function setOriginalName(string $originalName): static { $this->originalName = $originalName; return $this; }

    public function getStoredName(): ?string { return $this->storedName; }
    public function setStoredName(string $storedName): static { $this->storedName = $storedName; return $this; }

    public function getMimeType(): ?string { return $this->mimeType; }
    public function setMimeType(string $mimeType): static { $this->mimeType = $mimeType; return $this; }

    public function getFileSize(): ?int { return $this->fileSize; }
    public function setFileSize(int $fileSize): static { $this->fileSize = $fileSize; return $this; }

    public function getDescription(): ?string { return $this->description; }
    public function setDescription(?string $description): static { $this->description = $description; return $this; }

    public function getUploadedAt(): \DateTimeImmutable { return $this->uploadedAt; }

    public function getCategory(): ?DocumentCategory { return $this->category; }
    public function setCategory(?DocumentCategory $category): static { $this->category = $category; return $this; }

    public function getUploadedBy(): ?User { return $this->uploadedBy; }
    public function setUploadedBy(?User $uploadedBy): static { $this->uploadedBy = $uploadedBy; return $this; }

    public function getDeletedBy(): ?User { return $this->deletedBy; }
    public function setDeletedBy(?User $deletedBy): static { $this->deletedBy = $deletedBy; return $this; }

    public function getDeletedAt(): ?\DateTimeImmutable { return $this->deletedAt; }
    public function setDeletedAt(?\DateTimeImmutable $deletedAt): static { $this->deletedAt = $deletedAt; return $this; }

    public function isDeleted(): bool { return $this->deletedAt !== null; }

    public function getFileSizeFormatted(): string
    {
        $size = $this->fileSize ?? 0;
        if ($size < 1024) return $size . ' B';
        if ($size < 1048576) return round($size / 1024, 1) . ' KB';
        return round($size / 1048576, 1) . ' MB';
    }

    public function getExtension(): string
    {
        return strtolower(pathinfo($this->originalName ?? '', PATHINFO_EXTENSION));
    }

    public function getFileIcon(): string
    {
        return match($this->getExtension()) {
            'pdf' => 'fa-file-pdf',
            'doc', 'docx' => 'fa-file-word',
            'xls', 'xlsx' => 'fa-file-excel',
            'ppt', 'pptx' => 'fa-file-powerpoint',
            'jpg', 'jpeg', 'png', 'gif', 'webp' => 'fa-file-image',
            'zip', 'rar', '7z' => 'fa-file-archive',
            'txt', 'md' => 'fa-file-alt',
            'csv' => 'fa-file-csv',
            default => 'fa-file',
        };
    }
}
