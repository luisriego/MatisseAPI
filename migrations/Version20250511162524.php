<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250511162524 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE recurring_expense (id CHAR(36) NOT NULL, description VARCHAR(255) NOT NULL, amount INTEGER DEFAULT NULL, frequency VARCHAR(20) DEFAULT NULL, due_day INTEGER DEFAULT NULL, months_of_years CLOB DEFAULT NULL, start_date DATE NOT NULL, end_date DATE DEFAULT NULL, occorrences_left INTEGER DEFAULT NULL, is_active BOOLEAN NOT NULL, notes CLOB DEFAULT NULL, expense_type_id INTEGER DEFAULT NULL, CONSTRAINT FK_F5CC182FA857C7A9 FOREIGN KEY (expense_type_id) REFERENCES expense_type (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_F5CC182FA857C7A9 ON recurring_expense (expense_type_id)');
        $this->addSql('DROP TABLE recurring_expense_definition');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE recurring_expense_definition (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, description VARCHAR(255) NOT NULL COLLATE "BINARY", amount INTEGER DEFAULT NULL, frequency VARCHAR(20) DEFAULT NULL COLLATE "BINARY", due_day INTEGER DEFAULT NULL, months_of_years CLOB DEFAULT NULL COLLATE "BINARY", start_date DATE NOT NULL, end_date DATE DEFAULT NULL, occorrences_left INTEGER DEFAULT NULL, is_active BOOLEAN NOT NULL, notes CLOB DEFAULT NULL COLLATE "BINARY", expense_type_id INTEGER DEFAULT NULL, CONSTRAINT FK_C31E8FDFA857C7A9 FOREIGN KEY (expense_type_id) REFERENCES expense_type (id) ON UPDATE NO ACTION ON DELETE NO ACTION NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_C31E8FDFA857C7A9 ON recurring_expense_definition (expense_type_id)');
        $this->addSql('DROP TABLE recurring_expense');
    }
}
