<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250511161049 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE recurring_expense_definition (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, description VARCHAR(255) NOT NULL, amount INTEGER DEFAULT NULL, frequency VARCHAR(20) DEFAULT NULL, due_day INTEGER DEFAULT NULL, months_of_years CLOB DEFAULT NULL, start_date DATE NOT NULL, end_date DATE DEFAULT NULL, occorrences_left INTEGER DEFAULT NULL, is_active BOOLEAN NOT NULL, notes CLOB DEFAULT NULL, expense_type_id INTEGER DEFAULT NULL, CONSTRAINT FK_C31E8FDFA857C7A9 FOREIGN KEY (expense_type_id) REFERENCES expense_type (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_C31E8FDFA857C7A9 ON recurring_expense_definition (expense_type_id)');
        $this->addSql('CREATE TEMPORARY TABLE __temp__expense_type AS SELECT id, code, name, description, distribution_method, is_recurring FROM expense_type');
        $this->addSql('DROP TABLE expense_type');
        $this->addSql('CREATE TABLE expense_type (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, code VARCHAR(6) NOT NULL, name VARCHAR(100) NOT NULL, description VARCHAR(255) DEFAULT NULL, distribution_method VARCHAR(10) NOT NULL, is_recurring BOOLEAN DEFAULT NULL)');
        $this->addSql('INSERT INTO expense_type (id, code, name, description, distribution_method, is_recurring) SELECT id, code, name, description, distribution_method, is_recurring FROM __temp__expense_type');
        $this->addSql('DROP TABLE __temp__expense_type');
        $this->addSql('CREATE TEMPORARY TABLE __temp__resident AS SELECT id, unit, created_at, updated_at, ideal_fraction FROM resident');
        $this->addSql('DROP TABLE resident');
        $this->addSql('CREATE TABLE resident (id CHAR(36) NOT NULL, unit VARCHAR(10) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, ideal_fraction DOUBLE PRECISION NOT NULL, PRIMARY KEY(id))');
        $this->addSql('INSERT INTO resident (id, unit, created_at, updated_at, ideal_fraction) SELECT id, unit, created_at, updated_at, ideal_fraction FROM __temp__resident');
        $this->addSql('DROP TABLE __temp__resident');
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
        $this->addSql('DROP TABLE recurring_expense_definition');
        $this->addSql('CREATE TEMPORARY TABLE __temp__expense_type AS SELECT id, code, name, distribution_method, is_recurring, description FROM expense_type');
        $this->addSql('DROP TABLE expense_type');
        $this->addSql('CREATE TABLE expense_type (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, code VARCHAR(6) NOT NULL, name VARCHAR(100) NOT NULL, distribution_method VARCHAR(10) DEFAULT NULL, is_recurring BOOLEAN DEFAULT NULL, description VARCHAR(255) DEFAULT NULL)');
        $this->addSql('INSERT INTO expense_type (id, code, name, distribution_method, is_recurring, description) SELECT id, code, name, distribution_method, is_recurring, description FROM __temp__expense_type');
        $this->addSql('DROP TABLE __temp__expense_type');
        $this->addSql('CREATE TEMPORARY TABLE __temp__resident AS SELECT id, unit, created_at, updated_at, ideal_fraction FROM resident');
        $this->addSql('DROP TABLE resident');
        $this->addSql('CREATE TABLE resident (id CHAR(36) NOT NULL, unit VARCHAR(10) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, ideal_fraction DOUBLE PRECISION DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('INSERT INTO resident (id, unit, created_at, updated_at, ideal_fraction) SELECT id, unit, created_at, updated_at, ideal_fraction FROM __temp__resident');
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
