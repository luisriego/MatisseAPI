<?php

declare(strict_types=1);

namespace App\Bus\Expense;

use App\Entity\Expense;
use App\Event\Expense\ExpenseWasCreated;
use App\Repository\AccountRepository;
use App\Repository\ExpenseRepository;
use App\Repository\ExpenseTypeRepository;
use Symfony\Component\Messenger\Exception\ExceptionInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Uid\Uuid;

readonly class CreateExpenseCommandHandler
{
    public function __construct(
        private ExpenseRepository $expenseRepository,
        private ExpenseTypeRepository $expenseTypeRepository,
        private AccountRepository $accountRepository,
        private MessageBusInterface $bus
    ) {}

    /**
     * @throws ExceptionInterface
     * @throws \Exception
     */
    public function __invoke(CreateExpenseCommand $command): void
    {
        $expenseType = $this->expenseTypeRepository->findOneByIdOrFail($command->expenseTypeId);
        $account = $this->accountRepository->findOneByIdOrFail($command->paidFromAccountId);


        $expense = Expense::create(
            Uuid::v4()->toRfc4122(),
            $command->amount,
            $command->isRecurring,
            $command->payOnMonths,
            $expenseType,
            $account,
            new \DateTime($command->date)
        );

        $expense->addDescription($command->description);

        $this->expenseRepository->save($expense, true);

        $event = new ExpenseWasCreated(
            $expense->id(),
            $expense->amount(),
            $expense->description(),
            $expense->createdAt()::ATOM,
            (string) $expense->type()->id(),
            $expense->account()->id(),
            Uuid::v4()->toRfc4122(),
            (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM)
        );

        $this->bus->dispatch($event);
    }
}
