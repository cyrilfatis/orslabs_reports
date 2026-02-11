<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260207133211 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE report (id INT AUTO_INCREMENT NOT NULL, period VARCHAR(7) NOT NULL, filename VARCHAR(255) NOT NULL, title LONGTEXT DEFAULT NULL, description LONGTEXT DEFAULT NULL, impressions INT DEFAULT NULL, clicks INT DEFAULT NULL, ctr DOUBLE PRECISION DEFAULT NULL, position INT DEFAULT NULL, organic_sessions INT DEFAULT NULL, created_at DATETIME NOT NULL, report_date DATETIME DEFAULT NULL, is_active TINYINT NOT NULL, UNIQUE INDEX UNIQ_C42F7784C5B81ECE (period), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE report');
    }
}
