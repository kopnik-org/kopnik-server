<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190826154111 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('CREATE SEQUENCE users_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE users_oauths_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE users (id INT NOT NULL, foreman_id INT DEFAULT NULL, witness_id INT DEFAULT NULL, last_login_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, confirmed_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, first_name VARCHAR(32) NOT NULL, last_name VARCHAR(32) DEFAULT NULL, patronymic VARCHAR(32) DEFAULT NULL, passport_code INT DEFAULT NULL, is_confirmed BOOLEAN NOT NULL, birth_year INT DEFAULT NULL, latitude NUMERIC(10, 8) DEFAULT NULL, longitude NUMERIC(11, 8) DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_1483A5E9D99379F0 ON users (foreman_id)');
        $this->addSql('CREATE INDEX IDX_1483A5E9F28D7E1C ON users (witness_id)');
        $this->addSql('CREATE INDEX IDX_1483A5E98B8E8428 ON users (created_at)');
        $this->addSql('CREATE INDEX IDX_1483A5E9CFBEB2B3 ON users (is_confirmed)');
        $this->addSql('CREATE TABLE users_oauths (id INT NOT NULL, user_id INT DEFAULT NULL, email VARCHAR(100) DEFAULT NULL, access_token VARCHAR(100) NOT NULL, refresh_token VARCHAR(100) DEFAULT NULL, identifier BIGINT NOT NULL, provider VARCHAR(20) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_88E49D4AA76ED395 ON users_oauths (user_id)');
        $this->addSql('CREATE INDEX IDX_88E49D4A8B8E8428 ON users_oauths (created_at)');
        $this->addSql('CREATE INDEX IDX_88E49D4AE7927C74 ON users_oauths (email)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_88E49D4A772E836A92C4739C ON users_oauths (identifier, provider)');
        $this->addSql('ALTER TABLE users ADD CONSTRAINT FK_1483A5E9D99379F0 FOREIGN KEY (foreman_id) REFERENCES users (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE users ADD CONSTRAINT FK_1483A5E9F28D7E1C FOREIGN KEY (witness_id) REFERENCES users (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE users_oauths ADD CONSTRAINT FK_88E49D4AA76ED395 FOREIGN KEY (user_id) REFERENCES users (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE users DROP CONSTRAINT FK_1483A5E9D99379F0');
        $this->addSql('ALTER TABLE users DROP CONSTRAINT FK_1483A5E9F28D7E1C');
        $this->addSql('ALTER TABLE users_oauths DROP CONSTRAINT FK_88E49D4AA76ED395');
        $this->addSql('DROP SEQUENCE users_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE users_oauths_id_seq CASCADE');
        $this->addSql('DROP TABLE users');
        $this->addSql('DROP TABLE users_oauths');
    }
}
