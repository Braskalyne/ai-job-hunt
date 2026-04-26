<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260422201000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create job_offer table to store scraped jobs.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE job_offer (id SERIAL NOT NULL, source VARCHAR(20) NOT NULL, external_id VARCHAR(255) NOT NULL, title VARCHAR(255) NOT NULL, company VARCHAR(255) NOT NULL, location VARCHAR(255) DEFAULT NULL, url VARCHAR(1024) NOT NULL, published_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX uniq_job_offer_source_external ON job_offer (source, external_id)');
        $this->addSql('CREATE INDEX idx_job_offer_published_at ON job_offer (published_at)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE job_offer');
    }
}
