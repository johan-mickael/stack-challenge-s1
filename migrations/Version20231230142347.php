<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20231230142347 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE bill RENAME COLUMN nom_client TO name_client');
        $this->addSql('ALTER TABLE customer ALTER search_vector TYPE TSVECTOR');
        $this->addSql('ALTER TABLE quote ALTER search_vector TYPE TSVECTOR');
        $this->addSql('ALTER TABLE "user" DROP CONSTRAINT fk_8d93d649a4aeafea');
        $this->addSql('DROP INDEX idx_8d93d649a4aeafea');
        $this->addSql('ALTER TABLE "user" RENAME COLUMN entreprise_id TO company_id');
        $this->addSql('ALTER TABLE "user" ADD CONSTRAINT FK_8D93D649979B1AD6 FOREIGN KEY (company_id) REFERENCES company (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_8D93D649979B1AD6 ON "user" (company_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE customer ALTER search_vector TYPE TEXT');
        $this->addSql('ALTER TABLE "user" DROP CONSTRAINT FK_8D93D649979B1AD6');
        $this->addSql('DROP INDEX IDX_8D93D649979B1AD6');
        $this->addSql('ALTER TABLE "user" RENAME COLUMN company_id TO entreprise_id');
        $this->addSql('ALTER TABLE "user" ADD CONSTRAINT fk_8d93d649a4aeafea FOREIGN KEY (entreprise_id) REFERENCES company (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX idx_8d93d649a4aeafea ON "user" (entreprise_id)');
        $this->addSql('ALTER TABLE bill RENAME COLUMN name_client TO nom_client');
        $this->addSql('ALTER TABLE quote ALTER search_vector TYPE TEXT');
    }
}