<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260217190000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add image column to lecon for lesson thumbnail';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE lecon ADD image VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE lecon DROP image');
    }
}
