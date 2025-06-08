-- Events Table (for Event Sourcing)
CREATE TABLE events (
    event_id UUID PRIMARY KEY,
    aggregate_id UUID NOT NULL,
    aggregate_type VARCHAR(255) NOT NULL,
    event_type VARCHAR(255) NOT NULL,
    payload JSONB NOT NULL,
    version INTEGER NOT NULL,
    occurred_on TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT aggregate_version_unique UNIQUE (aggregate_id, version)
);

CREATE INDEX idx_events_aggregate_id ON events (aggregate_id);
CREATE INDEX idx_events_event_type ON events (event_type);

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

-- Note: Timestamps for created_at/updated_at in read models
-- are often managed by the application or an ORM.
-- Alternatively, triggers could be used for `updated_at`.
-- For simplicity, default CURRENT_TIMESTAMP is used for both here,
-- but `updated_at` would typically be updated manually or by a trigger on row update.

-- Example of a trigger function for updated_at:
-- CREATE OR REPLACE FUNCTION trigger_set_timestamp()
-- RETURNS TRIGGER AS $$
-- BEGIN
--   NEW.updated_at = NOW();
--   RETURN NEW;
-- END;
-- $$ LANGUAGE plpgsql;

-- Example of applying the trigger (do this for condominiums, owners, units):
-- CREATE TRIGGER set_timestamp_condominiums
-- BEFORE UPDATE ON condominiums
-- FOR EACH ROW
-- EXECUTE FUNCTION trigger_set_timestamp();
