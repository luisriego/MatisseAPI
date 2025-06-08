<?php

declare(strict_types=1);

namespace App\Adapter\Framework\Http\Controller;

use App\Application\Command\CreateUnitCommand;
use App\Application\Command\CreateUnitCommandHandler;
use App\Application\Command\RegisterCondominiumCommand;
use App\Application\Command\RegisterCondominiumCommandHandler;
use App\Application\Query\GetCondominiumDetailsQuery;
use App\Application\Query\GetCondominiumDetailsQueryHandler;
use App\Application\Command\RecordCondominiumExpenseCommand; // Added
use App\Application\Command\RecordCondominiumExpenseCommandHandler; // Added
use App\Application\Query\GetCondominiumFinancialSummaryQuery; // Added
use App\Application\Query\GetCondominiumFinancialSummaryQueryHandler; // Added
use App\Application\Query\GetUnitsInCondominiumQuery;
use App\Application\Query\GetUnitsInCondominiumQueryHandler;
use App\Domain\ValueObject\CondominiumId;
use App\Domain\ValueObject\ExpenseCategoryId; // Added
use Psr\Log\LoggerInterface;
use InvalidArgumentException;
use DomainException; // For business logic errors (e.g., not found)
use Exception; // Catch-all

// Simulating HTTP responses with [data, statusCode] tuples
final class CondominiumController
{
    private RegisterCondominiumCommandHandler $registerCondominiumCommandHandler;
    private CreateUnitCommandHandler $createUnitCommandHandler;
    private GetCondominiumDetailsQueryHandler $getCondominiumDetailsQueryHandler;
    private GetUnitsInCondominiumQueryHandler $getUnitsInCondominiumQueryHandler;
    private RecordCondominiumExpenseCommandHandler $recordCondominiumExpenseCommandHandler; // Added
    private GetCondominiumFinancialSummaryQueryHandler $getCondominiumFinancialSummaryQueryHandler; // Added
    private LoggerInterface $logger;

    public function __construct(
        RegisterCondominiumCommandHandler $registerCondominiumCommandHandler,
        CreateUnitCommandHandler $createUnitCommandHandler,
        GetCondominiumDetailsQueryHandler $getCondominiumDetailsQueryHandler,
        GetUnitsInCondominiumQueryHandler $getUnitsInCondominiumQueryHandler,
        RecordCondominiumExpenseCommandHandler $recordCondominiumExpenseCommandHandler, // Added
        GetCondominiumFinancialSummaryQueryHandler $getCondominiumFinancialSummaryQueryHandler, // Added
        LoggerInterface $logger
    ) {
        $this->registerCondominiumCommandHandler = $registerCondominiumCommandHandler;
        $this->createUnitCommandHandler = $createUnitCommandHandler;
        $this->getCondominiumDetailsQueryHandler = $getCondominiumDetailsQueryHandler;
        $this->getUnitsInCondominiumQueryHandler = $getUnitsInCondominiumQueryHandler;
        $this->recordCondominiumExpenseCommandHandler = $recordCondominiumExpenseCommandHandler; // Added
        $this->getCondominiumFinancialSummaryQueryHandler = $getCondominiumFinancialSummaryQueryHandler; // Added
        $this->logger = $logger;
    }

    // POST /condominiums
    public function registerCondominium(
        string $name,
        string $addressStreet,
        string $addressCity,
        string $addressPostalCode,
        string $addressCountry
    ): array {
        try {
            $command = new RegisterCondominiumCommand(
                $name,
                $addressStreet,
                $addressCity,
                $addressPostalCode,
                $addressCountry
            );
            $newCondominiumId = $this->registerCondominiumCommandHandler->handle($command);

            return [['condominiumId' => $newCondominiumId->toString(), 'message' => 'Condominium registered'], 201];
        } catch (InvalidArgumentException $e) {
            $this->logger->warning('Failed to register condominium due to invalid argument: ' . $e->getMessage(), ['exception' => $e]);
            return [['error' => $e->getMessage()], 400];
        } catch (Exception $e) {
            $this->logger->error("Error registering condominium: " . $e->getMessage(), ['exception_class' => get_class($e), 'trace' => $e->getTraceAsString()]);
            return [['error' => 'An unexpected server error occurred.'], 500];
        }
    }

    // GET /condominiums/{condominiumId}
    public function getCondominiumDetails(string $condominiumId): array
    {
        try {
            // Basic validation for UUID format before creating VO or running query
            if (!\Ramsey\Uuid\Uuid::isValid($condominiumId)) {
                 throw new InvalidArgumentException("Invalid condominium ID format.");
            }
            $query = new GetCondominiumDetailsQuery($condominiumId);
            $dto = $this->getCondominiumDetailsQueryHandler->handle($query);
            return [$dto, 200];
        } catch (InvalidArgumentException $e) {
            $this->logger->warning('Invalid input for getCondominiumDetails: ' . $e->getMessage(), ['condominiumId' => $condominiumId]);
            return [['error' => $e->getMessage()], 400];
        } catch (DomainException $e) { // Catches "Not Found" from handler
            $this->logger->notice('Condominium not found: ' . $e->getMessage(), ['condominiumId' => $condominiumId]);
            return [['error' => $e->getMessage()], 404];
        } catch (Exception $e) {
            $this->logger->error("Error fetching condominium details for ID {$condominiumId}: " . $e->getMessage(), ['exception_class' => get_class($e), 'trace' => $e->getTraceAsString()]);
            return [['error' => 'An unexpected server error occurred.'], 500];
        }
    }

    // POST /condominiums/{condominiumId}/units
    public function createUnit(string $rawCondominiumId, string $unitIdentifier): array
    {
        try {
            if (!\Ramsey\Uuid\Uuid::isValid($rawCondominiumId)) {
                 throw new InvalidArgumentException("Invalid condominium ID format for unit creation.");
            }
            $condominiumIdVO = new CondominiumId($rawCondominiumId);
            $command = new CreateUnitCommand($condominiumIdVO, $unitIdentifier);
            $newUnitId = $this->createUnitCommandHandler->handle($command);

            return [['unitId' => $newUnitId->toString(), 'message' => 'Unit created'], 201];
        } catch (InvalidArgumentException $e) { // Catches invalid UUID or other command validation
            $this->logger->warning('Failed to create unit due to invalid argument: ' . $e->getMessage(), ['condominiumId' => $rawCondominiumId, 'identifier' => $unitIdentifier]);
            return [['error' => $e->getMessage()], 400];
        }
        // Add catch for CondominiumNotFoundException if CreateUnitCommandHandler implements it
        catch (DomainException $e) { // Could be CondominiumNotFound if handler checks
             $this->logger->notice('Domain error creating unit: ' . $e->getMessage(), ['condominiumId' => $rawCondominiumId, 'identifier' => $unitIdentifier]);
             return [['error' => $e->getMessage()], (str_contains(strtolower($e->getMessage()), 'not found') ? 404 : 400) ];
        }
        catch (Exception $e) {
            $this->logger->error("Error creating unit for condo ID {$rawCondominiumId}: " . $e->getMessage(), ['exception_class' => get_class($e), 'trace' => $e->getTraceAsString()]);
            return [['error' => 'An unexpected server error occurred.'], 500];
        }
    }

    // GET /condominiums/{condominiumId}/units
    public function getUnitsInCondominium(string $condominiumId): array
    {
        try {
            if (!\Ramsey\Uuid\Uuid::isValid($condominiumId)) {
                 throw new InvalidArgumentException("Invalid condominium ID format for fetching units.");
            }
            $query = new GetUnitsInCondominiumQuery($condominiumId);
            $dtos = $this->getUnitsInCondominiumQueryHandler->handle($query);
            return [$dtos, 200];
        } catch (InvalidArgumentException $e) {
            $this->logger->warning('Invalid input for getUnitsInCondominium: ' . $e->getMessage(), ['condominiumId' => $condominiumId]);
            return [['error' => $e->getMessage()], 400];
        }
        // Potentially catch a CondominiumNotFoundException if the query handler was designed to throw it
        // when the condominium itself doesn't exist, though often it just returns an empty array.
        catch (Exception $e) {
            $this->logger->error("Error fetching units for condominium ID {$condominiumId}: " . $e->getMessage(), ['exception_class' => get_class($e), 'trace' => $e->getTraceAsString()]);
            return [['error' => 'An unexpected server error occurred.'], 500];
        }
    }

    // POST /condominiums/{condominiumId}/expenses
    public function recordExpense(
        string $rawCondominiumId,
        string $rawExpenseCategoryId,
        string $description,
        int $amountCents,
        string $currencyCode,
        string $expenseDate // YYYY-MM-DD
    ): array {
        try {
            if (!\Ramsey\Uuid\Uuid::isValid($rawCondominiumId)) {
                 throw new InvalidArgumentException("Invalid condominium ID format.");
            }
            if (!\Ramsey\Uuid\Uuid::isValid($rawExpenseCategoryId)) {
                 throw new InvalidArgumentException("Invalid expense category ID format.");
            }

            $condominiumIdVO = new CondominiumId($rawCondominiumId);
            $expenseCategoryIdVO = new ExpenseCategoryId($rawExpenseCategoryId);

            $command = new RecordCondominiumExpenseCommand(
                $condominiumIdVO,
                $expenseCategoryIdVO,
                $description,
                $amountCents,
                $currencyCode,
                $expenseDate
            );
            $newExpenseId = $this->recordCondominiumExpenseCommandHandler->handle($command);

            return [['expenseId' => $newExpenseId->toString(), 'message' => 'Expense recorded'], 201];
        } catch (InvalidArgumentException $e) {
            $this->logger->warning('Failed to record expense due to invalid argument: ' . $e->getMessage(), ['exception' => $e]);
            return [['error' => $e->getMessage()], 400];
        } catch (DomainException $e) { // Catches "Not Found" for condo or category, or other domain rules
            $this->logger->notice('Domain error recording expense: ' . $e->getMessage(), ['condominiumId' => $rawCondominiumId, 'expenseCategoryId' => $rawExpenseCategoryId]);
            return [['error' => $e->getMessage()], (str_contains(strtolower($e->getMessage()), 'not found') ? 404 : 400)];
        } catch (Exception $e) {
            $this->logger->error("Error recording expense for condo ID {$rawCondominiumId}: " . $e->getMessage(), ['exception_class' => get_class($e), 'trace' => $e->getTraceAsString()]);
            return [['error' => 'An unexpected server error occurred.'], 500];
        }
    }

    // GET /condominiums/{condominiumId}/financial-summary
    public function getFinancialSummary(string $condominiumId, ?string $periodStartDate = null, ?string $periodEndDate = null): array
    {
        try {
            if (!\Ramsey\Uuid\Uuid::isValid($condominiumId)) {
                 throw new InvalidArgumentException("Invalid condominium ID format.");
            }
            // Further validation for date formats if they are provided
            if ($periodStartDate && !\DateTimeImmutable::createFromFormat('Y-m-d', $periodStartDate)) {
                throw new InvalidArgumentException("Invalid start date format. Please use YYYY-MM-DD.");
            }
            if ($periodEndDate && !\DateTimeImmutable::createFromFormat('Y-m-d', $periodEndDate)) {
                throw new InvalidArgumentException("Invalid end date format. Please use YYYY-MM-DD.");
            }

            $query = new GetCondominiumFinancialSummaryQuery($condominiumId, $periodStartDate, $periodEndDate);
            $dto = $this->getCondominiumFinancialSummaryQueryHandler->handle($query);
            return [$dto, 200];
        } catch (InvalidArgumentException $e) {
            $this->logger->warning('Invalid input for getFinancialSummary: ' . $e->getMessage(), ['condominiumId' => $condominiumId]);
            return [['error' => $e->getMessage()], 400];
        } catch (DomainException $e) { // E.g. Condominium not found if handler checks that
            $this->logger->notice('Domain error fetching financial summary: ' . $e->getMessage(), ['condominiumId' => $condominiumId]);
            return [['error' => $e->getMessage()], (str_contains(strtolower($e->getMessage()), 'not found') ? 404 : 400)];
        } catch (Exception $e) {
            $this->logger->error("Error fetching financial summary for condo ID {$condominiumId}: " . $e->getMessage(), ['exception_class' => get_class($e), 'trace' => $e->getTraceAsString()]);
            return [['error' => 'An unexpected server error occurred.'], 500];
        }
    }
}
