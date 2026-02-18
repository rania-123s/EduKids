<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260211123000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add youtube_url column to lecon for storing youtube link';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE lecon ADD youtube_url VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE lecon DROP youtube_url');
    }
}

