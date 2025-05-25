<?php

namespace App\Controller\Admin;

use App\Repository\ExpenseRepository;
use App\Repository\ExpenseTypeRepository;
use App\Repository\IncomeRepository;
use App\Repository\RecurringExpenseRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')] // O el rol que uses para admin
class AdminDashboardController extends AbstractController
{
    public function __construct(
        private readonly RecurringExpenseRepository $recurringExpenseRepository,
        private readonly ExpenseTypeRepository $expenseTypeRepository,
        private readonly IncomeRepository $incomeRepository,
        private readonly ExpenseRepository $expenseRepository
    ) {
    }

    #[Route('/admin', name: 'admin_dashboard', methods: ['GET'])]
    public function __invoke(): Response
    {
        $targetMonthDate = new \DateTime();
        $year = $targetMonthDate->format('Y');
        $month = $targetMonthDate->format('n');

        $totalRecurringExpenses = $this->recurringExpenseRepository->count([]);
        $activeRecurringExpenses = $this->recurringExpenseRepository->count(['isActive' => true]);
        $totalExpenseTypes = $this->expenseTypeRepository->count([]);
        $totalIncomes = $this->incomeRepository->count([]);
        $totalExpenses = $this->expenseRepository->count([]);
        $expenses = $this->expenseRepository->findByMonth($month, $year);

        $totalExpensesAmountCents = $this->expenseRepository->getTotalAmountForCurrentMonth();


        return $this->render('admin/dashboard/index.html.twig', [
            'totalRecurringExpenses' => $totalRecurringExpenses,
            'activeRecurringExpenses' => $activeRecurringExpenses,
            'inactiveRecurringExpenses' => $totalRecurringExpenses - $activeRecurringExpenses,
            'totalExpenseTypes' => $totalExpenseTypes,
            'totalIncomes' => $totalIncomes,
            'totalExpenses' => $totalExpenses,
            'totalExpensesInCents' => $totalExpensesAmountCents,
            'expenses' => $expenses
        ]);
    }
}
