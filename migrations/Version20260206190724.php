<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260206190724 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE chat ADD is_muted TINYINT(1) DEFAULT 0, ADD is_read TINYINT(1) DEFAULT 0');
        $this->addSql('UPDATE chat SET is_muted = 0 WHERE is_muted IS NULL');
        $this->addSql('UPDATE chat SET is_read = 0 WHERE is_read IS NULL');
        $this->addSql('ALTER TABLE chat MODIFY is_muted TINYINT(1) NOT NULL, MODIFY is_read TINYINT(1) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE chat DROP is_muted, DROP is_read');
    }
}
