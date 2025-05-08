<?php

declare(strict_types=1);

namespace App\Controller\Admin\Expense;

use App\Entity\User;
use App\Repository\ExpenseRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[isGranted(User::ROLE_ADMIN)]
final class ExpenseListController extends AbstractController
{
    public function __construct(
        private readonly ExpenseRepository $expenseRepository
    ) {}

    #[Route('/admin/expenses', name: 'admin_expense_list', methods: ['GET'])]
    public function list(Request $request): Response
    {
        $month = $request->query->get('month'); // Format: YYYY-MM
        $selectedYear = null;
        $selectedMonth = null;

        if ($month) {
            [$selectedYear, $selectedMonth] = explode('-', $month);
        }

        $expenses = $selectedMonth && $selectedYear
            ? $this->expenseRepository->findByMonth($selectedMonth, $selectedYear)
            : $this->expenseRepository->findGroupedByMonth();

        return $this->render('admin/expense/list.html.twig', [
            'expenses' => $expenses,
            'selectedYear' => $selectedYear,
            'selectedMonth' => $selectedMonth,
        ]);
    }
}
