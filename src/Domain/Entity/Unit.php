<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use App\Domain\Model\AggregateRoot;
use App\Domain\ValueObject\CondominiumId;
use App\Domain\ValueObject\OwnerId;
use App\Domain\ValueObject\UnitId;
use App\Domain\Event\UnitCreatedEvent;
use App\Domain\Event\OwnerAssignedToUnitEvent;
use App\Domain\Event\OwnerRemovedFromUnitEvent;

class Unit extends AggregateRoot // Removed final
{
    private ?UnitId $id = null;
    private ?CondominiumId $condominiumId = null;
    private string $identifier = '';
    private ?OwnerId $ownerId = null;

    public static function createNew(UnitId $id, CondominiumId $condominiumId, string $identifier): self
    {
        $unit = new self();
        $unit->id = $id;
        $unit->condominiumId = $condominiumId;
        $unit->identifier = $identifier;
        $unit->ownerId = null;
        $unit->recordEvent(UnitCreatedEvent::create($id, $condominiumId, $identifier));
        return $unit;
    }

    public function __construct()
    {
        // For reconstitution
    }

    protected function apply(\App\Domain\Event\DomainEventInterface $event): void
    {
        match ($event::eventType()) {
            UnitCreatedEvent::eventType() => $this->applyUnitCreated($event),
            OwnerAssignedToUnitEvent::eventType() => $this->applyOwnerAssignedToUnit($event),
            OwnerRemovedFromUnitEvent::eventType() => $this->applyOwnerRemovedFromUnit($event),
            default => throw new \LogicException("Cannot apply unknown event " . $event::eventType())
        };
    }

    private function applyUnitCreated(UnitCreatedEvent $event): void
    {
        $this->id = new UnitId($event->getAggregateId());
        $this->condominiumId = $event->getCondominiumId();
        $this->identifier = $event->getIdentifier();
        $this->ownerId = null;
    }

    private function applyOwnerAssignedToUnit(OwnerAssignedToUnitEvent $event): void
    {
        $this->ownerId = $event->getOwnerId();
    }

    private function applyOwnerRemovedFromUnit(OwnerRemovedFromUnitEvent $event): void
    {
        $this->ownerId = null;
    }

    public function getId(): UnitId
    {
        return $this->id;
    }

    public function getCondominiumId(): CondominiumId
    {
        return $this->condominiumId;
    }

    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    public function getOwnerId(): ?OwnerId
    {
        return $this->ownerId;
    }

    public function assignOwner(OwnerId $newOwnerId): void
    {
        if ($this->id === null) {
            throw new \LogicException("Cannot assign owner to a unit that has not been initialized.");
        }
        if ($this->ownerId !== null && $this->ownerId->equals($newOwnerId)) {
            return; // Already assigned to this owner
        }
        $this->recordEvent(OwnerAssignedToUnitEvent::create($this->id, $newOwnerId));
        $this->ownerId = $newOwnerId; // Apply state change directly
    }

    public function removeOwner(): void
    {
        if ($this->id === null) {
            throw new \LogicException("Cannot remove owner from a unit that has not been initialized.");
        }
        if ($this->ownerId === null) {
            return; // No owner to remove
        }
        $oldOwnerId = $this->ownerId;
        $this->recordEvent(OwnerRemovedFromUnitEvent::create($this->id, $oldOwnerId));
        $this->ownerId = null; // Apply state change directly
    }
}
