<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260201190054 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE activite (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, nom_activite VARCHAR(255) NOT NULL, lieu VARCHAR(255) NOT NULL, description VARCHAR(255) NOT NULL, event_id INT DEFAULT NULL)');
        $this->addSql('CREATE INDEX IDX_B875551571F7E88B ON activite (event_id)');
        $this->addSql('CREATE TABLE chat (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, parent_id INT NOT NULL, date_creation DATETIME NOT NULL, dernier_message VARCHAR(255) NOT NULL, date_dernier_message DATETIME NOT NULL, is_muted BOOLEAN DEFAULT FALSE NOT NULL, is_read BOOLEAN DEFAULT FALSE NOT NULL)');
        $this->addSql('CREATE TABLE commande (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, date DATETIME NOT NULL, montant_total INT NOT NULL, statut VARCHAR(255) NOT NULL, user_id_id INT DEFAULT NULL)');
        $this->addSql('CREATE INDEX IDX_6EEAA67D9D86650F ON commande (user_id_id)');
        $this->addSql('CREATE TABLE cours (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, titre VARCHAR(255) NOT NULL, description VARCHAR(255) NOT NULL, niveau INT NOT NULL, matiere VARCHAR(255) NOT NULL, image VARCHAR(255) NOT NULL)');
        $this->addSql('CREATE TABLE event (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, titre VARCHAR(255) NOT NULL, description VARCHAR(255) NOT NULL, date_heure DATETIME NOT NULL)');
        $this->addSql('CREATE TABLE lecon (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, titre VARCHAR(255) NOT NULL, ordre INT NOT NULL, media_type VARCHAR(255) NOT NULL, media_url VARCHAR(255) NOT NULL, cours_id INT DEFAULT NULL)');
        $this->addSql('CREATE INDEX IDX_94E6242E7ECF78B0 ON lecon (cours_id)');
        $this->addSql('CREATE TABLE message (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, expediteur_id INT NOT NULL, contenu VARCHAR(255) NOT NULL, date_envoi DATETIME NOT NULL, lu BOOLEAN NOT NULL, chat_id INT DEFAULT NULL)');
        $this->addSql('CREATE INDEX IDX_B6BD307F1A9A7125 ON message (chat_id)');
        $this->addSql('CREATE TABLE produit (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, nom VARCHAR(255) NOT NULL, description VARCHAR(255) NOT NULL, prix INT NOT NULL, type VARCHAR(255) NOT NULL)');
        $this->addSql('CREATE TABLE question (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, enonce VARCHAR(255) NOT NULL, bonne_reponse VARCHAR(255) NOT NULL, choix INT NOT NULL, quiz_id INT DEFAULT NULL)');
        $this->addSql('CREATE INDEX IDX_B6F7494E853CD175 ON question (quiz_id)');
        $this->addSql('CREATE TABLE quiz (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, titre VARCHAR(255) NOT NULL, score_max VARCHAR(255) NOT NULL, cours_id INT NOT NULL)');
        $this->addSql('CREATE INDEX IDX_A412FA927ECF78B0 ON quiz (cours_id)');
        $this->addSql('CREATE TABLE user (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL)');
        $this->addSql('CREATE TABLE messenger_messages (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL, available_at DATETIME NOT NULL, delivered_at DATETIME DEFAULT NULL)');
        $this->addSql('CREATE INDEX IDX_75EA56E0FB7336F0E3BD61CE16BA31DBBF396750 ON messenger_messages (queue_name, available_at, delivered_at, id)');
        $this->addSql('ALTER TABLE activite ADD CONSTRAINT FK_B875551571F7E88B FOREIGN KEY (event_id) REFERENCES event (id)');
        $this->addSql('ALTER TABLE commande ADD CONSTRAINT FK_6EEAA67D9D86650F FOREIGN KEY (user_id_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE lecon ADD CONSTRAINT FK_94E6242E7ECF78B0 FOREIGN KEY (cours_id) REFERENCES cours (id)');
        $this->addSql('ALTER TABLE message ADD CONSTRAINT FK_B6BD307F1A9A7125 FOREIGN KEY (chat_id) REFERENCES chat (id)');
        $this->addSql('ALTER TABLE question ADD CONSTRAINT FK_B6F7494E853CD175 FOREIGN KEY (quiz_id) REFERENCES quiz (id)');
        $this->addSql('ALTER TABLE quiz ADD CONSTRAINT FK_A412FA927ECF78B0 FOREIGN KEY (cours_id) REFERENCES cours (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE activite DROP FOREIGN KEY FK_B875551571F7E88B');
        $this->addSql('ALTER TABLE commande DROP FOREIGN KEY FK_6EEAA67D9D86650F');
        $this->addSql('ALTER TABLE lecon DROP FOREIGN KEY FK_94E6242E7ECF78B0');
        $this->addSql('ALTER TABLE message DROP FOREIGN KEY FK_B6BD307F1A9A7125');
        $this->addSql('ALTER TABLE question DROP FOREIGN KEY FK_B6F7494E853CD175');
        $this->addSql('ALTER TABLE quiz DROP FOREIGN KEY FK_A412FA927ECF78B0');
        $this->addSql('DROP TABLE activite');
        $this->addSql('DROP TABLE chat');
        $this->addSql('DROP TABLE commande');
        $this->addSql('DROP TABLE cours');
        $this->addSql('DROP TABLE event');
        $this->addSql('DROP TABLE lecon');
        $this->addSql('DROP TABLE message');
        $this->addSql('DROP TABLE produit');
        $this->addSql('DROP TABLE question');
        $this->addSql('DROP TABLE quiz');
        $this->addSql('DROP TABLE `user`');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
