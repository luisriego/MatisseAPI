<?php

declare(strict_types=1);

namespace App\Controller\Admin\Income;

use App\Bus\Income\CreateIncomeCommand;
use App\Form\Admin\IncomeFormType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\Exception\ExceptionInterface;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
class IncomeCreateController extends AbstractController
{
    public function __construct(
        private readonly MessageBusInterface   $bus,
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {
    }

    #[Route('/admin/incomes/new', name: 'admin_income_create', methods: ['GET', 'POST'])]
    public function __invoke(Request $request): Response
    {
        $form = $this->createForm(IncomeFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $data['amount'] = (int) $data['amount'];
            try {
                $command = new CreateIncomeCommand(
                    $data['incomeTypeId']->id(),
                    $data['amount'],
                    $data['description'],
                    $data['dueDate'],
                    $data['residentId']->id()
                );
                $this->bus->dispatch($command);
                $this->addFlash('success', 'income.created_successfully');

                return new RedirectResponse($this->urlGenerator->generate('admin_income_list'));
            } catch (HandlerFailedException $e) {
                $previous = $e->getPrevious();
                $errorMessage = $previous ? $previous->getMessage() : 'Erro ao processar a receita.';
                $this->addFlash('error', $errorMessage);
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erro ao processar a receita.');
            } catch (ExceptionInterface $e) {
            }
        }

        return $this->render('admin/income/new.html.twig', [
            'form' => $form,
        ]);
    }
}