<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200427012358 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('CREATE TABLE users_closure (id SERIAL NOT NULL, ancestor INT NOT NULL, descendant INT NOT NULL, depth INT NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_B807F91BB4465BB ON users_closure (ancestor)');
        $this->addSql('CREATE INDEX IDX_B807F91B9A8FAD16 ON users_closure (descendant)');
        $this->addSql('CREATE INDEX IDX_7DF222F75AFE18F4 ON users_closure (depth)');
        $this->addSql('CREATE UNIQUE INDEX IDX_C64B2E1BC59206E4 ON users_closure (ancestor, descendant)');
        $this->addSql('ALTER TABLE users_closure ADD CONSTRAINT FK_B807F91BB4465BB FOREIGN KEY (ancestor) REFERENCES users (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE users_closure ADD CONSTRAINT FK_B807F91B9A8FAD16 FOREIGN KEY (descendant) REFERENCES users (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE users ADD rank INT DEFAULT 1 NOT NULL');
        $this->addSql('ALTER TABLE users ALTER role SET DEFAULT 5');
        $this->addSql('CREATE INDEX IDX_1483A5E98879E8E5 ON users (rank)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('CREATE SCHEMA public');
        $this->addSql('DROP TABLE users_closure');
        $this->addSql('DROP INDEX IDX_1483A5E98879E8E5');
        $this->addSql('ALTER TABLE users DROP rank');
        $this->addSql('ALTER TABLE users ALTER role SET DEFAULT 0');
    }
}
