<?php

// src/Bus/RecurringExpense/CreateRecurringExpenseCommandHandler.php
declare(strict_types=1);

namespace App\Bus\RecurringExpense;

use App\Entity\RecurringExpense;
use App\Repository\AccountRepository;
use App\Repository\ExpenseTypeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\Exception\ExceptionInterface;
use Symfony\Component\Messenger\MessageBusInterface; // Opcional, para despachar eventos
use App\Event\RecurringExpense\RecurringExpenseWasCreated;
use Symfony\Component\Uid\Uuid; // Si tu RecurringExpense usa UUIDs para el ID

#[AsMessageHandler]
readonly class CreateRecurringExpenseCommandHandler
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ExpenseTypeRepository  $expenseTypeRepository,
        private AccountRepository $accountRepository,
        private MessageBusInterface    $bus,
    ) {
    }

    /**
     * @throws ExceptionInterface
     */
    public function __invoke(CreateRecurringExpenseCommand $command): RecurringExpense // Devolver la entidad puede ser útil
    {
        $expenseType = $this->expenseTypeRepository->findOneByIdOrFail($command->expenseTypeId);
        $account = $this->accountRepository->findOneByIdOrFail($command->accountId);

        $recurringExpense = RecurringExpense::create(
            id: Uuid::v4()->toRfc4122(),
            description: $command->description,
            amount: $command->amount,
            expenseType: $expenseType,
            account: $account,
            frequency: $command->frequency,
            dueDay: $command->dueDay,
            monthsOfYear: $command->monthsOfYear,
            startDate: $command->startDate,
            endDate: $command->endDate,
            occurrencesLeft: $command->occurrencesLeft,
            isActive: $command->isActive,
            notes: $command->notes
        );
dump($recurringExpense);
        $this->entityManager->persist($recurringExpense);
        $this->entityManager->flush();

        dump('Creando el evento');
        $event = new RecurringExpenseWasCreated(
            recurringExpenseId: $recurringExpense->id(),
            amount: $recurringExpense->amount(),
            description: $recurringExpense->description(),
            frequency: $recurringExpense->frequency(),
            startDate: $recurringExpense->startDate()->format('Y-m-d'),
            isActive: $recurringExpense->isActive()
        );
dump('Despachando el evento');
        $this->bus->dispatch($event);

        return $recurringExpense;
    }
}
