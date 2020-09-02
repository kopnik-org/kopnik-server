<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200719093018 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('DROP INDEX idx_1483a5e957698a6a');
        $this->addSql('ALTER TABLE users RENAME COLUMN role TO kopnik_role');
        $this->addSql('CREATE INDEX IDX_1483A5E9BD2F75F2 ON users (kopnik_role)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('CREATE SCHEMA public');
        $this->addSql('DROP INDEX IDX_1483A5E9BD2F75F2');
        $this->addSql('ALTER TABLE users RENAME COLUMN kopnik_role TO role');
        $this->addSql('CREATE INDEX idx_1483a5e957698a6a ON users (role)');
    }
}
