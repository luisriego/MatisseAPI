<?php

namespace App\Service\Slip;

use App\Entity\Expense;
use App\Entity\RecurringExpense;
use App\Event\Expense\ExpenseWasCreated;
use App\Repository\ExpenseRepository;
use App\Repository\RecurringExpenseRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Exception\ExceptionInterface as MessengerExceptionInterface;
use Symfony\Component\Uid\Uuid;
use DateTimeImmutable;
use DateTimeInterface;
use DateTime;
use IntlDateFormatter;

class RecurringExpenseHandlerService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly RecurringExpenseRepository $recurringExpenseRepository,
        private readonly ExpenseRepository $expenseRepository,
        private readonly MessageBusInterface $commandBus
    ) {
    }

    /**
     * @param array<int, array<string, mixed>> $submittedRecurringData
     * @throws MessengerExceptionInterface
     * @return array{generated_count: int, messages: array<int, array{type: string, text: string}>}
     */
    public function processSelections(array $submittedRecurringData, DateTimeInterface $targetMonthDate): array
    {
        $messages = [];
        $expensesWithoutAccounts = [];
        $missingAmounts = [];
        $generatedCount = 0;
        $invalidRecurringExpenses = [];

        foreach ($submittedRecurringData as $itemData) {
            $recurringExpenseId = $itemData['recurringExpenseId'] ?? null;
            if (!$recurringExpenseId) {
                $invalidRecurringExpenses[] = 'ID não fornecido';
                continue;
            }

            $recurringExpense = $this->recurringExpenseRepository->find($recurringExpenseId);
            if (!$recurringExpense) {
                $invalidRecurringExpenses[] = "ID {$recurringExpenseId}";
                continue;
            }

            $existingExpense = null;
            $allExpensesForThisRE = $this->expenseRepository->findBy(['recurringExpense' => $recurringExpense]);
            foreach ($allExpensesForThisRE as $expenseItem) {
                if ($expenseItem->dueDate()->format('Y-m') === $targetMonthDate->format('Y-m')) {
                    $existingExpense = $expenseItem;
                    break;
                }
            }

            $include = filter_var($itemData['include'] ?? false, FILTER_VALIDATE_BOOLEAN);
            $amount = isset($itemData['amount']) && $itemData['amount'] !== '' ? (int)$itemData['amount'] : null;

            /** @var ?DateTime $formDueDate */
            $formDueDate = $itemData['dueDate'] ?? null;
            if (is_string($formDueDate)) {
                try {
                    $formDueDate = (new DateTime($formDueDate))->setTime(12,0,0);
                } catch (\Exception $e) {
                    $invalidRecurringExpenses[] = "Data de vencimento inválida para '{$recurringExpense->description()}'";
                    continue;
                }
            }

            if ($include) {
                if ($amount === null) {
                    $missingAmounts[] = $recurringExpense->description();
                    if ($existingExpense) {
                        $this->entityManager->remove($existingExpense);
                    }
                    continue;
                }

                if ($formDueDate === null) {
                    $formDueDate = $this->calculateExpenseDateForMonth($recurringExpense, $targetMonthDate);
                }

                $needsRecreation = false;
                if ($existingExpense) {
                    $existingDueDateFormatted = $existingExpense->dueDate()->format('Y-m-d H:i:s');
                    $formDueDateFormatted = $formDueDate->format('Y-m-d H:i:s');

                    if ($existingExpense->amount() !== $amount || $existingDueDateFormatted !== $formDueDateFormatted) {
                        $this->entityManager->remove($existingExpense);
                        $needsRecreation = true;
                    }
                } else {
                    $needsRecreation = true;
                }

                if ($needsRecreation) {
                    $newExpenseId = Uuid::v4()->toRfc4122();
                    $expenseType = $recurringExpense->expenseType();

                    if (!$expenseType) {
                        $invalidRecurringExpenses[] = "Tipo de despesa não definido para '{$recurringExpense->description()}'";
                        continue;
                    }

                    $accountForConstructor = null;
                    if (method_exists($expenseType, 'account') && $expenseType->account() !== null) {
                        $accountForConstructor = $expenseType->account();
                    } elseif (method_exists($expenseType, 'defaultAccount') && $expenseType->defaultAccount() !== null) {
                        $accountForConstructor = $expenseType->defaultAccount();
                    } elseif (method_exists($recurringExpense, 'account') && $recurringExpense->account() !== null) {
                        $accountForConstructor = $recurringExpense->account();
                    }

                    if ($accountForConstructor === null) {
                        $expensesWithoutAccounts[] = $recurringExpense->description();
                    }

                    $newExpense = new Expense(
                        $newExpenseId,
                        $amount,
                        $expenseType,
                        $accountForConstructor,
                        $formDueDate
                    );
                    $newExpense->setRecurringExpense($recurringExpense);
                    $newExpense->addDescription($recurringExpense->description());

                    $this->entityManager->persist($newExpense);

                    $event = new ExpenseWasCreated(
                        $newExpense->id(),
                        $newExpense->amount(),
                        $newExpense->description(),
                        $newExpense->dueDate()->format('Y-m-d H:i:s'),
                        $expenseType->id(),
                        $accountForConstructor ? $accountForConstructor->id() : '',
                        Uuid::v4()->toRfc4122(),
                        (new DateTimeImmutable())->format(DateTimeInterface::ATOM)
                    );

                    $this->commandBus->dispatch($event);
                    $generatedCount++;
                }
            } else {
                if ($existingExpense) {
                    $this->entityManager->remove($existingExpense);
                }
            }
        }

        if (!empty($invalidRecurringExpenses)) {
            $messages[] = ['type' => 'error', 'text' => "Problemas encontrados: " . implode('; ', $invalidRecurringExpenses)];
        }
        if (!empty($expensesWithoutAccounts)) {
            $messages[] = ['type' => 'warning', 'text' => "As seguintes despesas foram criadas sem conta associada: " . implode(', ', $expensesWithoutAccounts) . ". Revise-as para adicionar uma conta."];
        }
        if (!empty($missingAmounts)) {
            $messages[] = ['type' => 'warning', 'text' => "As seguintes despesas foram ignoradas por não terem valor fornecido: " . implode(', ', $missingAmounts)];
        }

        if (empty($invalidRecurringExpenses)) {
            if ($generatedCount === 1) {
                $messages[] = ['type' => 'success', 'text' => "1 despesa foi gerada com sucesso para " . $this->formatMonthName($targetMonthDate)];
            } elseif ($generatedCount > 1) {
                $messages[] = ['type' => 'success', 'text' => "{$generatedCount} despesas foram geradas com sucesso para " . $this->formatMonthName($targetMonthDate)];
            } elseif (empty($missingAmounts) && empty($expensesWithoutAccounts)) {
                $messages[] = ['type' => 'info', 'text' => "Nenhuma despesa nova foi gerada para " . $this->formatMonthName($targetMonthDate)];
            }
        }
        $this->entityManager->flush();

        return [
            'generated_count' => $generatedCount,
            'messages' => $messages
        ];
    }

    public function getInitialRecurringExpensesData(\DateTimeInterface $targetMonthDate): array
    {
        $year = (int)$targetMonthDate->format('Y');
        $month = (int)$targetMonthDate->format('n');
        $preparedData = [];

        // Use the service's own repository
        $activeRecurringEntities = $this->recurringExpenseRepository->findActivesForThisMonth($month);

        foreach ($activeRecurringEntities as $re) {
            // Use the service's own private method
            if (!$this->isRecurringExpenseEffectivelyActive($re, $year, $month)) {
                continue;
            }

            // Use the service's own repository
            if ($this->expenseRepository->hasInstanceForRecurringExpenseAndMonth($re, $year, $month)) {
                continue;
            }

            $isIncluded = true; // Default to true for initial form display
            $currentAmount = $re->amount();
            // Use the service's own private method
            $currentDueDate = $this->calculateExpenseDateForMonth($re, $targetMonthDate);

            $preparedData[] = [
                'recurringExpenseId' => $re->id(),
                'description' => $re->description() ?: ('Recorrente ID: ' . $re->id()),
                'include' => $isIncluded,
                'dueDate' => $currentDueDate,
                'amount' => $currentAmount,
            ];
        }
        return $preparedData;
    }

    private function isRecurringExpenseEffectivelyActive(RecurringExpense $re, int $year, int $month): bool
    {
        $targetPeriodStart = (new DateTimeImmutable())->setDate($year, $month, 1)->setTime(12,0,0);
        $targetPeriodEnd = $targetPeriodStart->modify('last day of this month')->setTime(23,59,59);
        $reStartDate = $re->startDate();
        $reEndDate = $re->endDate();
        if ($reStartDate && $reStartDate > $targetPeriodEnd) { return false; }
        if ($reEndDate && $reEndDate < $targetPeriodStart->setTime(0,0,0) ) { return false; }
        return true;
    }

    private function calculateExpenseDateForMonth(RecurringExpense $re, DateTimeInterface $targetMonthDate): DateTime
    {
        $year = (int)$targetMonthDate->format('Y');
        $month = (int)$targetMonthDate->format('n');
        $day = $re->dueDay() ?? 1;

        if (!checkdate($month, $day, $year)) {
            $day = (int)((clone $targetMonthDate)->modify('last day of this month')->format('d'));
        }
        return (new DateTime())->setDate($year, $month, $day)->setTime(12,0,0);
    }

    private function formatMonthName(DateTimeInterface $date): string
    {
        $formatter = new IntlDateFormatter(
            'pt_BR',
            IntlDateFormatter::NONE,
            IntlDateFormatter::NONE,
            'America/Sao_Paulo',
            IntlDateFormatter::GREGORIAN,
            'MMMM yyyy'
        );
        return ucfirst($formatter->format($date)) ?: $date->format('F Y');
    }
}
