<?php

namespace App\Controller\Admin\ExpenseType;

use App\Repository\ExpenseTypeRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted; // Para proteger la ruta

#[IsGranted("ROLE_ADMIN")] // Asegúrate de que el rol sea el correcto para tu admin
class ExpenseTypeIndexController extends AbstractController
{
    public function __construct(
        private readonly ExpenseTypeRepository $expenseTypeRepository
    ) {
    }

    #[Route('/admin/expense-type', name: 'admin_expense_type_index', methods: ['GET'])]
    public function __invoke(): Response
    {
        $expenseTypes = $this->expenseTypeRepository->findAllActivesOrderedByCode();

        return $this->render('admin/ExpenseType/list.html.twig', [
            'expense_types' => $expenseTypes,
            'page_title_text' => 'Lista de Tipos de Despesa',
        ]);
    }
}
