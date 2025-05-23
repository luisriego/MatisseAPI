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
class ExpenseTypeCreateController extends AbstractController
{
    public function __construct(
        private readonly ExpenseTypeRepository $expenseTypeRepository,
    ) {
    }

    #[Route('/admin/expense-type/new', name: 'admin_expense_type_new', methods: ['GET', 'POST'])]
    public function create(Request $request): Response
    {
        $expenseType = new ExpenseType();
        $form = $this->createForm(ExpenseTypeFormType::class, $expenseType);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $this->expenseTypeRepository->save($expenseType, true);

            $this->addFlash('success', 'Tipo de despesa "' . $expenseType->name() . '" criado com sucesso!');

            return $this->redirectToRoute('admin_expense_type_index');
        }

        return $this->render('admin/ExpenseType/new.html.twig', [
            'form' => $form->createView(),
            'page_title_text' => 'Novo Tipo de Despesa',
        ]);
    }
}
