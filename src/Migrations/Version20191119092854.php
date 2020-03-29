<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20191119092854 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('DROP INDEX idx_1483a5e9cfbeb2b3');
        $this->addSql('ALTER TABLE users ADD status SMALLINT DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE users DROP is_confirmed');
        $this->addSql('CREATE INDEX IDX_1483A5E97B00651C ON users (status)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('DROP INDEX IDX_1483A5E97B00651C');
        $this->addSql('ALTER TABLE users ADD is_confirmed BOOLEAN NOT NULL');
        $this->addSql('ALTER TABLE users DROP status');
        $this->addSql('CREATE INDEX idx_1483a5e9cfbeb2b3 ON users (is_confirmed)');
    }
}
