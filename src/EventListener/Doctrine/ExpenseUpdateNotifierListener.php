<?php

namespace App\EventListener\Doctrine; // O la ubicación que prefieras para tus listeners de Doctrine

use App\Entity\Expense;
use App\Event\Expense\ExpenseWasUpdated;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
// CAMBIO: Importar la clase Security correcta del SecurityBundle
use Symfony\Bundle\SecurityBundle\Security; // Para obtener el usuario actual
use Symfony\Component\Uid\Uuid;

#[AsEntityListener(event: Events::preUpdate, method: 'onExpensePreUpdate', entity: Expense::class)]
class ExpenseUpdateNotifierListener // O un nombre más específico si solo notifica este evento
{
    private ?string $currentUserIdString = null;

    public function __construct(
        private readonly EventDispatcherInterface $eventDispatcher,
        // CAMBIO: Usar el type-hint correcto para el servicio Security
        private readonly Security $security
    ) {
        $currentUser = $this->security->getUser();
        if ($currentUser instanceof \App\Entity\User) { // Ajusta a tu clase User
            $this->currentUserIdString = $currentUser->getId(); // Asumiendo que User ID es Uuid
        } elseif ($currentUser) {
            // Fallback si no es tu entidad User pero implementa alguna forma de obtener un ID
            $this->currentUserIdString = (string) $currentUser->getUserIdentifier();
        }
    }

    public function onExpensePreUpdate(Expense $expense, PreUpdateEventArgs $eventArgs): void
    {
        $changedData = [];
        foreach ($eventArgs->getEntityChangeSet() as $fieldName => $values) {
            // $values[0] es el valor antiguo, $values[1] es el nuevo valor
            $changedData[$fieldName] = [
                'old' => $this->normalizeValueForEvent($values[0]),
                'new' => $this->normalizeValueForEvent($values[1]),
            ];
        }

        if (empty($changedData)) {
            return; // No hay cambios que registrar y despachar
        }

        $expenseId = $expense->id();
        $eventId = Uuid::v4()->toRfc4122();
        $occurredOn = (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM);

        $updateEvent = new ExpenseWasUpdated(
            $expenseId instanceof Uuid ? $expenseId->toRfc4122() : (string) $expenseId,
            $changedData,
            $this->currentUserIdString,
            $eventId,
            $occurredOn
        );

        $this->eventDispatcher->dispatch($updateEvent, ExpenseWasUpdated::eventName());
    }

    private function normalizeValueForEvent(mixed $value): mixed
    {
        if ($value instanceof \DateTimeInterface) {
            return $value->format(\DateTimeInterface::ATOM);
        }
        if ($value instanceof Uuid) {
            return $value->toRfc4122();
        }
        if (is_object($value) && method_exists($value, 'getId')) {
            // Para entidades relacionadas, podrías querer loguear su ID y clase
            $relatedId = $value->getId();
            return [
                '_class' => get_class($value),
                'id' => $relatedId instanceof Uuid ? $relatedId->toRfc4122() : (string) $relatedId,
            ];
        }
        if (is_object($value)) {
            // Fallback para otros objetos
            return get_class($value);
        }
        // Podrías añadir más normalización para arrays, etc.
        return $value;
    }
}
