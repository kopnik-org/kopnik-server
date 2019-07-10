<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190710071031 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE users (id INT AUTO_INCREMENT NOT NULL, foreman_id INT DEFAULT NULL, witness_id INT DEFAULT NULL, created_at DATETIME NOT NULL, last_login_at DATETIME DEFAULT NULL, confirmed_at DATETIME DEFAULT NULL, first_name VARCHAR(32) NOT NULL, last_name VARCHAR(32) DEFAULT NULL, patronymic VARCHAR(32) DEFAULT NULL, passport_code INT DEFAULT NULL, is_confirmed TINYINT(1) NOT NULL, birth_year INT DEFAULT NULL, INDEX IDX_1483A5E9D99379F0 (foreman_id), INDEX IDX_1483A5E9F28D7E1C (witness_id), INDEX IDX_1483A5E98B8E8428 (created_at), INDEX IDX_1483A5E9CFBEB2B3 (is_confirmed), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE users_oauths (id INT AUTO_INCREMENT NOT NULL, user_id INT DEFAULT NULL, created_at DATETIME NOT NULL, email VARCHAR(100) DEFAULT NULL, access_token VARCHAR(100) NOT NULL, refresh_token VARCHAR(100) DEFAULT NULL, identifier BIGINT NOT NULL, provider VARCHAR(20) NOT NULL, INDEX IDX_88E49D4AA76ED395 (user_id), INDEX IDX_88E49D4A8B8E8428 (created_at), INDEX IDX_88E49D4AE7927C74 (email), UNIQUE INDEX UNIQ_88E49D4A772E836A92C4739C (identifier, provider), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE users ADD CONSTRAINT FK_1483A5E9D99379F0 FOREIGN KEY (foreman_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE users ADD CONSTRAINT FK_1483A5E9F28D7E1C FOREIGN KEY (witness_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE users_oauths ADD CONSTRAINT FK_88E49D4AA76ED395 FOREIGN KEY (user_id) REFERENCES users (id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE users DROP FOREIGN KEY FK_1483A5E9D99379F0');
        $this->addSql('ALTER TABLE users DROP FOREIGN KEY FK_1483A5E9F28D7E1C');
        $this->addSql('ALTER TABLE users_oauths DROP FOREIGN KEY FK_88E49D4AA76ED395');
        $this->addSql('DROP TABLE users');
        $this->addSql('DROP TABLE users_oauths');
    }
}
