<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250510152344 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE expense ADD COLUMN is_recurring BOOLEAN DEFAULT NULL');
        $this->addSql('ALTER TABLE expense ADD COLUMN pay_on_months CLOB DEFAULT NULL');
        $this->addSql('CREATE TEMPORARY TABLE __temp__expense_type AS SELECT id, code, name, description, distribution_method FROM expense_type');
        $this->addSql('DROP TABLE expense_type');
        $this->addSql('CREATE TABLE expense_type (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, code VARCHAR(6) NOT NULL, name VARCHAR(100) NOT NULL, description VARCHAR(255) DEFAULT NULL, distribution_method VARCHAR(10) DEFAULT NULL, is_recurring BOOLEAN DEFAULT NULL)');
        $this->addSql('INSERT INTO expense_type (id, code, name, description, distribution_method) SELECT id, code, name, description, distribution_method FROM __temp__expense_type');
        $this->addSql('DROP TABLE __temp__expense_type');
        $this->addSql('CREATE TEMPORARY TABLE __temp__resident AS SELECT id, unit, created_at, updated_at, ideal_fraction FROM resident');
        $this->addSql('DROP TABLE resident');
        $this->addSql('CREATE TABLE resident (id CHAR(36) NOT NULL, unit VARCHAR(10) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, ideal_fraction DOUBLE PRECISION DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('INSERT INTO resident (id, unit, created_at, updated_at, ideal_fraction) SELECT id, unit, created_at, updated_at, ideal_fraction FROM __temp__resident');
        $this->addSql('DROP TABLE __temp__resident');
        $this->addSql('ALTER TABLE slip ADD COLUMN due_date DATETIME NOT NULL');
        $this->addSql('CREATE TEMPORARY TABLE __temp__user AS SELECT id, full_name, username, email, password, roles, resident_id FROM user');
        $this->addSql('DROP TABLE user');
        $this->addSql('CREATE TABLE user (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, full_name VARCHAR(255) NOT NULL, username VARCHAR(255) NOT NULL, email VARCHAR(255) NOT NULL, password VARCHAR(255) NOT NULL, roles CLOB NOT NULL, resident_id CHAR(36) DEFAULT NULL, CONSTRAINT FK_8D93D6498012C5B0 FOREIGN KEY (resident_id) REFERENCES resident (id) ON UPDATE NO ACTION ON DELETE NO ACTION NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO user (id, full_name, username, email, password, roles, resident_id) SELECT id, full_name, username, email, password, roles, resident_id FROM __temp__user');
        $this->addSql('DROP TABLE __temp__user');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D649E7927C74 ON user (email)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D649F85E0677 ON user (username)');
        $this->addSql('CREATE INDEX IDX_8D93D6498012C5B0 ON user (resident_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TEMPORARY TABLE __temp__expense AS SELECT id, amount, description, due_date, paid_at, created_at, account_id, type_id FROM expense');
        $this->addSql('DROP TABLE expense');
        $this->addSql('CREATE TABLE expense (id CHAR(36) NOT NULL, amount INTEGER NOT NULL, description VARCHAR(255) DEFAULT NULL, due_date DATETIME NOT NULL, paid_at DATETIME DEFAULT NULL, created_at DATETIME NOT NULL, account_id CHAR(36) DEFAULT NULL, type_id INTEGER DEFAULT NULL, PRIMARY KEY(id), CONSTRAINT FK_2D3A8DA69B6B5FBA FOREIGN KEY (account_id) REFERENCES account (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_2D3A8DA6C54C8C93 FOREIGN KEY (type_id) REFERENCES expense_type (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO expense (id, amount, description, due_date, paid_at, created_at, account_id, type_id) SELECT id, amount, description, due_date, paid_at, created_at, account_id, type_id FROM __temp__expense');
        $this->addSql('DROP TABLE __temp__expense');
        $this->addSql('CREATE INDEX IDX_2D3A8DA69B6B5FBA ON expense (account_id)');
        $this->addSql('CREATE INDEX IDX_2D3A8DA6C54C8C93 ON expense (type_id)');
        $this->addSql('CREATE TEMPORARY TABLE __temp__expense_type AS SELECT id, code, name, distribution_method, description FROM expense_type');
        $this->addSql('DROP TABLE expense_type');
        $this->addSql('CREATE TABLE expense_type (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, code VARCHAR(6) NOT NULL, name VARCHAR(100) NOT NULL, distribution_method VARCHAR(10) DEFAULT NULL, description VARCHAR(255) DEFAULT NULL)');
        $this->addSql('INSERT INTO expense_type (id, code, name, distribution_method, description) SELECT id, code, name, distribution_method, description FROM __temp__expense_type');
        $this->addSql('DROP TABLE __temp__expense_type');
        $this->addSql('CREATE TEMPORARY TABLE __temp__resident AS SELECT id, unit, created_at, updated_at, ideal_fraction FROM resident');
        $this->addSql('DROP TABLE resident');
        $this->addSql('CREATE TABLE resident (id CHAR(36) NOT NULL, unit VARCHAR(10) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, ideal_fraction DOUBLE PRECISION DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('INSERT INTO resident (id, unit, created_at, updated_at, ideal_fraction) SELECT id, unit, created_at, updated_at, ideal_fraction FROM __temp__resident');
        $this->addSql('DROP TABLE __temp__resident');
        $this->addSql('CREATE TEMPORARY TABLE __temp__slip AS SELECT id, amount, status, created_at, residence_id FROM slip');
        $this->addSql('DROP TABLE slip');
        $this->addSql('CREATE TABLE slip (id CHAR(36) NOT NULL, amount INTEGER NOT NULL, status VARCHAR(25) DEFAULT NULL, created_at DATETIME NOT NULL, residence_id CHAR(36) DEFAULT NULL, PRIMARY KEY(id), CONSTRAINT FK_FD3943F88B225FBD FOREIGN KEY (residence_id) REFERENCES resident (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO slip (id, amount, status, created_at, residence_id) SELECT id, amount, status, created_at, residence_id FROM __temp__slip');
        $this->addSql('DROP TABLE __temp__slip');
        $this->addSql('CREATE INDEX IDX_FD3943F88B225FBD ON slip (residence_id)');
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
