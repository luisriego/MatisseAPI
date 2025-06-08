<?php

declare(strict_types=1);

namespace App\Adapter\Framework\Http\Controller;

use App\Application\Command\AssignOwnerToUnitCommand;
use App\Application\Command\AssignOwnerToUnitCommandHandler;
use App\Application\Command\IssueFeeToUnitCommand;         // Added
use App\Application\Command\IssueFeeToUnitCommandHandler;  // Added
use App\Application\Command\ReceivePaymentFromUnitCommand; // Added
use App\Application\Command\ReceivePaymentFromUnitCommandHandler; // Added
use App\Application\Query\GetUnitStatementQuery;           // Added
use App\Application\Query\GetUnitStatementQueryHandler;    // Added
use App\Domain\ValueObject\FeeItemId; // Added
use App\Domain\ValueObject\OwnerId;
use App\Domain\ValueObject\UnitId;
use Psr\Log\LoggerInterface;
use InvalidArgumentException;
use DomainException; // For UnitNotFound, OwnerNotFound etc.
use Exception;

final class UnitController
{
    private AssignOwnerToUnitCommandHandler $assignOwnerToUnitCommandHandler;
    private IssueFeeToUnitCommandHandler $issueFeeToUnitCommandHandler; // Added
    private ReceivePaymentFromUnitCommandHandler $receivePaymentFromUnitCommandHandler; // Added
    private GetUnitStatementQueryHandler $getUnitStatementQueryHandler; // Added
    private LoggerInterface $logger;

    public function __construct(
        AssignOwnerToUnitCommandHandler $assignOwnerToUnitCommandHandler,
        IssueFeeToUnitCommandHandler $issueFeeToUnitCommandHandler, // Added
        ReceivePaymentFromUnitCommandHandler $receivePaymentFromUnitCommandHandler, // Added
        GetUnitStatementQueryHandler $getUnitStatementQueryHandler, // Added
        LoggerInterface $logger
    ) {
        $this->assignOwnerToUnitCommandHandler = $assignOwnerToUnitCommandHandler;
        $this->issueFeeToUnitCommandHandler = $issueFeeToUnitCommandHandler; // Added
        $this->receivePaymentFromUnitCommandHandler = $receivePaymentFromUnitCommandHandler; // Added
        $this->getUnitStatementQueryHandler = $getUnitStatementQueryHandler; // Added
        $this->logger = $logger;
    }

    // PUT /units/{unitId}/owner
    public function assignOwnerToUnit(string $rawUnitId, string $rawOwnerId): array
    {
        try {
            // Basic validation for UUID formats
            if (!\Ramsey\Uuid\Uuid::isValid($rawUnitId)) {
                 throw new InvalidArgumentException("Invalid Unit ID format.");
            }
            if (!\Ramsey\Uuid\Uuid::isValid($rawOwnerId)) {
                 throw new InvalidArgumentException("Invalid Owner ID format.");
            }

            $unitIdVO = new UnitId($rawUnitId);
            $ownerIdVO = new OwnerId($rawOwnerId);

            $command = new AssignOwnerToUnitCommand($unitIdVO, $ownerIdVO);
            $this->assignOwnerToUnitCommandHandler->handle($command);

            return [['message' => 'Owner assigned to unit'], 200];
        } catch (InvalidArgumentException $e) {
            $this->logger->warning('Failed to assign owner to unit due to invalid argument: ' . $e->getMessage(), ['exception' => $e]);
            return [['error' => $e->getMessage()], 400];
        } catch (DomainException $e) { // Catches UnitNotFound or OwnerNotFound from handler
            $this->logger->notice(
                'Domain error assigning owner to unit: ' . $e->getMessage(),
                ['unitId' => $rawUnitId, 'ownerId' => $rawOwnerId]
            );
            return [['error' => $e->getMessage()], (str_contains(strtolower($e->getMessage()), 'not found') ? 404 : 400)];
        } catch (Exception $e) {
            $this->logger->error(
                "Error assigning owner to unit {$rawUnitId}: " . $e->getMessage(),
                ['ownerId' => $rawOwnerId, 'exception_class' => get_class($e), 'trace' => $e->getTraceAsString()]
            );
            return [['error' => 'An unexpected server error occurred.'], 500];
        }
    }

    // POST /units/{unitId}/fees
    public function issueFee(
        string $rawUnitId,
        string $rawFeeItemId,
        int $amountCents,
        string $currencyCode,
        string $dueDate, // YYYY-MM-DD
        ?string $description = null
    ): array {
        try {
            if (!\Ramsey\Uuid\Uuid::isValid($rawUnitId)) {
                 throw new InvalidArgumentException("Invalid Unit ID format.");
            }
            if (!\Ramsey\Uuid\Uuid::isValid($rawFeeItemId)) {
                 throw new InvalidArgumentException("Invalid FeeItem ID format.");
            }
            $unitIdVO = new UnitId($rawUnitId);
            $feeItemIdVO = new FeeItemId($rawFeeItemId);

            $command = new IssueFeeToUnitCommand(
                $unitIdVO,
                $feeItemIdVO,
                $amountCents,
                $currencyCode,
                $dueDate,
                $description
            );
            $this->issueFeeToUnitCommandHandler->handle($command);
            // This command handler currently returns void. A success message is appropriate.
            return [['message' => 'Fee issued to unit successfully'], 201];
        } catch (InvalidArgumentException $e) {
            $this->logger->warning('Failed to issue fee to unit due to invalid argument: ' . $e->getMessage(), ['exception' => $e]);
            return [['error' => $e->getMessage()], 400];
        } catch (DomainException $e) { // Catches UnitNotFound, FeeItemNotFound, etc.
            $this->logger->notice('Domain error issuing fee: ' . $e->getMessage(), ['unitId' => $rawUnitId, 'feeItemId' => $rawFeeItemId]);
            return [['error' => $e->getMessage()], (str_contains(strtolower($e->getMessage()), 'not found') ? 404 : 400)];
        } catch (Exception $e) {
            $this->logger->error("Error issuing fee to unit {$rawUnitId}: " . $e->getMessage(), ['feeItemId' => $rawFeeItemId, 'exception_class' => get_class($e), 'trace' => $e->getTraceAsString()]);
            return [['error' => 'An unexpected server error occurred.'], 500];
        }
    }

    // POST /units/{unitId}/payments
    public function receivePayment(
        string $rawUnitId,
        int $amountCents,
        string $currencyCode,
        string $paymentDate, // YYYY-MM-DD
        string $paymentMethod
    ): array {
        try {
            if (!\Ramsey\Uuid\Uuid::isValid($rawUnitId)) {
                 throw new InvalidArgumentException("Invalid Unit ID format.");
            }
            $unitIdVO = new UnitId($rawUnitId);

            $command = new ReceivePaymentFromUnitCommand(
                $unitIdVO,
                $amountCents,
                $currencyCode,
                $paymentDate,
                $paymentMethod
            );
            $this->receivePaymentFromUnitCommandHandler->handle($command);
            return [['message' => 'Payment received successfully'], 201];
        } catch (InvalidArgumentException $e) {
            $this->logger->warning('Failed to receive payment due to invalid argument: ' . $e->getMessage(), ['exception' => $e]);
            return [['error' => $e->getMessage()], 400];
        } catch (DomainException $e) { // Catches UnitNotFound, UnitLedgerAccountNotFound
            $this->logger->notice('Domain error receiving payment: ' . $e->getMessage(), ['unitId' => $rawUnitId]);
            return [['error' => $e->getMessage()], (str_contains(strtolower($e->getMessage()), 'not found') ? 404 : 400)];
        } catch (Exception $e) {
            $this->logger->error("Error receiving payment for unit {$rawUnitId}: " . $e->getMessage(), ['exception_class' => get_class($e), 'trace' => $e->getTraceAsString()]);
            return [['error' => 'An unexpected server error occurred.'], 500];
        }
    }

    // GET /units/{unitId}/statement
    public function getStatement(string $rawUnitId): array
    {
        try {
            if (!\Ramsey\Uuid\Uuid::isValid($rawUnitId)) {
                 throw new InvalidArgumentException("Invalid Unit ID format.");
            }
            $query = new GetUnitStatementQuery($rawUnitId);
            $dto = $this->getUnitStatementQueryHandler->handle($query);
            return [$dto, 200];
        } catch (InvalidArgumentException $e) {
            $this->logger->warning('Invalid input for getUnitStatement: ' . $e->getMessage(), ['unitId' => $rawUnitId]);
            return [['error' => $e->getMessage()], 400];
        } catch (DomainException $e) { // Catches UnitLedgerAccountNotFound from handler
            $this->logger->notice('Domain error fetching unit statement: ' . $e->getMessage(), ['unitId' => $rawUnitId]);
            return [['error' => $e->getMessage()], (str_contains(strtolower($e->getMessage()), 'not found') ? 404 : 400)];
        } catch (Exception $e) {
            $this->logger->error("Error fetching unit statement for {$rawUnitId}: " . $e->getMessage(), ['exception_class' => get_class($e), 'trace' => $e->getTraceAsString()]);
            return [['error' => 'An unexpected server error occurred.'], 500];
        }
    }
}
