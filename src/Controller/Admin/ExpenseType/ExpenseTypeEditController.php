<?php

namespace App\Controller\Admin\ExpenseType;

use App\Entity\ExpenseType;
use App\Form\Admin\ExpenseTypeFormType;
use App\Repository\ExpenseTypeRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted("ROLE_ADMIN")]
class ExpenseTypeEditController extends AbstractController
{
    public function __construct(
        private readonly ExpenseTypeRepository $expenseTypeRepository
    ) {
    }

    #[Route('/admin/expense-type/{id}/edit', name: 'admin_expense_type_edit', methods: ['GET', 'POST'])]
    public function __invoke(Request $request, int $id): Response
    {
        $expenseType = $this->expenseTypeRepository->findOneByIdOrFail($id);

        $form = $this->createForm(ExpenseTypeFormType::class, $expenseType);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->expenseTypeRepository->save($expenseType, true);

            $this->addFlash('success', 'Tipo de despesa atualizado com sucesso!');

            return $this->redirectToRoute('admin_expense_type_index');
        }

        return $this->render('admin/ExpenseType/edit.html.twig', [
            'expense_type' => $expenseType,
            'form' => $form,
            'page_title_text' => 'Editar Tipo de Despesa',
        ]);
    }
}
