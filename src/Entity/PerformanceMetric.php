<?php

namespace App\Entity;

use App\Repository\PerformanceMetricRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Stocke les indicateurs de performance (SEO, LinkedIn, etc.)
 * Les données SEO sont extraites automatiquement des rapports.
 * Les données LinkedIn sont saisies manuellement (futur: via EasyAdmin).
 */
#[ORM\Entity(repositoryClass: PerformanceMetricRepository::class)]
#[ORM\Table(name: 'performance_metric')]
#[ORM\UniqueConstraint(name: 'unique_period_source', columns: ['period', 'source'])]
class PerformanceMetric
{
    public const SOURCE_SEO = 'seo';
    public const SOURCE_LINKEDIN = 'linkedin';
    public const SOURCE_ANALYTICS = 'analytics';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /** Format: 2025-12 */
    #[ORM\Column(length: 7)]
    private ?string $period = null;

    /** seo, linkedin, analytics */
    #[ORM\Column(length: 30)]
    private ?string $source = null;

    // --- SEO Metrics (Google Search Console) ---

    #[ORM\Column(nullable: true)]
    private ?int $impressions = null;

    #[ORM\Column(nullable: true)]
    private ?int $clicks = null;

    #[ORM\Column(nullable: true)]
    private ?float $ctr = null;

    #[ORM\Column(nullable: true)]
    private ?float $avgPosition = null;

    // --- Analytics Metrics (GA4) ---

    #[ORM\Column(nullable: true)]
    private ?int $organicSessions = null;

    #[ORM\Column(nullable: true)]
    private ?int $directTraffic = null;

    #[ORM\Column(nullable: true)]
    private ?int $totalUsers = null;

    #[ORM\Column(nullable: true)]
    private ?float $bounceRate = null;

    #[ORM\Column(nullable: true)]
    private ?float $engagementRate = null;

    #[ORM\Column(nullable: true)]
    private ?int $avgSessionDurationSeconds = null;

    #[ORM\Column(nullable: true)]
    private ?int $organicSocialSessions = null;

    // --- LinkedIn Metrics (manual) ---

    #[ORM\Column(nullable: true)]
    private ?int $linkedinFollowers = null;

    #[ORM\Column(nullable: true)]
    private ?int $linkedinImpressions = null;

    #[ORM\Column(nullable: true)]
    private ?int $linkedinReactions = null;

    #[ORM\Column(nullable: true)]
    private ?int $linkedinComments = null;

    #[ORM\Column(nullable: true)]
    private ?int $linkedinShares = null;

    #[ORM\Column(nullable: true)]
    private ?int $linkedinProfileViews = null;

    #[ORM\Column(nullable: true)]
    private ?int $linkedinPostsPublished = null;

    #[ORM\Column(nullable: true)]
    private ?float $linkedinEngagementRate = null;

    // --- Meta ---

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $notes = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $updatedAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    // --- Getters/Setters ---

    public function getId(): ?int { return $this->id; }

    public function getPeriod(): ?string { return $this->period; }
    public function setPeriod(string $period): static { $this->period = $period; return $this; }

    public function getSource(): ?string { return $this->source; }
    public function setSource(string $source): static { $this->source = $source; return $this; }

    public function getImpressions(): ?int { return $this->impressions; }
    public function setImpressions(?int $impressions): static { $this->impressions = $impressions; return $this; }

    public function getClicks(): ?int { return $this->clicks; }
    public function setClicks(?int $clicks): static { $this->clicks = $clicks; return $this; }

    public function getCtr(): ?float { return $this->ctr; }
    public function setCtr(?float $ctr): static { $this->ctr = $ctr; return $this; }

    public function getAvgPosition(): ?float { return $this->avgPosition; }
    public function setAvgPosition(?float $avgPosition): static { $this->avgPosition = $avgPosition; return $this; }

    public function getOrganicSessions(): ?int { return $this->organicSessions; }
    public function setOrganicSessions(?int $organicSessions): static { $this->organicSessions = $organicSessions; return $this; }

    public function getDirectTraffic(): ?int { return $this->directTraffic; }
    public function setDirectTraffic(?int $directTraffic): static { $this->directTraffic = $directTraffic; return $this; }

    public function getTotalUsers(): ?int { return $this->totalUsers; }
    public function setTotalUsers(?int $totalUsers): static { $this->totalUsers = $totalUsers; return $this; }

    public function getBounceRate(): ?float { return $this->bounceRate; }
    public function setBounceRate(?float $bounceRate): static { $this->bounceRate = $bounceRate; return $this; }

    public function getEngagementRate(): ?float { return $this->engagementRate; }
    public function setEngagementRate(?float $engagementRate): static { $this->engagementRate = $engagementRate; return $this; }

    public function getAvgSessionDurationSeconds(): ?int { return $this->avgSessionDurationSeconds; }
    public function setAvgSessionDurationSeconds(?int $seconds): static { $this->avgSessionDurationSeconds = $seconds; return $this; }

    public function getOrganicSocialSessions(): ?int { return $this->organicSocialSessions; }
    public function setOrganicSocialSessions(?int $sessions): static { $this->organicSocialSessions = $sessions; return $this; }

    public function getLinkedinFollowers(): ?int { return $this->linkedinFollowers; }
    public function setLinkedinFollowers(?int $v): static { $this->linkedinFollowers = $v; return $this; }

    public function getLinkedinImpressions(): ?int { return $this->linkedinImpressions; }
    public function setLinkedinImpressions(?int $v): static { $this->linkedinImpressions = $v; return $this; }

    public function getLinkedinReactions(): ?int { return $this->linkedinReactions; }
    public function setLinkedinReactions(?int $v): static { $this->linkedinReactions = $v; return $this; }

    public function getLinkedinComments(): ?int { return $this->linkedinComments; }
    public function setLinkedinComments(?int $v): static { $this->linkedinComments = $v; return $this; }

    public function getLinkedinShares(): ?int { return $this->linkedinShares; }
    public function setLinkedinShares(?int $v): static { $this->linkedinShares = $v; return $this; }

    public function getLinkedinProfileViews(): ?int { return $this->linkedinProfileViews; }
    public function setLinkedinProfileViews(?int $v): static { $this->linkedinProfileViews = $v; return $this; }

    public function getLinkedinPostsPublished(): ?int { return $this->linkedinPostsPublished; }
    public function setLinkedinPostsPublished(?int $v): static { $this->linkedinPostsPublished = $v; return $this; }

    public function getLinkedinEngagementRate(): ?float { return $this->linkedinEngagementRate; }
    public function setLinkedinEngagementRate(?float $v): static { $this->linkedinEngagementRate = $v; return $this; }

    public function getNotes(): ?string { return $this->notes; }
    public function setNotes(?string $notes): static { $this->notes = $notes; return $this; }

    public function getCreatedAt(): ?\DateTimeImmutable { return $this->createdAt; }
    public function setCreatedAt(\DateTimeImmutable $createdAt): static { $this->createdAt = $createdAt; return $this; }

    public function getUpdatedAt(): ?\DateTimeImmutable { return $this->updatedAt; }
    public function setUpdatedAt(\DateTimeImmutable $updatedAt): static { $this->updatedAt = $updatedAt; return $this; }

    /**
     * Retourne le nom du mois en français
     */
    public function getMonthName(): string
    {
        $months = [
            1 => 'Janvier', 2 => 'Février', 3 => 'Mars', 4 => 'Avril',
            5 => 'Mai', 6 => 'Juin', 7 => 'Juillet', 8 => 'Août',
            9 => 'Septembre', 10 => 'Octobre', 11 => 'Novembre', 12 => 'Décembre'
        ];
        $parts = explode('-', $this->period);
        if (count($parts) === 2) {
            return $months[(int)$parts[1]] . ' ' . $parts[0];
        }
        return $this->period;
    }

    /**
     * Formate la durée de session
     */
    public function getFormattedSessionDuration(): string
    {
        if ($this->avgSessionDurationSeconds === null) return 'N/A';
        $min = intdiv($this->avgSessionDurationSeconds, 60);
        $sec = $this->avgSessionDurationSeconds % 60;
        return sprintf('%d:%02d', $min, $sec);
    }
}
