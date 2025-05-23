<?php

namespace App\Bus\Slip;

use App\Event\Slip\SlipsWasGenerated;
use App\Service\SlipGenerationService;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\Exception\ExceptionInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Uid\Uuid;

#[AsMessageHandler]
readonly class GenerateSlipsCommandHandler
{
    public function __construct(
        private SlipGenerationService $slipGenerationService,
        private MessageBusInterface   $bus,
        private LoggerInterface       $logger
    ) {
    }

    /**
     * @throws \DateMalformedStringException
     */
    public function __invoke(GenerateSlipsCommand $command): array
    {
        $this->logger->info(
            '[GenerateSlipsCommandHandler] Command process was initiated.',
            ['targetMonth' => $command->targetMonthDateString]
        );

        try {
            $targetMonthDate = new \DateTimeImmutable($command->targetMonthDateString);

            $result = $this->slipGenerationService->generateSlipsForMonth($targetMonthDate);

            if ($result['success'] && !empty($result['slipsData'])) {
                $this->logger->info(
                    '[GenerateSlipsCommandHandler] O serviço gerou com sucesso os boletos.',
                    [
                        'month' => $targetMonthDate->format('Y-m'),
                        'count' => $result['slipsCount'] ?? count($result['slipsData']),
                        'service_message' => $result['message'] ?? '',
                    ]
                );

                $aggregateId = $targetMonthDate->format('Y-m');

                $event = new SlipsWasGenerated(
                    $aggregateId,
                    $result['slipsData'],
                    Uuid::v4()->toRfc4122(),
                    (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM)
                );

                $this->bus->dispatch($event);
                $this->logger->info(
                    '[GenerateSlipsCommandHandler] Evento SlipsWasGenerated despachado.',
                    ['aggregateId' => $aggregateId, 'event_id' => $event->eventId()]
                );

            } elseif ($result['success'] && empty($result['slipsData'])) {
                $this->logger->warning(
                    '[GenerateSlipsCommandHandler] Proceso de generación de boletos completado por el servicio, pero no se generaron boletos.',
                    [
                        'month' => $targetMonthDate->format('Y-m'),
                        'service_message' => $result['message'] ?? 'Posiblemente no hay residentes/gastos elegibles.',
                    ]
                );
                // Opcional: Podrías despachar un evento diferente aquí si es un escenario significativo,
                // por ejemplo, `SlipsGenerationYieldedNoResults`.
            } else {
                // La generación falló según el servicio (ej. menos de 5 boletos, rollback)
                $this->logger->error(
                    '[GenerateSlipsCommandHandler] Falló la generación de boletos según el servicio.',
                    [
                        'month' => $targetMonthDate->format('Y-m'),
                        'service_message' => $result['message'] ?? 'Error desconocido desde SlipGenerationService.',
                    ]
                );
            }
            return $result;
        } catch (\DateMalformedStringException $e) {
            $this->logger->error(
                '[GenerateSlipsCommandHandler] Exceção na geração dos boletos.',
                [
                    'exception_message' => $e->getMessage(),
                    'exception_trace' => $e->getTraceAsString(),
                    'targetMonth' => $command->targetMonthDateString,
                ]
            );
            throw $e;
        } catch (ExceptionInterface $e) {
            $this->logger->error('[GenerateSlipsCommandHandler] Exceção na geração dos boletos');
        }

        return $result;
    }
}
