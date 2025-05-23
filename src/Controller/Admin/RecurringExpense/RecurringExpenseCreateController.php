<?php

declare(strict_types=1);

namespace App\Controller\Admin\RecurringExpense;

use App\Bus\RecurringExpense\CreateRecurringExpenseCommand;
use App\Entity\RecurringExpense;
use App\Entity\User;
use App\Form\Admin\RecurringExpenseFormType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\Exception\ExceptionInterface;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[IsGranted('ROLE_ADMIN')]
class RecurringExpenseCreateController extends AbstractController
{
    public function __construct(
        private readonly MessageBusInterface $bus,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly ValidatorInterface $validator
    ) {
    }

    /**
     * @throws ExceptionInterface
     */
    #[Route('/admin/recurring-expenses/new', name: 'admin_recurring_expense_generate', methods: ['GET', 'POST'])]
    public function __invoke(Request $request, #[CurrentUser] User $user,): Response
    {
        $recurringExpense = new RecurringExpense();
        $form = $this->createForm(RecurringExpenseFormType::class, $recurringExpense);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if (null === $recurringExpense->expenseType()) {
                $this->addFlash('error', 'O tipo de despesa é obrigatório.');
                return $this->render('admin/recurring_expense/new.html.twig', [
                    'form' => $form->createView(),
                    'user' => $user, // Si se usa
                ]);
            }

            try {
                $command = new CreateRecurringExpenseCommand(
                    description: $recurringExpense->description(),
                    amount: $recurringExpense->amount(),
                    expenseTypeId: $recurringExpense->expenseType()->id(),
                    accountId: $recurringExpense->account()->id(),
                    frequency: $recurringExpense->frequency(),
                    dueDay: $recurringExpense->dueDay(),
                    monthsOfYear: $recurringExpense->monthsOfYear(),
                    startDate: $recurringExpense->startDate(),
                    endDate: $recurringExpense->endDate(),
                    occurrencesLeft: $recurringExpense->occurrencesLeft(),
                    isActive: $recurringExpense->isActive(),
                    notes: $recurringExpense->notes()
                );

                $this->bus->dispatch($command);

                $this->addFlash('success', 'recurring_expense.created_successfully');

                return new RedirectResponse($this->urlGenerator->generate('admin_recurring_expense_index'));
            } catch (HandlerFailedException $e) {
                $previous = $e->getPrevious();
                $errorMessage = $previous ? $previous->getMessage() : 'Erro ao processar a despesa recorrente.';
                $this->addFlash('error', $errorMessage);
            }catch (\Exception $e) {
                $this->addFlash('error', 'Ocorreu um erro inesperado ao salvar a despesa recorrente.');
            }
        }

        return $this->render('admin/recurring_expense/new.html.twig', [
            'form' => $form->createView(),
            'user' => $user,
        ]);
    }
}
