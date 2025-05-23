<?php

namespace App\Event\RecurringExpense;

use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
readonly class RecurringExpenseWasCreatedHandler
{
    public function __construct(
        private LoggerInterface $logger
    ) {
    }

    public function __invoke(RecurringExpenseWasCreated $event): void
    {
        $this->logger->info(sprintf(
            'Nova Despesa Recorrente criada: ID %s, Descrição: "%s", Frequência: %s. Evento ocorrido em: %s',
            $event->recurringExpenseId,
            $event->description,
            $event->frequency,
            $event->occurredOn
        ));

        // Aquí puedes añadir más lógica:
        // - Enviar un email de notificación al administrador.
        // - Actualizar alguna estadística.
        // - Crear una tarea pendiente relacionada.
        // - Etc.
    }
}
