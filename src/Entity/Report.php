<?php

namespace App\Entity;

use App\Repository\ReportRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ReportRepository::class)]
#[ORM\Table(name: 'report')]
class Report
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 7, unique: true)]
    private ?string $period = null; // Format: 2025-11

    #[ORM\Column(length: 255)]
    private ?string $filename = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $pdfFilename = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(nullable: true)]
    private ?int $impressions = null;

    #[ORM\Column(nullable: true)]
    private ?int $clicks = null;

    #[ORM\Column(nullable: true)]
    private ?float $ctr = null;

    #[ORM\Column(nullable: true)]
    private ?int $position = null;

    #[ORM\Column(nullable: true)]
    private ?int $organicSessions = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $reportDate = null;

    #[ORM\Column]
    private bool $isActive = true;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPeriod(): ?string
    {
        return $this->period;
    }

    public function setPeriod(string $period): static
    {
        $this->period = $period;
        return $this;
    }

    public function getFilename(): ?string
    {
        return $this->filename;
    }

    public function setFilename(string $filename): static
    {
        $this->filename = $filename;
        return $this;
    }

    public function getPdfFilename(): ?string
    {
        return $this->pdfFilename;
    }

    public function setPdfFilename(?string $pdfFilename): static
    {
        $this->pdfFilename = $pdfFilename;
        return $this;
    }

    /**
     * Indique si un PDF original est disponible pour ce rapport
     */
    public function hasPdf(): bool
    {
        return $this->pdfFilename !== null;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): static
    {
        $this->title = $title;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;
        return $this;
    }

    public function getImpressions(): ?int
    {
        return $this->impressions;
    }

    public function setImpressions(?int $impressions): static
    {
        $this->impressions = $impressions;
        return $this;
    }

    public function getClicks(): ?int
    {
        return $this->clicks;
    }

    public function setClicks(?int $clicks): static
    {
        $this->clicks = $clicks;
        return $this;
    }

    public function getCtr(): ?float
    {
        return $this->ctr;
    }

    public function setCtr(?float $ctr): static
    {
        $this->ctr = $ctr;
        return $this;
    }

    public function getPosition(): ?int
    {
        return $this->position;
    }

    public function setPosition(?int $position): static
    {
        $this->position = $position;
        return $this;
    }

    public function getOrganicSessions(): ?int
    {
        return $this->organicSessions;
    }

    public function setOrganicSessions(?int $organicSessions): static
    {
        $this->organicSessions = $organicSessions;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getReportDate(): ?\DateTimeImmutable
    {
        return $this->reportDate;
    }

    public function setReportDate(?\DateTimeImmutable $reportDate): static
    {
        $this->reportDate = $reportDate;
        return $this;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): static
    {
        $this->isActive = $isActive;
        return $this;
    }

    /**
     * Retourne le nom du mois en français
     */
    public function getMonthName(): string
    {
        if (!$this->reportDate) {
            return '';
        }

        $months = [
            1 => 'Janvier', 2 => 'Février', 3 => 'Mars', 4 => 'Avril',
            5 => 'Mai', 6 => 'Juin', 7 => 'Juillet', 8 => 'Août',
            9 => 'Septembre', 10 => 'Octobre', 11 => 'Novembre', 12 => 'Décembre'
        ];

        return $months[(int)$this->reportDate->format('n')] . ' ' . $this->reportDate->format('Y');
    }

    /**
     * Retourne un résumé formaté
     */
    public function getSummary(): string
    {
        $parts = [];

        if ($this->impressions) {
            $parts[] = number_format($this->impressions, 0, ',', ' ') . ' impressions';
        }

        if ($this->organicSessions) {
            $parts[] = number_format($this->organicSessions, 0, ',', ' ') . ' sessions';
        }

        return !empty($parts) ? implode(' • ', $parts) : 'Aucune donnée';
    }
}