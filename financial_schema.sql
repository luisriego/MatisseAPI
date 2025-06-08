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
        ON DELETE CASCADE, -- If condo is deleted, its expenses are gone
    CONSTRAINT fk_expense_category
        FOREIGN KEY(expense_category_id)
        REFERENCES expense_categories(id) -- Assuming an expense_categories table exists
        ON DELETE RESTRICT -- Prevent deleting a category if expenses are logged against it
);

CREATE INDEX idx_expenses_condominium_id ON expenses (condominium_id);
CREATE INDEX idx_expenses_expense_category_id ON expenses (expense_category_id);
CREATE INDEX idx_expenses_expense_date ON expenses (expense_date);

-- Fees Issued Table (Read Model for fees applied to units)
CREATE TABLE fees_issued (
    id UUID PRIMARY KEY, -- A unique ID for this fee instance, can be event_id from FeeAppliedToUnitLedgerEvent
    unit_id UUID NOT NULL,
    fee_item_id UUID NOT NULL, -- References a FeeItem definition
    description TEXT, -- Copied from FeeItem or overridden by command
    amount_cents BIGINT NOT NULL,
    currency_code VARCHAR(3) NOT NULL,
    due_date DATE, -- The due date for this fee
    issued_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP, -- When the fee was applied
    status VARCHAR(50) NOT NULL DEFAULT 'PENDING', -- e.g., PENDING, PAID, OVERDUE
    CONSTRAINT fk_unit
        FOREIGN KEY(unit_id)
        REFERENCES units(id)
        ON DELETE CASCADE, -- If unit is deleted, its fees are gone
    CONSTRAINT fk_fee_item
        FOREIGN KEY(fee_item_id)
        REFERENCES fee_items(id) -- Assuming a fee_items table exists for FeeItem definitions
        ON DELETE RESTRICT -- Prevent deleting a fee item if fees are issued against it
);

CREATE INDEX idx_fees_issued_unit_id ON fees_issued (unit_id);
CREATE INDEX idx_fees_issued_fee_item_id ON fees_issued (fee_item_id);
CREATE INDEX idx_fees_issued_status ON fees_issued (status);
CREATE INDEX idx_fees_issued_due_date ON fees_issued (due_date);

-- Payments Received Table (Read Model for payments from units)
CREATE TABLE payments_received (
    id UUID PRIMARY KEY, -- Corresponds to paymentId (TransactionId from the event)
    unit_id UUID NOT NULL,
    amount_cents BIGINT NOT NULL,
    currency_code VARCHAR(3) NOT NULL,
    payment_date DATE NOT NULL, -- The date the payment was made
    payment_method VARCHAR(100),
    received_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP, -- When it was recorded
    -- Potentially a foreign key to link payment to specific fees, if applicable (many-to-many or allocation logic)
    -- For now, keeping it simple as a record of payment towards unit's balance.
    CONSTRAINT fk_unit
        FOREIGN KEY(unit_id)
        REFERENCES units(id)
        ON DELETE CASCADE -- If unit is deleted, its payment records are gone
);

CREATE INDEX idx_payments_received_unit_id ON payments_received (unit_id);
CREATE INDEX idx_payments_received_payment_date ON payments_received (payment_date);

-- Note: `expense_categories` and `fee_items` tables are assumed to exist
-- based on ExpenseCategoryId and FeeItemId. Their DDL would be:

-- CREATE TABLE expense_categories (
--     id UUID PRIMARY KEY,
--     condominium_id UUID NOT NULL,
--     name VARCHAR(255) NOT NULL,
--     created_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
--     updated_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
--     CONSTRAINT fk_condominium
--         FOREIGN KEY(condominium_id)
--         REFERENCES condominiums(id)
--         ON DELETE CASCADE,
--     UNIQUE (condominium_id, name)
-- );

-- CREATE TABLE fee_items (
--     id UUID PRIMARY KEY,
--     condominium_id UUID NOT NULL,
--     description TEXT NOT NULL,
--     default_amount_cents BIGINT NOT NULL,
--     default_currency_code VARCHAR(3) NOT NULL,
--     created_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
--     updated_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
--     CONSTRAINT fk_condominium
--         FOREIGN KEY(condominium_id)
--         REFERENCES condominiums(id)
--         ON DELETE CASCADE
-- );
