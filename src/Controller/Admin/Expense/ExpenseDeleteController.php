<?php

declare(strict_types=1);

namespace App\Controller\Admin\Expense;

use App\Repository\ExpenseRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ExpenseDeleteController extends AbstractController
{
    #[Route('/admin/expenses/{id}/delete', name: 'admin_expense_delete', methods: ['POST'])]
    public function __invoke(string $id, ExpenseRepository $expenseRepository): Response
    {
        $expense = $expenseRepository->find($id);

        if (!$expense) {
            $this->addFlash('error', 'Expense not found.');
            return $this->redirectToRoute('admin_expense_list');
        }

        $expenseRepository->remove($expense, true);

        $this->addFlash('success', 'Expense deleted successfully.');

        return $this->redirectToRoute('admin_expense_list');
    }
}