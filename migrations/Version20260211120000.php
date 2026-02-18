<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260211120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add video_url column to lecon for storing lesson video file';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE lecon ADD video_url VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE lecon DROP video_url');
    }
}

