<?php

namespace App\Controller\Admin\Expense;

use App\Form\Admin\ExpenseFormType;
use App\Repository\ExpenseRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ExpenseEditController extends AbstractController
{
    #[Route('/admin/expenses/{id}/edit', name: 'admin_expense_edit', methods: ['GET', 'POST'])]
    public function __invoke(string $id, Request $request, ExpenseRepository $expenseRepository): Response
    {
        $expense = $expenseRepository->find($id);

        if (!$expense) {
            throw $this->createNotFoundException('Expense not found.');
        }

        $form = $this->createForm(ExpenseFormType::class, [
            'amount' => $expense->amount(),
            'description' => $expense->description(),
            'date' => $expense->dueDate()->format('Y-m-d'),
            'type' => $expense->type(),
            'paidFromAccountId' => $expense->account(),
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            $expense->addDescription($data['description']);
            $expense->payedAt(new \DateTimeImmutable());
            $expenseRepository->save($expense, true);

            $this->addFlash('success', 'Expense updated successfully.');

            return $this->redirectToRoute('admin_expense_list');
        }

        return $this->render('admin/expense/edit.html.twig', [
            'form' => $form->createView(),
            'expense' => $expense,
        ]);
    }
}
