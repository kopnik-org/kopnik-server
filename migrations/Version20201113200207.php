<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20201113200207 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE users RENAME COLUMN foreman_chat_invite_link TO ten_chat_invite_link');
        $this->addSql('ALTER TABLE users RENAME COLUMN foreman_chat_id TO ten_chat_id');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE users RENAME COLUMN ten_chat_invite_link TO foreman_chat_invite_link');
        $this->addSql('ALTER TABLE users RENAME COLUMN ten_chat_id TO foreman_chat_id');
    }
}
