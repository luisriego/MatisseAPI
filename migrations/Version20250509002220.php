<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250509002220 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE expense_type ADD COLUMN distribution_method VARCHAR(10) NOT NULL');
        $this->addSql('ALTER TABLE resident ADD COLUMN ideal_fraction DOUBLE PRECISION NOT NULL');
        $this->addSql('CREATE TEMPORARY TABLE __temp__user AS SELECT id, full_name, username, email, password, roles, resident_id FROM user');
        $this->addSql('DROP TABLE user');
        $this->addSql('CREATE TABLE user (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, full_name VARCHAR(255) NOT NULL, username VARCHAR(255) NOT NULL, email VARCHAR(255) NOT NULL, password VARCHAR(255) NOT NULL, roles CLOB NOT NULL, resident_id CHAR(36) NOT NULL, CONSTRAINT FK_8D93D6498012C5B0 FOREIGN KEY (resident_id) REFERENCES resident (id) ON UPDATE NO ACTION ON DELETE NO ACTION NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO user (id, full_name, username, email, password, roles, resident_id) SELECT id, full_name, username, email, password, roles, resident_id FROM __temp__user');
        $this->addSql('DROP TABLE __temp__user');
        $this->addSql('CREATE INDEX IDX_8D93D6498012C5B0 ON user (resident_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D649F85E0677 ON user (username)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D649E7927C74 ON user (email)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TEMPORARY TABLE __temp__expense_type AS SELECT id, code, name, description FROM expense_type');
        $this->addSql('DROP TABLE expense_type');
        $this->addSql('CREATE TABLE expense_type (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, code VARCHAR(6) NOT NULL, name VARCHAR(100) NOT NULL, description VARCHAR(255) DEFAULT NULL)');
        $this->addSql('INSERT INTO expense_type (id, code, name, description) SELECT id, code, name, description FROM __temp__expense_type');
        $this->addSql('DROP TABLE __temp__expense_type');
        $this->addSql('CREATE TEMPORARY TABLE __temp__resident AS SELECT id, unit, created_at, updated_at FROM resident');
        $this->addSql('DROP TABLE resident');
        $this->addSql('CREATE TABLE resident (id CHAR(36) NOT NULL, unit VARCHAR(10) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, PRIMARY KEY(id))');
        $this->addSql('INSERT INTO resident (id, unit, created_at, updated_at) SELECT id, unit, created_at, updated_at FROM __temp__resident');
        $this->addSql('DROP TABLE __temp__resident');
        $this->addSql('CREATE TEMPORARY TABLE __temp__user AS SELECT id, full_name, username, email, password, roles, resident_id FROM user');
        $this->addSql('DROP TABLE user');
        $this->addSql('CREATE TABLE user (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, full_name VARCHAR(255) NOT NULL, username VARCHAR(255) NOT NULL, email VARCHAR(255) NOT NULL, password VARCHAR(255) NOT NULL, roles CLOB NOT NULL, resident_id CHAR(36) DEFAULT NULL, CONSTRAINT FK_8D93D6498012C5B0 FOREIGN KEY (resident_id) REFERENCES resident (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO user (id, full_name, username, email, password, roles, resident_id) SELECT id, full_name, username, email, password, roles, resident_id FROM __temp__user');
        $this->addSql('DROP TABLE __temp__user');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D649F85E0677 ON user (username)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D649E7927C74 ON user (email)');
        $this->addSql('CREATE INDEX IDX_8D93D6498012C5B0 ON user (resident_id)');
    }
}
