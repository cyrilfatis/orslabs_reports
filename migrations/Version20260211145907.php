<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260211145907 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE performance_metric (id INT AUTO_INCREMENT NOT NULL, period VARCHAR(7) NOT NULL, source VARCHAR(30) NOT NULL, impressions INT DEFAULT NULL, clicks INT DEFAULT NULL, ctr DOUBLE PRECISION DEFAULT NULL, avg_position DOUBLE PRECISION DEFAULT NULL, organic_sessions INT DEFAULT NULL, direct_traffic INT DEFAULT NULL, total_users INT DEFAULT NULL, bounce_rate DOUBLE PRECISION DEFAULT NULL, engagement_rate DOUBLE PRECISION DEFAULT NULL, avg_session_duration_seconds INT DEFAULT NULL, organic_social_sessions INT DEFAULT NULL, linkedin_followers INT DEFAULT NULL, linkedin_impressions INT DEFAULT NULL, linkedin_reactions INT DEFAULT NULL, linkedin_comments INT DEFAULT NULL, linkedin_shares INT DEFAULT NULL, linkedin_profile_views INT DEFAULT NULL, linkedin_posts_published INT DEFAULT NULL, linkedin_engagement_rate DOUBLE PRECISION DEFAULT NULL, notes LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, UNIQUE INDEX unique_period_source (period, source), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE performance_metric');
    }
}
