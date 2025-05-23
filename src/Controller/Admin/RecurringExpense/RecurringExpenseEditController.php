<?php

namespace App\Controller\Admin\RecurringExpense;

use App\Entity\RecurringExpense;
use App\Form\Admin\RecurringExpenseFormType;
use App\Repository\RecurringExpenseRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
class RecurringExpenseEditController extends AbstractController
{
    public function __construct(
        private readonly RecurringExpenseRepository $recurringExpenseRepository // Inyecta el repositorio
        // Puedes necesitar inyectar el MessageBusInterface si guardas aquí
    ) {
    }

    #[Route('/admin/recurring-expenses/{id}/edit', name: 'admin_recurring_expense_edit', methods: ['GET', 'POST'])]
    public function __invoke(Request $request, RecurringExpense $recurringExpense): Response
    {
        $form = $this->createForm(RecurringExpenseFormType::class, $recurringExpense);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Aquí iría la lógica para guardar los cambios
            // Probablemente usando tu RecurringExpenseRepository o un Command/Handler
            $this->recurringExpenseRepository->save($recurringExpense, true); // Ejemplo simple con el repositorio

            $this->addFlash('success', 'recurring_expense.updated_successfully');

            // Redirigir a la lista después de guardar
            return $this->redirectToRoute('admin_recurring_expense_index');
        }

        // Renderizar la plantilla del formulario de edición
        return $this->render('admin/recurring_expense/edit.html.twig', [
            'form' => $form->createView(),
            'recurring_expense' => $recurringExpense, // Puedes pasar la entidad a la plantilla si la necesitas
        ]);
    }
}
