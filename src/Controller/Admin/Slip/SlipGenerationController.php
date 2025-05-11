<?php

namespace App\Controller\Admin\Slip;

use App\Bus\Slip\GenerateSlipsCommand;
use App\Form\Admin\GenerateSlipsFormType;
use App\Repository\SlipRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\Exception\ExceptionInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/slips')]
#[IsGranted('ROLE_ADMIN')]
class SlipGenerationController extends AbstractController
{
    public function __construct(
        private readonly SlipRepository $slipRepository,
        private readonly MessageBusInterface $commandBus
    ) {
    }

    /**
     * @throws ExceptionInterface
     * @throws \DateMalformedStringException
     */
    #[Route('/generate', name: 'admin_slip_generation', methods: ['GET', 'POST'])]
    public function __invoke(Request $request): Response
    {
        $formData = ['targetMonth' => new \DateTimeImmutable('first day of last month')];

        // Opciones iniciales para el formulario
        $formOptions = [
            'needs_confirmation' => false,
            'month_to_confirm' => null,
            'existing_slips_count' => 0,
        ];

        // Lógica para manejar el estado de confirmación
        $formName = $this->createForm(GenerateSlipsFormType::class, $formData, $formOptions)->getName();
        if ($request->isMethod('POST') && $request->request->has($formName)) {
            $submittedData = $request->request->all($formName);
            if (isset($submittedData['targetMonth']) && isset($submittedData['confirm_regeneration'])) {
                try {
                    $monthToConfirmDate = new \DateTimeImmutable($submittedData['targetMonth']);
                    $formOptions['month_to_confirm'] = $monthToConfirmDate;
                    $formOptions['existing_slips_count'] = $this->slipRepository->countForMonth(
                        (int)$monthToConfirmDate->format('Y'),
                        (int)$monthToConfirmDate->format('m')
                    );
                    $formOptions['needs_confirmation'] = true;
                    $formData['targetMonth'] = $monthToConfirmDate;
                } catch (\Exception $e) {
                    $this->addFlash('danger', 'Data inválida no formulário de confirmação.');
                }
            }
        }

        $form = $this->createForm(GenerateSlipsFormType::class, $formData, $formOptions);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data =  $form->getData();
            $targetMonthDate = new \DateTimeImmutable($data['targetMonth']);

            $year = (int)$targetMonthDate->format('Y');
            $month = (int)$targetMonthDate->format('m');
            $existingSlipsCount = $this->slipRepository->countForMonth($year, $month);

            $confirmRegenerationField = $form->has('confirm_regeneration') ? $form->get('confirm_regeneration')->getData() : false;

            if ($existingSlipsCount > 0 && !$confirmRegenerationField) {
                $this->addFlash('warning', sprintf(
                    'Já existem %d boletos para %s. Por favor, confirme para regerá-los.',
                    $existingSlipsCount,
                    $targetMonthDate->format('F Y')
                ));

                $confirmationFormOptions = [
                    'needs_confirmation' => true,
                    'month_to_confirm' => $targetMonthDate,
                    'existing_slips_count' => $existingSlipsCount,
                ];
                $confirmationFormData = ['targetMonth' => $targetMonthDate];
                $confirmationForm = $this->createForm(GenerateSlipsFormType::class, $confirmationFormData, $confirmationFormOptions);

                return $this->render('admin/slip/generate.html.twig', [
                    'form' => $confirmationForm->createView(),
                ]);
            }

            $command = new GenerateSlipsCommand($targetMonthDate->format('Y-m-d'));
            $envelope = $this->commandBus->dispatch($command);

            // Para obtener el resultado de un handler síncrono:
            /** @var HandledStamp|null $handledStamp */
            $handledStamp = $envelope->last(HandledStamp::class);
            $handlerResult = null;
            if ($handledStamp) {
                $handlerResult = $handledStamp->getResult();
            }

            if ($handlerResult && is_array($handlerResult) && isset($handlerResult['success'])) {
                if ($handlerResult['success'] && isset($handlerResult['slipsCount']) && $handlerResult['slipsCount'] > 0) {
                    // Éxito y boletos generados
                    $this->addFlash('success', $handlerResult['message']); // Mensaje del servicio
                } elseif ($handlerResult['success'] && isset($handlerResult['slipsCount']) && $handlerResult['slipsCount'] === 0) {
                    // Éxito, pero ningún boleto generado (ej. sin gastos, sin residentes, todos valor cero)
                    $this->addFlash('warning', $handlerResult['message']); // Mensaje del servicio
                } else {
                    // Fallo reportado por el servicio/handler
                    $this->addFlash('danger', $handlerResult['message'] ?? 'Ocorreu um problema ao processar a solicitação.');
                }
            } else {
                // Mensaje genérico si no se pudo obtener resultado del handler
                // (podría ser asíncrono o un error inesperado antes que el handler devuelva un array)
                $this->addFlash('info', sprintf(
                    'A solicitação para gerar boletos para %s foi enviada. Verifique os logs para o resultado do processamento.',
                    $targetMonthDate->format('F Y')
                ));
            }

            return $this->redirectToRoute('admin_slip_generation'); // O a una lista, etc.
        }

        return $this->render('admin/slip/generate.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
