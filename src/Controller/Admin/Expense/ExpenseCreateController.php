<?php

namespace App\Controller\Admin\Expense;

use App\Bus\Expense\CreateExpenseCommand;
use App\Entity\User;
use App\Form\Admin\ExpenseFormType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted(User::ROLE_ADMIN)]
final class ExpenseCreateController extends AbstractController
{
    public function __construct(
        private readonly MessageBusInterface   $bus,
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {
    }

    #[Route('/admin/expenses/new', name: 'admin_expense_create', methods: ['GET', 'POST'])]
    public function __invoke(
        #[CurrentUser] User $user,
        Request $request,
    ): Response
    {
        $form = $this->createForm(ExpenseFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            try {
                $command = new CreateExpenseCommand(
                    $data['type']->id(),
                    $data['amount'],
                    $data['description'],
                    $data['date'],
                    $data['paidFromAccountId']->id()
                );

                $this->bus->dispatch($command);

                $this->addFlash('success', 'expense.created_successfully');

                return new RedirectResponse($this->urlGenerator->generate('admin_expense_list'));
            } catch (HandlerFailedException $e) {
                $previous = $e->getPrevious();
                $errorMessage = $previous ? $previous->getMessage() : 'Erro ao processar a despesa.';
                $this->addFlash('error', $errorMessage);

            } catch (\Exception $e) {
                $this->addFlash('error', 'Ocorreu um erro inesperado ao salvar a despesa.');
            }
        }

        return $this->render('admin/expense/new.html.twig', [
            'form' => $form->createView(),
            'user' => $user,
        ]);
    }
}