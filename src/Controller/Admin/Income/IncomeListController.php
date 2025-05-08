<?php

declare(strict_types=1);

namespace App\Controller\Admin\Income;

use App\Entity\User;
use App\Repository\IncomeRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[isGranted(User::ROLE_ADMIN)]
class IncomeListController extends AbstractController
{
    public function __construct(
        private readonly IncomeRepository $incomeRepository
    ) {}

    #[Route('/admin/incomes', name: 'admin_income_list', methods: ['GET'])]
    public function __invoke(Request $request): Response
    {

        return $this->render('admin/income/list.html.twig', [
            'incomes' => $this->incomeRepository->findAll(),
        ]);
    }
}
