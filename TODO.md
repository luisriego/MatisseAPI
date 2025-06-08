# Project TODO List & Future Enhancements

## Core Functionality & Domain
- [ ] **Enhance `FeeItem` and `ExpenseCategory` to be fully event-sourced for updates**:
    - Define `FeeItemDescriptionChangedEvent`, `FeeItemDefaultAmountChangedEvent`.
    - Define `ExpenseCategoryNameChangedEvent`.
    - Implement event recording in their respective `change...` methods.
    - Update their `apply()` methods to handle these new events.
    - Update relevant projectors if these changes need to be reflected in read models.
- [ ] **Review and refine `Ledger` and `UnitLedgerAccount` balance logic**: Ensure currency consistency and handle potential edge cases in financial calculations. The `Ledger` debit/credit methods currently use a placeholder description; this should be passed from the command or derived.
- [ ] **Transaction Management for Projections**: `UnitLedgerAccountProjector` currently wraps projections in a transaction. Review if other projectors need this or if a more global UoW for projections after event persistence is better.
- [ ] **Optimistic Concurrency Control**: Implement version checking in `PostgresEventStore::append()` by having aggregates track their version and passing an `expectedVersion` to the `append` method.
- [ ] **Snapshotting for Aggregates**: For aggregates with long event streams, implement snapshotting to improve reconstitution performance.

## API & Infrastructure
- [ ] **Implement Asynchronous Projections**: For read models that can tolerate slight delays or for performance-critical write paths, move projection updates to an asynchronous process (e.g., using a message queue).
- [ ] **Integrate Comprehensive Routing Library**: Replace basic `public/index.php` router with a robust library (e.g., Symfony Routing, FastRoute).
- [ ] **Robust Input Validation**: Implement a dedicated validation layer for API requests (e.g., using `symfony/validator` or similar) instead of manual checks in controllers.
- [ ] **Production-Grade Logger**: Configure and use a production-grade logger (e.g., Monolog) with appropriate channels, formatters, and handlers (e.g., logging to files, external services). Inject logger into more services like Command Handlers and Event Store.
- [ ] **User Authentication and Authorization**: Secure API endpoints with proper authentication (e.g., JWT, OAuth2) and authorization mechanisms.
- [ ] **API Versioning**: Plan for API versioning if future breaking changes are anticipated.
- [ ] **.env File Loading**: Replace simple `$_ENV` loading in `public/index.php` and `phinx.php` with a proper library like `vlucas/phpdotenv`.
- [ ] **Database Management**:
    *   Ensure PostgreSQL server is available and configured correctly for development, testing, and production environments.
    *   Resolve "Connection refused" errors in the test environment.
    *   Consider separate read-only replicas for query endpoints if scaling becomes an issue.

## Testing
- [ ] **Complete Integration Testing Suite**:
    *   Ensure a stable PostgreSQL environment is available for CI/testing pipelines.
    *   Write `PostgresEventStoreTest.php` with full coverage.
    *   Expand controller tests to cover all error handling paths and edge cases.
    *   Add integration tests for projectors that ensure foreign key constraints are handled (e.g., by pre-inserting related entities).
- [ ] **Refactor Unit Tests**: More broadly use Object Mother or Test Data Builder patterns for creating test fixtures, improving clarity and maintainability.
- [ ] **End-to-End API Tests**: Implement tests that interact with the API endpoints using an HTTP client (e.g., Guzzle, Symfony HTTP Client) to verify full request-response cycles.

## Documentation
- [ ] **API Documentation**: Generate or write comprehensive API documentation (e.g., using OpenAPI/Swagger).
- [ ] **Architectural Decisions**: Document key architectural decisions and patterns used.

## Phinx Migrations
- [ ] **Review Initial Migration**: The initial migration file uses a mix of Phinx table builder (for `events`) and raw SQL. For consistency and better database independence, consider converting all table creations to use the Phinx table builder syntax over time.
- [ ] **Idempotency**: Ensure SQL in migrations is idempotent or correctly handled in `down()` methods for rollbacks. Using `IF EXISTS` or Phinx's `hasTable`/`dropTable` is good. `DROP TABLE ... CASCADE` in `down()` is effective.
