<?php

namespace App\Entity;

use App\Repository\DocumentCategoryRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DocumentCategoryRepository::class)]
#[ORM\Table(name: 'document_category')]
class DocumentCategory
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    private ?string $name = null;

    #[ORM\Column(length: 100)]
    private ?string $slug = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $description = null;

    /** Icône FontAwesome ex: "fa-file-invoice" */
    #[ORM\Column(length: 50, nullable: true)]
    private ?string $icon = null;

    /** Couleur hex ex: "#1E3A8A" */
    #[ORM\Column(length: 20, nullable: true)]
    private ?string $color = null;

    #[ORM\Column]
    private int $sortOrder = 0;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    /** @var Collection<int, Document> */
    #[ORM\OneToMany(targetEntity: Document::class, mappedBy: 'category', cascade: ['persist'])]
    #[ORM\OrderBy(['uploadedAt' => 'DESC'])]
    private Collection $documents;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->documents = new ArrayCollection();
    }

    public function getId(): ?int { return $this->id; }

    public function getName(): ?string { return $this->name; }
    public function setName(string $name): static { $this->name = $name; return $this; }

    public function getSlug(): ?string { return $this->slug; }
    public function setSlug(string $slug): static { $this->slug = $slug; return $this; }

    public function getDescription(): ?string { return $this->description; }
    public function setDescription(?string $description): static { $this->description = $description; return $this; }

    public function getIcon(): ?string { return $this->icon; }
    public function setIcon(?string $icon): static { $this->icon = $icon; return $this; }

    public function getColor(): ?string { return $this->color; }
    public function setColor(?string $color): static { $this->color = $color; return $this; }

    public function getSortOrder(): int { return $this->sortOrder; }
    public function setSortOrder(int $sortOrder): static { $this->sortOrder = $sortOrder; return $this; }

    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }

    public function getDocuments(): Collection { return $this->documents; }

    public function getDocumentCount(): int { return $this->documents->count(); }
}
