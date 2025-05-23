<?php

// src/Controller/Admin/Slip/SlipGenerationController.php
namespace App\Controller\Admin\Slip;

use App\Bus\Slip\GenerateSlipsCommand;
use App\Entity\Expense;
use App\Entity\RecurringExpense;
use App\Event\Expense\ExpenseWasCreated;
use App\Form\Admin\GenerateSlipsFormType;
use App\Repository\ExpenseRepository;
use App\Repository\RecurringExpenseRepository;
use App\Repository\SlipRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\Exception\ExceptionInterface as MessengerExceptionInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Uid\Uuid;

#[Route('/admin/slips')]
#[IsGranted('ROLE_ADMIN')]
class SlipGenerationController extends AbstractController
{
    public function __construct(
        private readonly SlipRepository $slipRepository,
        private readonly ExpenseRepository $expenseRepository,
        private readonly RecurringExpenseRepository $recurringExpenseRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly MessageBusInterface $commandBus
    ) {
    }

    /**
     * @throws MessengerExceptionInterface
     */
    #[Route('/generate', name: 'admin_slip_generation', methods: ['GET', 'POST'])]
    public function __invoke(Request $request): Response
    {
        $effectiveTargetMonth = $this->determineEffectiveTargetMonth($request);
        $recurringExpensesDataForForm = $this->prepareRecurringExpensesDataForForm($effectiveTargetMonth);

        $formData = [
            'targetMonth' => $effectiveTargetMonth,
            'recurringExpenses' => $recurringExpensesDataForForm,
        ];
        $formOptions = [
            'needs_confirmation' => false,
            'month_to_confirm' => null,
            'existing_slips_count' => 0,
        ];

        $form = $this->createForm(GenerateSlipsFormType::class, $formData, $formOptions);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            /** @var \DateTimeImmutable $finalTargetMonthDate */
            $finalTargetMonthDate = $data['targetMonth'];
            /** @var array<int, array<string, mixed>> $submittedRecurringData */
            $submittedRecurringData = $data['recurringExpenses'] ?? [];

            $this->processRecurringExpenseSelections($submittedRecurringData, $finalTargetMonthDate);

            $year = (int)$finalTargetMonthDate->format('Y');
            $monthForDb = (int)$finalTargetMonthDate->format('m');
            $existingSlipsCount = $this->slipRepository->countForMonth($year, $monthForDb);
            $confirmRegenerationField = $form->has('confirm_regeneration') ? $form->get('confirm_regeneration')->getData() : false;

            if ($existingSlipsCount > 0 && !$confirmRegenerationField) {
                $this->addFlash('warning', sprintf(
                    'Já existem %d boletos para %s. Por favor, confirme para regerá-los.',
                    $existingSlipsCount,
                    $finalTargetMonthDate->format('F Y')
                ));
                $confirmationFormOptions = [
                    'needs_confirmation' => true,
                    'month_to_confirm' => $finalTargetMonthDate,
                    'existing_slips_count' => $existingSlipsCount,
                ];
                $confirmationFormData = [
                    'targetMonth' => $finalTargetMonthDate,
                    'recurringExpenses' => $this->prepareRecurringExpensesDataForForm($finalTargetMonthDate),
                ];
                $confirmationForm = $this->createForm(GenerateSlipsFormType::class, $confirmationFormData, $confirmationFormOptions);
                // Usar o método adaptado findByMonthDueDateRange
                $expensesForDisplay = $this->expenseRepository->findByMonthDueDateRange($finalTargetMonthDate);
                $monthNameToDisplay = $this->formatMonthName($finalTargetMonthDate);
                return $this->render('admin/slip/generate.html.twig', [
                    'form' => $confirmationForm->createView(),
                    'monthlyExpenses' => $expensesForDisplay,
                    'selectedMonthName' => $monthNameToDisplay,
                ]);
            }

            $command = new GenerateSlipsCommand($finalTargetMonthDate->format('Y-m-d'));
            $envelope = $this->commandBus->dispatch($command);
            /** @var HandledStamp|null $handledStamp */
            $handledStamp = $envelope->last(HandledStamp::class);
            $handlerResult = $handledStamp?->getResult();

            if ($handlerResult && is_array($handlerResult) && isset($handlerResult['success'])) {
                if ($handlerResult['success'] && isset($handlerResult['slipsCount']) && $handlerResult['slipsCount'] > 0) {
                    $this->addFlash('success', $handlerResult['message']);
                } elseif ($handlerResult['success'] && isset($handlerResult['slipsCount']) && $handlerResult['slipsCount'] === 0) {
                    $this->addFlash('warning', $handlerResult['message']);
                } else {
                    $this->addFlash('danger', $handlerResult['message'] ?? 'Ocorreu um problema ao processar a solicitação.');
                }
            } else {
                $this->addFlash('info', sprintf(
                    'A solicitação para gerar boletos para %s foi enviada. Verifique os logs para o resultado do processamento.',
                    $finalTargetMonthDate->format('F Y')
                ));
            }
            return $this->redirectToRoute('admin_slip_generation', ['targetMonthInput' => $finalTargetMonthDate->format('Y-m')]);
        }

        $displayTargetMonthDate = $form->get('targetMonth')->getData() ?? $effectiveTargetMonth;
        // Usar o método adaptado findByMonthDueDateRange
        $monthlyExpensesToDisplay = $this->expenseRepository->findByMonthDueDateRange($displayTargetMonthDate);
        $selectedMonthNameToDisplay = $this->formatMonthName($displayTargetMonthDate);

        return $this->render('admin/slip/generate.html.twig', [
            'form' => $form->createView(),
            'monthlyExpenses' => $monthlyExpensesToDisplay,
            'selectedMonthName' => $selectedMonthNameToDisplay,
        ]);
    }

    private function determineEffectiveTargetMonth(Request $request): \DateTimeImmutable
    {
        $targetMonthString = null;
        if ($request->isMethod('POST')) {
            $formName = $this->createForm(GenerateSlipsFormType::class)->getName();
            $submittedData = $request->request->all($formName);
            if (isset($submittedData['targetMonth']) && is_string($submittedData['targetMonth']) && !empty($submittedData['targetMonth'])) {
                $targetMonthString = $submittedData['targetMonth'];
            }
        }
        if ($targetMonthString === null && $request->query->has('targetMonthInput')) {
            $targetMonthString = $request->query->getString('targetMonthInput');
        }

        if ($targetMonthString) {
            try {
                return new \DateTimeImmutable($targetMonthString . '-01');
            } catch (\Exception $e) {
                $this->addFlash('warning', 'Formato de data inválido fornecido. Usando data padrão.');
            }
        }

        $currentDateTime = new \DateTimeImmutable();
        $day = (int)$currentDateTime->format('d');
        $initialDefaultMonth = (int)$currentDateTime->format('n');
        $initialDefaultYear = (int)$currentDateTime->format('Y');
        if ($day < 9) {
            $lastMonthDateTime = $currentDateTime->modify('last month');
            $initialDefaultMonth = (int)$lastMonthDateTime->format('n');
            $initialDefaultYear = (int)$lastMonthDateTime->format('Y');
        }
        return (new \DateTimeImmutable())->setDate($initialDefaultYear, $initialDefaultMonth, 1)->setTime(0, 0, 0);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function prepareRecurringExpensesDataForForm(\DateTimeInterface $targetMonthDate): array
    {
        $year = (int)$targetMonthDate->format('Y');
        $month = (int)$targetMonthDate->format('n');
        $preparedData = [];

        $activeRecurringEntities = $this->recurringExpenseRepository->findActivesForThisMonth($month);

        foreach ($activeRecurringEntities as $re) {
            if (!$this->isRecurringExpenseEffectivelyActive($re, $year, $month)) {
                continue;
            }

            // Verifica se já existe uma Expense para este RecurringExpense no mês/ano alvo
            $existingExpense = null;
            $allExpensesForThisRE = $this->expenseRepository->findBy(['recurringExpense' => $re]);
            foreach ($allExpensesForThisRE as $expenseItem) {
                if ($expenseItem->dueDate()->format('Y-m') === $targetMonthDate->format('Y-m')) {
                    $existingExpense = $expenseItem;
                    break;
                }
            }

            // MODIFICAÇÃO PRINCIPAL:
            // Se já existe uma Expense para este recorrente neste mês, NÃO o adicione à lista do formulário.
            if ($existingExpense !== null) {
                continue; // Pula para o próximo RecurringExpense
            }

            // Se chegou aqui, não há Expense existente, então preparamos os dados para o formulário
            // O valor de 'include' será true por padrão, pois estamos apresentando-o para ser incluído.
            // O usuário pode desmarcar se não quiser.
            $isIncluded = true; // Default to include if it's being presented in the form
            $currentAmount = $re->amount(); // Default to RecurringExpense amount
            $currentDueDate = $this->calculateExpenseDateForMonth($re, $targetMonthDate); // Default to calculated due date

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
        $targetPeriodStart = (new \DateTimeImmutable())->setDate($year, $month, 1)->setTime(0,0,0);
        $targetPeriodEnd = $targetPeriodStart->modify('last day of this month')->setTime(23,59,59);
        $reStartDate = $re->startDate();
        $reEndDate = $re->endDate();
        if ($reStartDate && $reStartDate > $targetPeriodEnd) { return false; }
        if ($reEndDate && $reEndDate < $targetPeriodStart) { return false; }
        return true;
    }

    /**
     * @param array<int, array<string, mixed>> $submittedRecurringData
     * @throws MessengerExceptionInterface
     */
    private function processRecurringExpenseSelections(array $submittedRecurringData, \DateTimeInterface $targetMonthDate): void
    {
        $year = (int)$targetMonthDate->format('Y');
        $month = (int)$targetMonthDate->format('n');

        foreach ($submittedRecurringData as $itemData) {
            $recurringExpenseId = $itemData['recurringExpenseId'] ?? null;
            if (!$recurringExpenseId) {
                $this->addFlash('warning', 'Item recorrente inválido (sem ID) recebido.');
                continue;
            }

            $recurringExpense = $this->recurringExpenseRepository->find($recurringExpenseId);
            if (!$recurringExpense) {
                $this->addFlash('warning', "Gasto recorrente com ID {$recurringExpenseId} não encontrado.");
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

            /** @var ?\DateTime $formDueDate */
            $formDueDate = $itemData['dueDate'] ?? null;

            if ($include) {
                if ($amount === null) {
                    $this->addFlash('warning', "Valor não fornecido para '{$recurringExpense->description()}' que estava marcado para inclusão. Ignorando.");
                    if ($existingExpense) {
                        $this->entityManager->remove($existingExpense);
                    }
                    continue;
                }
                // Se 'include' é true, a dueDate é obrigatória. Se não veio do form, calcula.
                if ($formDueDate === null) {
                    $this->addFlash('info', "Data de vencimento não fornecida para '{$recurringExpense->description()}', usando data calculada.");
                    $formDueDate = $this->calculateExpenseDateForMonth($recurringExpense, $targetMonthDate);
                }

                $needsRecreation = false;
                if ($existingExpense) {
                    // Compara a data formatada para evitar problemas com objetos DateTime diferentes representando o mesmo dia/hora
                    $existingDueDateFormatted = $existingExpense->dueDate()->format('Y-m-d');
                    $formDueDateFormatted = $formDueDate->format('Y-m-d');

                    if ($existingExpense->amount() !== $amount || $existingDueDateFormatted !== $formDueDateFormatted) {
                        $this->addFlash('info', "Dados da despesa '{$recurringExpense->description()}' alterados (valor ou data). A despesa existente foi substituída.");
                        $this->entityManager->remove($existingExpense);
                        $needsRecreation = true;
                    }
                } else {
                    $needsRecreation = true; // Não existia, precisa criar
                }

                if ($needsRecreation) { // Changed from if ($existingExpense === null)
                    $newExpenseId = Uuid::v4()->toRfc4122();
                    $expenseType = $recurringExpense->expenseType();


                    if (!$expenseType) { // Explicit check for null ExpenseType
                        $this->addFlash('warning', "Tipo de despesa não definido para '{$recurringExpense->description()}'. Não é possível criar a despesa.");
                        continue;
                    }

                    $accountForConstructor = null;
                    // Tenta obter a conta diretamente do ExpenseType associado ao RecurringExpense
                    // Assumindo que ExpenseType tem um método para obter sua conta associada (ex: getAccount() ou similar)
                    // ou que ExpenseType *é* a própria conta padrão ou tem uma propriedade de conta padrão.

                    // Cenário 1: ExpenseType tem um método getAccount() ou similar que retorna a Account
                    if (method_exists($expenseType, 'account')) { // Ou o nome correto do método em ExpenseType
                        $accountForConstructor = $expenseType->account();
                    }
                    // Cenário 2: Se ExpenseType tem um método defaultAccount() (como tentamos antes)
                    // Este `else if` é opcional se você sabe que `getAccount` é o caminho certo.
                    else if (method_exists($expenseType, 'defaultAccount')) {
                        $accountForConstructor = $expenseType->defaultAccount();
                    }
                    // Cenário 3: RecurringExpense tem uma relação direta com Account
                     else if (method_exists($recurringExpense, 'account')) {
                        $accountForConstructor = $recurringExpense->account();
                     }


                    if ($accountForConstructor === null) {
                        // Se ainda for null, pode ser que não haja conta padrão configurada.
                        // O construtor de Expense aceita null para account.
                        $this->addFlash('info', "Nenhuma conta padrão encontrada para o tipo de despesa de '{$recurringExpense->description()}'. A despesa será criada sem conta associada.");
                    }

                    $newExpense = new Expense(
                        $newExpenseId,
                        $amount,
                        $expenseType, // Pass the ExpenseType object
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
                        (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM)
                    );

                    $this->commandBus->dispatch($event);
                }
            } else { // Not $include
                if ($existingExpense) {
                    $this->entityManager->remove($existingExpense);
                    $this->addFlash('info', "Despesa '{$existingExpense->description()}' (recorrente) foi desmarcada e removida para {$month}/{$year}.");
                }
            }
        }
        $this->entityManager->flush();
    }

    private function calculateExpenseDateForMonth(RecurringExpense $re, \DateTimeInterface $targetMonthDate): \DateTime
    {
        $year = (int)$targetMonthDate->format('Y');
        $month = (int)$targetMonthDate->format('n');
        $day = $re->dueDay() ?? 1;
        if (!checkdate($month, $day, $year)) {
            $day = (int)((clone $targetMonthDate)->modify('last day of this month')->format('d'));
        }
        // Expense constructor e setDueDate esperam \DateTime
        return (new \DateTime())->setDate($year, $month, $day)->setTime(0,0,0);
    }

    private function formatMonthName(\DateTimeInterface $date): string
    {
        $formatter = new \IntlDateFormatter('pt_BR', \IntlDateFormatter::FULL, \IntlDateFormatter::NONE, null, \IntlDateFormatter::GREGORIAN, "MMMM 'de' yyyy");
        return $formatter->format($date);
    }
}
