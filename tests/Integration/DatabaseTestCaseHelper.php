<?php

declare(strict_types=1);

namespace Tests\Integration;

use PDO;
use PDOException;

trait DatabaseTestCaseHelper
{
    protected static ?PDO $pdo = null;
    protected static string $dbName = 'test_condo_management'; // Default, can be overridden by env

    public static function getPDO(): PDO
    {
        if (self::$pdo === null) {
            $host = getenv('TEST_DB_HOST') ?: '127.0.0.1';
            $port = getenv('TEST_DB_PORT') ?: '5432';
            $user = getenv('TEST_DB_USER') ?: 'testuser';
            $pass = getenv('TEST_DB_PASSWORD') ?: 'testpass';
            self::$dbName = getenv('TEST_DB_NAME') ?: 'test_condo_management';

            $dsn = "pgsql:host={$host};port={$port};dbname=" . self::$dbName;

            try {
                self::$pdo = new PDO($dsn, $user, $pass, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ]);
            } catch (PDOException $e) {
                // Attempt to connect without specifying dbname to create it
                $dsnSys = "pgsql:host={$host};port={$port}";
                $pdoSys = new PDO($dsnSys, $user, $pass);
                $pdoSys->exec("CREATE DATABASE \"" . self::$dbName . "\"");
                self::$pdo = new PDO($dsn, $user, $pass, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ]);
                // If schema migration is needed here, it would be done now.
                // For this exercise, schema is applied externally or by first test run.
            }
        }
        return self::$pdo;
    }

    protected function startTransaction(): void
    {
        self::getPDO()->beginTransaction();
    }

    protected function rollback(): void
    {
        if (self::getPDO()->inTransaction()) {
            self::getPDO()->rollBack();
        }
    }

    protected static function applySchema(): void
    {
        $pdo = self::getPDO();
        $schemaPath = __DIR__ . '/../../schema.sql'; // Assuming schema.sql is in root
        $financialSchemaPath = __DIR__ . '/../../financial_schema.sql';

        if (file_exists($schemaPath)) {
            $sql = file_get_contents($schemaPath);
            $pdo->exec($sql);
        } else {
            throw new \RuntimeException("schema.sql not found at {$schemaPath}");
        }

        if (file_exists($financialSchemaPath)) {
            $sql = file_get_contents($financialSchemaPath);
            $pdo->exec($sql);
        } else {
            throw new \RuntimeException("financial_schema.sql not found at {$financialSchemaPath}");
        }
    }

    protected static function truncateTables(array $tables): void
    {
        $pdo = self::getPDO();
        foreach ($tables as $table) {
            // Temporarily disable foreign key checks if needed, or use CASCADE
            // For PostgreSQL, TRUNCATE ... CASCADE handles this.
            $pdo->exec("TRUNCATE TABLE \"{$table}\" RESTART IDENTITY CASCADE");
        }
    }

    public static function setUpBeforeClassNeedsDb(): void
    {
        // This method could be called in setUpBeforeClass of test classes
        // to ensure DB and schema are ready.
        // For the first run, it might create DB and schema.
        try {
            self::getPDO(); // Ensure connection
             // Check if tables exist, if not, apply schema
            $stmt = self::getPDO()->query("SELECT 1 FROM events LIMIT 1");
        } catch (PDOException $e) {
            // Table not found (or other DB error), likely schema needs to be applied
            if (str_contains($e->getMessage(), 'Undefined table') || str_contains($e->getMessage(), 'does not exist')) {
                 self::applySchema();
            } else {
                // If it's a different error (e.g. connection failed even after create attempt)
                throw $e;
            }
        }
    }
}
