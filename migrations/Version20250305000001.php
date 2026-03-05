<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250305000001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Création des tables document_category et document';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('
            CREATE TABLE document_category (
                id          INT AUTO_INCREMENT NOT NULL,
                name        VARCHAR(100) NOT NULL,
                slug        VARCHAR(100) NOT NULL,
                description VARCHAR(255) DEFAULT NULL,
                icon        VARCHAR(50)  DEFAULT NULL,
                color       VARCHAR(20)  DEFAULT NULL,
                sort_order  INT NOT NULL DEFAULT 0,
                created_at  DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
                UNIQUE INDEX UNIQ_CATEGORY_SLUG (slug),
                PRIMARY KEY (id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB
        ');

        $this->addSql('
            CREATE TABLE document (
                id             INT AUTO_INCREMENT NOT NULL,
                category_id    INT NOT NULL,
                uploaded_by_id INT NOT NULL,
                deleted_by_id  INT DEFAULT NULL,
                original_name  VARCHAR(255)  NOT NULL,
                stored_name    VARCHAR(255)  NOT NULL,
                mime_type      VARCHAR(20)   NOT NULL,
                file_size      INT           NOT NULL,
                description    VARCHAR(1000) DEFAULT NULL,
                uploaded_at    DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
                deleted_at     DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\',
                INDEX IDX_DOCUMENT_CATEGORY    (category_id),
                INDEX IDX_DOCUMENT_UPLOADED_BY (uploaded_by_id),
                INDEX IDX_DOCUMENT_DELETED_BY  (deleted_by_id),
                PRIMARY KEY (id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB
        ');

        $this->addSql('
            ALTER TABLE document
                ADD CONSTRAINT FK_DOCUMENT_CATEGORY
                    FOREIGN KEY (category_id)    REFERENCES document_category (id),
                ADD CONSTRAINT FK_DOCUMENT_UPLOADER
                    FOREIGN KEY (uploaded_by_id) REFERENCES user (id),
                ADD CONSTRAINT FK_DOCUMENT_DELETER
                    FOREIGN KEY (deleted_by_id)  REFERENCES user (id)
        ');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE document DROP FOREIGN KEY FK_DOCUMENT_CATEGORY');
        $this->addSql('ALTER TABLE document DROP FOREIGN KEY FK_DOCUMENT_UPLOADER');
        $this->addSql('ALTER TABLE document DROP FOREIGN KEY FK_DOCUMENT_DELETER');
        $this->addSql('DROP TABLE document');
        $this->addSql('DROP TABLE document_category');
    }
}