<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class InitialSchema extends AbstractMigration
{
    public function up(): void
    {
        // 1. Create 'events' table using Phinx Table builder
        $eventsTable = $this->table('events', ['id' => false, 'primary_key' => ['event_id']]);
        $eventsTable->addColumn('event_id', 'uuid')
                    ->addColumn('aggregate_id', 'uuid')
                    ->addColumn('aggregate_type', 'string', ['limit' => 255])
                    ->addColumn('event_type', 'string', ['limit' => 255])
                    ->addColumn('payload', 'jsonb')
                    ->addColumn('version', 'integer')
                    ->addColumn('occurred_on', 'timestamp', ['timezone' => true, 'default' => 'CURRENT_TIMESTAMP'])
                    ->addIndex(['aggregate_id'])
                    ->addIndex(['event_type'])
                    ->addIndex(['aggregate_id', 'version'], ['unique' => true, 'name' => 'aggregate_version_unique'])
                    ->create();

        // 2. DDL from schema.sql (excluding events table, and assuming it defines expense_categories and fee_items)
        // Manually extracted content of schema.sql (without events table)
        $schemaSql = <<<SQL
-- Condominiums Table (Read Model)
CREATE TABLE condominiums (
    id UUID PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    address_street VARCHAR(255),
    address_city VARCHAR(255),
    address_postal_code VARCHAR(50),
    address_country VARCHAR(255),
    created_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- Owners Table (Read Model)
CREATE TABLE owners (
    id UUID PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE,
    phone_number VARCHAR(50),
    created_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- Units Table (Read Model)
CREATE TABLE units (
    id UUID PRIMARY KEY,
    condominium_id UUID NOT NULL,
    owner_id UUID,
    identifier VARCHAR(100) NOT NULL,
    created_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_condominium
        FOREIGN KEY(condominium_id)
        REFERENCES condominiums(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_owner
        FOREIGN KEY(owner_id)
        REFERENCES owners(id)
        ON DELETE SET NULL,
    CONSTRAINT condominium_unit_identifier_unique UNIQUE (condominium_id, identifier)
);

CREATE INDEX idx_units_condominium_id ON units (condominium_id);
CREATE INDEX idx_units_owner_id ON units (owner_id);

-- Unit Ledger Accounts Table (Read Model for balances)
CREATE TABLE unit_ledger_accounts (
    unit_id UUID PRIMARY KEY,
    balance_amount_cents BIGINT NOT NULL,
    balance_currency_code VARCHAR(3) NOT NULL,
    last_updated_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_unit
        FOREIGN KEY(unit_id)
        REFERENCES units(id)
        ON DELETE CASCADE
);

-- Expense Categories Table (Self-defined based on need for financial_schema.sql)
CREATE TABLE expense_categories (
    id UUID PRIMARY KEY,
    condominium_id UUID NOT NULL,
    name VARCHAR(255) NOT NULL,
    created_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_condominium_exp_cat
        FOREIGN KEY(condominium_id)
        REFERENCES condominiums(id)
        ON DELETE CASCADE,
    UNIQUE (condominium_id, name)
);

-- Fee Items Table (Self-defined based on need for financial_schema.sql)
CREATE TABLE fee_items (
    id UUID PRIMARY KEY,
    condominium_id UUID NOT NULL,
    description TEXT NOT NULL,
    default_amount_cents BIGINT NOT NULL,
    default_currency_code VARCHAR(3) NOT NULL,
    created_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_condominium_fee_item
        FOREIGN KEY(condominium_id)
        REFERENCES condominiums(id)
        ON DELETE CASCADE
);
SQL;
        $this->execute($schemaSql);

        // 3. DDL from financial_schema.sql
        $financialSchemaSql = <<<SQL
-- Expenses Table (Read Model for condominium expenses)
CREATE TABLE expenses (
    id UUID PRIMARY KEY, -- Corresponds to ExpenseId
    condominium_id UUID NOT NULL,
    expense_category_id UUID NOT NULL,
    description TEXT NOT NULL,
    amount_cents BIGINT NOT NULL,
    currency_code VARCHAR(3) NOT NULL,
    expense_date DATE NOT NULL, -- The date the expense was incurred
    recorded_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP, -- When it was entered into the system
    CONSTRAINT fk_condominium
        FOREIGN KEY(condominium_id)
        REFERENCES condominiums(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_expense_category
        FOREIGN KEY(expense_category_id)
        REFERENCES expense_categories(id)
        ON DELETE RESTRICT
);

CREATE INDEX idx_expenses_condominium_id ON expenses (condominium_id);
CREATE INDEX idx_expenses_expense_category_id ON expenses (expense_category_id);
CREATE INDEX idx_expenses_expense_date ON expenses (expense_date);

-- Fees Issued Table (Read Model for fees applied to units)
CREATE TABLE fees_issued (
    id UUID PRIMARY KEY,
    unit_id UUID NOT NULL,
    fee_item_id UUID NOT NULL,
    description TEXT,
    amount_cents BIGINT NOT NULL,
    currency_code VARCHAR(3) NOT NULL,
    due_date DATE,
    issued_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
    status VARCHAR(50) NOT NULL DEFAULT 'PENDING',
    CONSTRAINT fk_unit_fee
        FOREIGN KEY(unit_id)
        REFERENCES units(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_fee_item
        FOREIGN KEY(fee_item_id)
        REFERENCES fee_items(id)
        ON DELETE RESTRICT
);

CREATE INDEX idx_fees_issued_unit_id ON fees_issued (unit_id);
CREATE INDEX idx_fees_issued_fee_item_id ON fees_issued (fee_item_id);
CREATE INDEX idx_fees_issued_status ON fees_issued (status);
CREATE INDEX idx_fees_issued_due_date ON fees_issued (due_date);

-- Payments Received Table (Read Model for payments from units)
CREATE TABLE payments_received (
    id UUID PRIMARY KEY,
    unit_id UUID NOT NULL,
    amount_cents BIGINT NOT NULL,
    currency_code VARCHAR(3) NOT NULL,
    payment_date DATE NOT NULL,
    payment_method VARCHAR(100),
    received_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_unit_payment
        FOREIGN KEY(unit_id)
        REFERENCES units(id)
        ON DELETE CASCADE
);

CREATE INDEX idx_payments_received_unit_id ON payments_received (unit_id);
CREATE INDEX idx_payments_received_payment_date ON payments_received (payment_date);
SQL;
        $this->execute($financialSchemaSql);
    }

    public function down(): void
    {
        // Drop tables in reverse order of creation to handle foreign key constraints
        $this->execute("DROP TABLE IF EXISTS payments_received CASCADE");
        $this->execute("DROP TABLE IF EXISTS fees_issued CASCADE");
        $this->execute("DROP TABLE IF EXISTS expenses CASCADE");
        $this->execute("DROP TABLE IF EXISTS fee_items CASCADE");
        $this->execute("DROP TABLE IF EXISTS expense_categories CASCADE");
        $this->execute("DROP TABLE IF EXISTS unit_ledger_accounts CASCADE");
        $this->execute("DROP TABLE IF EXISTS units CASCADE");
        $this->execute("DROP TABLE IF EXISTS owners CASCADE");
        $this->execute("DROP TABLE IF EXISTS condominiums CASCADE");
        $this->execute("DROP TABLE IF EXISTS events CASCADE"); // Or $this->table('events')->drop()->save();
    }
}
