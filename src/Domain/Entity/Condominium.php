<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use App\Domain\Model\AggregateRoot;
use App\Domain\ValueObject\Address;
use App\Domain\ValueObject\CondominiumId;
use App\Domain\ValueObject\UnitId;
use App\Domain\Event\CondominiumRegisteredEvent;
use App\Domain\Event\CondominiumRenamedEvent;
use App\Domain\Event\CondominiumAddressChangedEvent;
// UnitAddedToCondominiumEvent is not directly recorded here if Unit is an AR

class Condominium extends AggregateRoot // Removed final
{
    private ?CondominiumId $id = null; // Nullable to allow for reconstitution
    private string $name = '';
    private ?Address $address = null;
    /** @var UnitId[] */
    private array $unitIds = [];

    // Constructor for new aggregate creation
    public static function createNew(CondominiumId $id, string $name, Address $address): self
    {
        $condominium = new self(); // Call the basic constructor
        $condominium->id = $id;
        $condominium->name = $name;
        $condominium->address = $address;
        $condominium->unitIds = [];
        $condominium->recordEvent(CondominiumRegisteredEvent::create($id, $name, $address));
        return $condominium;
    }

    // Basic constructor for reconstitution (and used by createNew)
    public function __construct()
    {
        // Intentionally left blank or for minimal default initialization
        // State is applied via apply() method during reconstitution or set by createNew()
    }

    protected function apply(\App\Domain\Event\DomainEventInterface $event): void
    {
        match ($event::eventType()) {
            CondominiumRegisteredEvent::eventType() => $this->applyCondominiumRegistered($event),
            CondominiumRenamedEvent::eventType() => $this->applyCondominiumRenamed($event),
            CondominiumAddressChangedEvent::eventType() => $this->applyCondominiumAddressChanged($event),
            default => throw new \LogicException("Cannot apply unknown event " . $event::eventType())
        };
    }

    private function applyCondominiumRegistered(CondominiumRegisteredEvent $event): void
    {
        $this->id = new CondominiumId($event->getAggregateId());
        $this->name = $event->getName();
        $this->address = $event->getAddress();
        $this->unitIds = [];
    }

    private function applyCondominiumRenamed(CondominiumRenamedEvent $event): void
    {
        $this->name = $event->getNewName();
    }

    private function applyCondominiumAddressChanged(CondominiumAddressChangedEvent $event): void
    {
        $this->address = $event->getNewAddress();
    }

    public function getId(): CondominiumId
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getAddress(): Address
    {
        return $this->address;
    }

    /** @return UnitId[] */
    public function getUnitIds(): array
    {
        // Note: unitIds management via events (e.g., UnitAssociatedEvent) is not fully fleshed out here.
        // For reconstitution, this list would be populated by applying such events.
        // If addUnit directly modifies and Condominium is not event sourced for this list,
        // then this part of state is not purely from events.
        // For now, we assume addUnit is a command that would also record an event if this list was event sourced.
        return $this->unitIds;
    }

    // Command methods still record events for new changes
    public function addUnit(UnitId $unitId): void
    {
        foreach ($this->unitIds as $existingUnitId) {
            if ($existingUnitId->equals($unitId)) {
                return;
            }
        }
        $this->unitIds[] = $unitId; // Direct modification for now
        // If this were to be event sourced:
        // $this->recordEvent(UnitLinkedToCondominiumEvent::create($this->id, $unitId));
        // And then applyUnitLinkedToCondominium would add to $this->unitIds.
    }

    public function rename(string $newName): void
    {
        if ($this->id === null) {
            throw new \LogicException("Cannot rename a condominium that has not been initialized.");
        }
        if ($this->name === $newName || empty($newName)) {
            return;
        }
        $this->recordEvent(CondominiumRenamedEvent::create($this->id, $newName));
        $this->name = $newName; // Apply state change directly
    }

    public function changeAddress(Address $newAddress): void
    {
        if ($this->id === null) {
            throw new \LogicException("Cannot change address of a condominium that has not been initialized.");
        }
        if ($this->address !== null && $this->address->equals($newAddress)) {
            return;
        }
        $this->recordEvent(CondominiumAddressChangedEvent::create($this->id, $newAddress));
        $this->address = $newAddress; // Apply state change directly
    }
}
