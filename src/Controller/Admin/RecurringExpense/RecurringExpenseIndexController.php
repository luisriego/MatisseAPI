<?php

namespace App\Controller\Admin\RecurringExpense;

use App\Repository\RecurringExpenseRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
class RecurringExpenseIndexController extends AbstractController
{
    public function __construct(
        private readonly RecurringExpenseRepository $recurringExpenseRepository,
    )
    {

    }
    #[Route('/admin/recurring-expenses', name: 'admin_recurring_expense_index', methods: ['GET'])]
    public function __invoke(): Response
    {
        $recurringExpenses = $this->recurringExpenseRepository->findAllActives();

        return $this->render('admin/recurring_expense/index.html.twig', [
            'recurring_expenses' => $recurringExpenses
        ]);
    }
}
