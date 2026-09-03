<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260903142850 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'mailing_file.sent_at devient nullable (campagne en attente)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE mailing_file CHANGE sent_at sent_at DATETIME DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE mailing_file CHANGE sent_at sent_at DATETIME NOT NULL');
    }
}
