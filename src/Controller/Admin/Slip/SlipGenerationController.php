<?php

// src/Controller/Admin/Slip/SlipGenerationController.php
namespace App\Controller\Admin\Slip;

use App\Bus\Slip\GenerateSlipsCommand;
// App\Entity\Expense; // No longer directly used in this controller after refactoring
// App\Entity\RecurringExpense; // No longer directly used in this controller after refactoring
// App\Event\Expense\ExpenseWasCreated; // No longer directly used in this controller
use App\Form\Admin\GenerateSlipsFormType;
use App\Repository\ExpenseRepository; // Kept for findByMonthDueDateRange
use App\Repository\RecurringExpenseRepository; // Kept for constructor, though not directly used in methods
use App\Repository\SlipRepository;
use App\Service\Slip\RecurringExpenseHandlerService;
use App\Service\Slip\SlipTargetMonthService;
use Doctrine\ORM\EntityManagerInterface; // Kept for constructor
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\Exception\ExceptionInterface as MessengerExceptionInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
// use Symfony\Component\Uid\Uuid; // No longer directly used

#[Route('/admin/slips')]
#[IsGranted('ROLE_ADMIN')]
class SlipGenerationController extends AbstractController
{
    public function __construct(
        private readonly SlipRepository $slipRepository,
        private readonly ExpenseRepository $expenseRepository,
        private readonly RecurringExpenseRepository $recurringExpenseRepository, // Preserved as per instruction
        private readonly EntityManagerInterface $entityManager, // Preserved as per instruction
        private readonly MessageBusInterface $commandBus,
        private readonly SlipTargetMonthService $slipTargetMonthService,
        private readonly RecurringExpenseHandlerService $recurringExpenseHandlerService
    ) {
    }

    #[Route('/generate', name: 'admin_slip_generation', methods: ['GET', 'POST'])]
    public function __invoke(Request $request): Response
    {
        // Use SlipTargetMonthService to determine the effective month
        $effectiveTargetMonth = $this->slipTargetMonthService->determine($request);

        // Use RecurringExpenseHandlerService to get initial data for the form's recurringExpenses
        $recurringExpensesDataForForm = $this->recurringExpenseHandlerService->getInitialRecurringExpensesData($effectiveTargetMonth);

        $yearForInitialCheck = (int)$effectiveTargetMonth->format('Y');
        $monthForInitialCheck = (int)$effectiveTargetMonth->format('m');
        $initialExistingSlipsCount = $this->slipRepository->countForMonth($yearForInitialCheck, $monthForInitialCheck);

        $formData = [
            'targetMonth' => $effectiveTargetMonth->format('Y-m'),
            'recurringExpenses' => $recurringExpensesDataForForm,
        ];

        $formOptions = [
            'needs_confirmation' => $initialExistingSlipsCount > 0,
            'month_to_confirm' => $initialExistingSlipsCount > 0 ? $effectiveTargetMonth : null,
            'existing_slips_count' => $initialExistingSlipsCount,
        ];

        $form = $this->createForm(GenerateSlipsFormType::class, $formData, $formOptions);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var array<int, array<string, mixed>> $submittedRecurringData */
            $submittedRecurringData = $form->get('recurringExpenses')->getData() ?? [];

            // Use RecurringExpenseHandlerService to process the submitted recurring expenses
            $processingResult = $this->recurringExpenseHandlerService->processSelections($submittedRecurringData, $effectiveTargetMonth);
            foreach ($processingResult['messages'] as $message) {
                $this->addFlash($message['type'], $message['text']);
            }

            // Existing logic for dispatching GenerateSlipsCommand
            $command = new GenerateSlipsCommand($effectiveTargetMonth->format('Y-m-d'));
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
                    $this->formatMonthName($effectiveTargetMonth) // formatMonthName is kept for this
                ));
            }
            return $this->redirectToRoute('admin_slip_generation', ['targetMonthInput' => $effectiveTargetMonth->format('Y-m')]);
        }

        // Data for rendering the template
        $displayTargetMonthDate = $effectiveTargetMonth;
        $monthlyExpensesToDisplay = $this->expenseRepository->findByMonthDueDateRange($displayTargetMonthDate);
        $selectedMonthNameToDisplay = $this->formatMonthName($displayTargetMonthDate); // formatMonthName is kept for this

        $monthlySlipsToDisplay = [];
        if ($initialExistingSlipsCount > 0 || ($form->isSubmitted() && !$form->isValid())) {
            $monthlySlipsToDisplay = $this->slipRepository->findByMonthAndYear(
                (int)$displayTargetMonthDate->format('n'),
                (int)$displayTargetMonthDate->format('Y')
            );
        }

        return $this->render('admin/slip/generate.html.twig', [
            'form' => $form->createView(),
            'monthlyExpenses' => $monthlyExpensesToDisplay,
            'selectedMonthName' => $selectedMonthNameToDisplay,
            'monthlySlips' => $monthlySlipsToDisplay,
        ]);
    }

    // Kept method as per instruction, used by __invoke
    private function formatMonthName(\DateTimeInterface $date): string
    {
        $formatter = new \IntlDateFormatter(
            'pt_BR',
            \IntlDateFormatter::NONE,
            \IntlDateFormatter::NONE,
            'America/Sao_Paulo',
            \IntlDateFormatter::GREGORIAN,
            'MMMM yyyy'
        );
        return ucfirst($formatter->format($date)) ?: $date->format('F Y');
    }
}
