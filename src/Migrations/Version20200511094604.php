<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200511094604 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE users ADD foreman_request_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE users ADD foreman_request_date TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('ALTER TABLE users ALTER rank SET DEFAULT 1');
        $this->addSql('ALTER TABLE users ADD CONSTRAINT FK_1483A5E9712708DF FOREIGN KEY (foreman_request_id) REFERENCES users (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_1483A5E9712708DF ON users (foreman_request_id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE users DROP CONSTRAINT FK_1483A5E9712708DF');
        $this->addSql('DROP INDEX IDX_1483A5E9712708DF');
        $this->addSql('ALTER TABLE users DROP foreman_request_id');
        $this->addSql('ALTER TABLE users DROP foreman_request_date');
        $this->addSql('ALTER TABLE users ALTER rank SET DEFAULT 0');
    }
}
