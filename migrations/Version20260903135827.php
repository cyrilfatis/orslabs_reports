<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260903135827 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajout de la table mailing_file';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE mailing_file (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(255) NOT NULL, stored_name VARCHAR(255) NOT NULL, sent_at DATETIME NOT NULL, tracking_url VARCHAR(500) DEFAULT NULL, created_at DATETIME NOT NULL, created_by_id INT NOT NULL, INDEX IDX_DD93BDA7B03A8386 (created_by_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE mailing_file ADD CONSTRAINT FK_DD93BDA7B03A8386 FOREIGN KEY (created_by_id) REFERENCES `user` (id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE mailing_file DROP FOREIGN KEY FK_DD93BDA7B03A8386');
        $this->addSql('DROP TABLE mailing_file');
    }
}
