<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250513011936 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TEMPORARY TABLE __temp__expense AS SELECT id, amount, description, due_date, paid_at, created_at, account_id, type_id FROM expense');
        $this->addSql('DROP TABLE expense');
        $this->addSql('CREATE TABLE expense (id CHAR(36) NOT NULL, amount INTEGER NOT NULL, description VARCHAR(255) DEFAULT NULL, due_date DATETIME NOT NULL, paid_at DATETIME DEFAULT NULL, created_at DATETIME NOT NULL, account_id CHAR(36) DEFAULT NULL, type_id INTEGER DEFAULT NULL, recurring_id CHAR(36) DEFAULT NULL, PRIMARY KEY(id), CONSTRAINT FK_2D3A8DA69B6B5FBA FOREIGN KEY (account_id) REFERENCES account (id) ON UPDATE NO ACTION ON DELETE NO ACTION NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_2D3A8DA6C54C8C93 FOREIGN KEY (type_id) REFERENCES expense_type (id) ON UPDATE NO ACTION ON DELETE NO ACTION NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_2D3A8DA6B149C95E FOREIGN KEY (recurring_id) REFERENCES recurring_expense (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO expense (id, amount, description, due_date, paid_at, created_at, account_id, type_id) SELECT id, amount, description, due_date, paid_at, created_at, account_id, type_id FROM __temp__expense');
        $this->addSql('DROP TABLE __temp__expense');
        $this->addSql('CREATE INDEX IDX_2D3A8DA6C54C8C93 ON expense (type_id)');
        $this->addSql('CREATE INDEX IDX_2D3A8DA69B6B5FBA ON expense (account_id)');
        $this->addSql('CREATE INDEX IDX_2D3A8DA6B149C95E ON expense (recurring_id)');
        $this->addSql('CREATE TEMPORARY TABLE __temp__recurring_expense AS SELECT id, description, amount, frequency, due_day, months_of_years, start_date, end_date, occorrences_left, is_active, notes, expense_type_id FROM recurring_expense');
        $this->addSql('DROP TABLE recurring_expense');
        $this->addSql('CREATE TABLE recurring_expense (id CHAR(36) NOT NULL, description VARCHAR(255) NOT NULL, amount INTEGER DEFAULT NULL, frequency VARCHAR(20) DEFAULT NULL, due_day INTEGER DEFAULT NULL, months_of_year CLOB DEFAULT NULL, start_date DATE NOT NULL, end_date DATETIME DEFAULT NULL, occurrences_left INTEGER DEFAULT NULL, is_active BOOLEAN NOT NULL, notes CLOB DEFAULT NULL, expense_type_id INTEGER DEFAULT NULL, created_at DATETIME NOT NULL, PRIMARY KEY(id), CONSTRAINT FK_F5CC182FA857C7A9 FOREIGN KEY (expense_type_id) REFERENCES expense_type (id) ON UPDATE NO ACTION ON DELETE NO ACTION NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO recurring_expense (id, description, amount, frequency, due_day, months_of_year, start_date, end_date, occurrences_left, is_active, notes, expense_type_id) SELECT id, description, amount, frequency, due_day, months_of_years, start_date, end_date, occorrences_left, is_active, notes, expense_type_id FROM __temp__recurring_expense');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TEMPORARY TABLE __temp__expense AS SELECT id, amount, description, due_date, paid_at, created_at, account_id, type_id FROM expense');
        $this->addSql('DROP TABLE expense');
        $this->addSql('CREATE TABLE expense (id CHAR(36) NOT NULL, amount INTEGER NOT NULL, description VARCHAR(255) DEFAULT NULL, due_date DATETIME NOT NULL, paid_at DATETIME DEFAULT NULL, created_at DATETIME NOT NULL, account_id CHAR(36) DEFAULT NULL, type_id INTEGER DEFAULT NULL, is_recurring BOOLEAN DEFAULT NULL, pay_on_months CLOB DEFAULT NULL, PRIMARY KEY(id), CONSTRAINT FK_2D3A8DA69B6B5FBA FOREIGN KEY (account_id) REFERENCES account (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_2D3A8DA6C54C8C93 FOREIGN KEY (type_id) REFERENCES expense_type (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO expense (id, amount, description, due_date, paid_at, created_at, account_id, type_id) SELECT id, amount, description, due_date, paid_at, created_at, account_id, type_id FROM __temp__expense');
        $this->addSql('DROP TABLE __temp__expense');
        $this->addSql('CREATE INDEX IDX_2D3A8DA69B6B5FBA ON expense (account_id)');
        $this->addSql('CREATE INDEX IDX_2D3A8DA6C54C8C93 ON expense (type_id)');
        $this->addSql('CREATE TEMPORARY TABLE __temp__recurring_expense AS SELECT id, description, amount, frequency, due_day, months_of_year, start_date, end_date, occurrences_left, is_active, notes, expense_type_id FROM recurring_expense');
        $this->addSql('DROP TABLE recurring_expense');
        $this->addSql('CREATE TABLE recurring_expense (id CHAR(36) NOT NULL, description VARCHAR(255) NOT NULL, amount INTEGER DEFAULT NULL, frequency VARCHAR(20) DEFAULT NULL, due_day INTEGER DEFAULT NULL, months_of_years CLOB DEFAULT NULL, start_date DATE NOT NULL, end_date DATE DEFAULT NULL, occorrences_left INTEGER DEFAULT NULL, is_active BOOLEAN NOT NULL, notes CLOB DEFAULT NULL, expense_type_id INTEGER DEFAULT NULL, CONSTRAINT FK_F5CC182FA857C7A9 FOREIGN KEY (expense_type_id) REFERENCES expense_type (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO recurring_expense (id, description, amount, frequency, due_day, months_of_years, start_date, end_date, occorrences_left, is_active, notes, expense_type_id) SELECT id, description, amount, frequency, due_day, months_of_year, start_date, end_date, occurrences_left, is_active, notes, expense_type_id FROM __temp__recurring_expense');
        $this->addSql('DROP TABLE __temp__recurring_expense');
        $this->addSql('CREATE INDEX IDX_F5CC182FA857C7A9 ON recurring_expense (expense_type_id)');
    }
}
