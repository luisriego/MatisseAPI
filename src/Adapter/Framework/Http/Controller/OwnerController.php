<?php

declare(strict_types=1);

namespace App\Adapter\Framework\Http\Controller;

use App\Application\Command\CreateOwnerCommand;
use App\Application\Command\CreateOwnerCommandHandler;
use Psr\Log\LoggerInterface;
use InvalidArgumentException;
use Exception;

final class OwnerController
{
    private CreateOwnerCommandHandler $createOwnerCommandHandler;
    private LoggerInterface $logger;

    public function __construct(
        CreateOwnerCommandHandler $createOwnerCommandHandler,
        LoggerInterface $logger
    ) {
        $this->createOwnerCommandHandler = $createOwnerCommandHandler;
        $this->logger = $logger;
    }

    // POST /owners
    public function createOwner(string $name, string $email, string $phoneNumber): array
    {
        try {
            // Basic validation (e.g., non-empty name, valid email format) can be done here
            // or by a dedicated request validation layer before controller action.
            if (empty($name)) {
                throw new InvalidArgumentException("Owner name cannot be empty.");
            }
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new InvalidArgumentException("Invalid email format provided for owner.");
            }

            $command = new CreateOwnerCommand($name, $email, $phoneNumber);
            $newOwnerId = $this->createOwnerCommandHandler->handle($command);

            return [['ownerId' => $newOwnerId->toString(), 'message' => 'Owner created'], 201];
        } catch (InvalidArgumentException $e) {
            $this->logger->warning('Failed to create owner due to invalid argument: ' . $e->getMessage(), ['exception' => $e]);
            return [['error' => $e->getMessage()], 400];
        }
        // Catch specific domain exceptions if CreateOwnerCommandHandler can throw them (e.g., EmailAlreadyExistsException)
        // catch (EmailAlreadyExistsException $e) {
        //     $this->logger->notice('Attempt to create owner with existing email: ' . $email, ['exception' => $e]);
        //     return [['error' => $e->getMessage()], 409]; // Conflict
        // }
        catch (DomainException $e) { // If any domain specific exceptions were to be thrown by handler
            $this->logger->notice('Domain error creating owner: ' . $e->getMessage(), ['name' => $name, 'email' => $email]);
            return [['error' => $e->getMessage()], 400]; // Or appropriate code
        }
        catch (Exception $e) {
            $this->logger->error("Error creating owner: " . $e->getMessage(), ['exception_class' => get_class($e), 'trace' => $e->getTraceAsString()]);
            return [['error' => 'An unexpected server error occurred.'], 500];
        }
    }
}
