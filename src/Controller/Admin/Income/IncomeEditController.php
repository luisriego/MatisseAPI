<?php

namespace App\Controller\Admin\Income;

use App\Form\Admin\IncomeFormType;
use App\Repository\IncomeRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class IncomeEditController extends AbstractController
{
    public function __construct(
        private readonly IncomeRepository $incomeRepository,
    ) {
    }

    public function __invoke(string $id, Request $request): Response
    {
        $income = $this->incomeRepository->findOneByIdOrFail($id);

        $form = $this->createForm(IncomeFormType::class, [
            'amount' => $income->amount(),
            'description' => $income->description(),
            'dueDate' => $income->dueDate()->format('Y-m-d'),
            'incomeTypeId' => $income->type(),
            'residentId' => $income->residence()->id()
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid) {
            $data = $form->getData();

            $income->addDescription($data['description']);
            $income->payedAt(new \DateTimeImmutable());
            $this->incomeRepository->save($income, true);

            $this->addFlash('success', 'Income updated successfully.');

            return $this->redirectToRoute('admin_income_list');
        }

        return $this->render('admin/income/edit.html.twig', [
            'form' => $form->createView(),
            'income' => $income,
        ]);
    }
}
