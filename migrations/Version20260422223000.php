<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260422223000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create job_application table to track user application status for each job.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE job_application (id SERIAL NOT NULL, user_id INT NOT NULL, job_offer_id INT NOT NULL, status VARCHAR(20) NOT NULL, applied_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX uniq_job_application_user_job ON job_application (user_id, job_offer_id)');
        $this->addSql('CREATE INDEX IDX_3E8AF7DDA76ED395 ON job_application (user_id)');
        $this->addSql('CREATE INDEX IDX_3E8AF7DDC58D3B1E ON job_application (job_offer_id)');
        $this->addSql('ALTER TABLE job_application ADD CONSTRAINT FK_3E8AF7DDA76ED395 FOREIGN KEY (user_id) REFERENCES app_user (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE job_application ADD CONSTRAINT FK_3E8AF7DDC58D3B1E FOREIGN KEY (job_offer_id) REFERENCES job_offer (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE job_application DROP CONSTRAINT FK_3E8AF7DDA76ED395');
        $this->addSql('ALTER TABLE job_application DROP CONSTRAINT FK_3E8AF7DDC58D3B1E');
        $this->addSql('DROP TABLE job_application');
    }
}
