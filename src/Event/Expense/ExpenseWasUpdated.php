<?php

namespace App\Event\Expense;

use App\Bus\DomainEvent;
use Symfony\Component\Uid\Uuid; // Asumiendo que usas UUIDs para los IDs

final readonly class ExpenseWasUpdated extends DomainEvent
{
    /**
     * @param string $aggregateId El ID del gasto que fue actualizado.
     * @param array<string, array{old: mixed, new: mixed}> $changedFields Un array asociativo donde cada clave es el nombre del campo que cambió,
     *                                                                  y el valor es un array con 'old' y 'new' values.
     * @param string|null $userId El ID del usuario que realizó el cambio (opcional).
     * @param string $eventId El ID único para esta instancia del evento.
     * @param string $occurredOn La fecha y hora en que ocurrió el evento (formato ATOM).
     */
    public function __construct(
        string $aggregateId,
        public array $changedFields,
        public ?string $userId, // ID del usuario que realizó el cambio
        string $eventId,
        string $occurredOn
    ) {
        parent::__construct($aggregateId, $eventId, $occurredOn);
    }

    public static function fromPrimitives(
        string $aggregateId,
        array $body, // Espera ['changedFields' => [...], 'userId' => '...']
        string $eventId,
        string $occurredOn
    ): self {
        return new self(
            $aggregateId,
            $body['changedFields'] ?? [],
            $body['userId'] ?? null,
            $eventId,
            $occurredOn
        );
    }

    public static function eventName(): string
    {
        return 'expense.was_updated';
    }

    public function toPrimitives(): array
    {
        return [
            'changedFields' => $this->changedFields,
            'userId' => $this->userId,
        ];
    }

    /**
     * @return array<string, array{old: mixed, new: mixed}>
     */
    public function getChangedFields(): array
    {
        return $this->changedFields;
    }

    public function getUserId(): ?string
    {
        return $this->userId;
    }
}
