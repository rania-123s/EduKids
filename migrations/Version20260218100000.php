<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260218100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add likes and dislikes columns to cours';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE cours ADD likes INT NOT NULL DEFAULT 0, ADD dislikes INT NOT NULL DEFAULT 0');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE cours DROP likes, DROP dislikes');
    }
}
