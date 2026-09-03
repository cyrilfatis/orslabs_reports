<?php

namespace App\Entity;

use App\Repository\MailingFileRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MailingFileRepository::class)]
#[ORM\Table(name: 'mailing_file')]
class MailingFile
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $title = null;

    /** Nom du fichier html sur le disque (unique, horodaté) */
    #[ORM\Column(length: 255)]
    private ?string $storedName = null;

    /** Date d'envoi de la campagne — null tant qu'elle n'a pas été envoyée */
    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $sentAt = null;

    #[ORM\Column(length: 500, nullable: true)]
    private ?string $trackingUrl = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $createdBy = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }

    public function getTitle(): ?string { return $this->title; }
    public function setTitle(string $title): static { $this->title = $title; return $this; }

    public function getStoredName(): ?string { return $this->storedName; }
    public function setStoredName(string $storedName): static { $this->storedName = $storedName; return $this; }

    public function getSentAt(): ?\DateTimeImmutable { return $this->sentAt; }
    public function setSentAt(?\DateTimeImmutable $sentAt): static { $this->sentAt = $sentAt; return $this; }

    public function getTrackingUrl(): ?string { return $this->trackingUrl; }
    public function setTrackingUrl(?string $trackingUrl): static { $this->trackingUrl = $trackingUrl; return $this; }

    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }

    public function getCreatedBy(): ?User { return $this->createdBy; }
    public function setCreatedBy(?User $createdBy): static { $this->createdBy = $createdBy; return $this; }
}
