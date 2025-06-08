<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use App\Domain\Model\AggregateRoot;
use App\Domain\ValueObject\CondominiumId;
use App\Domain\ValueObject\FeeItemId;
use App\Domain\ValueObject\Money;
use App\Domain\Event\FeeItemCreatedEvent;
// Placeholder for other events like FeeItemDescriptionChangedEvent, FeeItemDefaultAmountChangedEvent

class FeeItem extends AggregateRoot // Removed final
{
    private ?FeeItemId $id = null;
    private ?CondominiumId $condominiumId = null;
    private string $description = '';
    private ?Money $defaultAmount = null;

    public static function createNew(
        FeeItemId $id,
        CondominiumId $condominiumId,
        string $description,
        Money $defaultAmount
    ): self {
        $item = new self();
        $item->id = $id;
        $item->condominiumId = $condominiumId;
        $item->description = $description;
        $item->defaultAmount = $defaultAmount;
        $item->recordEvent(FeeItemCreatedEvent::create($id, $condominiumId, $description, $defaultAmount));
        return $item;
    }

    public function __construct()
    {
        // For reconstitution
    }

    protected function apply(\App\Domain\Event\DomainEventInterface $event): void
    {
        if ($event instanceof FeeItemCreatedEvent) {
            $this->applyFeeItemCreated($event);
        }
        // Add other event applications like FeeItemDescriptionChangedEvent if defined
        // elseif ($event instanceof FeeItemDescriptionChangedEvent) { ... }
        else {
            throw new \LogicException("Cannot apply unknown event " . $event::eventType() . " to FeeItem.");
        }
    }

    private function applyFeeItemCreated(FeeItemCreatedEvent $event): void
    {
        $this->id = new FeeItemId($event->getAggregateId());
        $this->condominiumId = $event->getCondominiumId();
        $this->description = $event->getDescription();
        $this->defaultAmount = $event->getDefaultAmount();
    }

    public function getId(): FeeItemId
    {
        return $this->id;
    }

    public function getCondominiumId(): ?CondominiumId
    {
        return $this->condominiumId;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getDefaultAmount(): ?Money
    {
        return $this->defaultAmount;
    }

    // Methods to update description or amount would go here, recording events
    public function changeDescription(string $newDescription): void
    {
        if ($this->id === null) {
            throw new \LogicException("FeeItem must be initialized before changing description.");
        }
        if ($this->description === $newDescription || empty($newDescription)) {
            return;
        }
        // $oldDescription = $this->description; // Needed if event stores old value
        // $this->recordEvent(FeeItemDescriptionChangedEvent::create($this->id, $newDescription, $oldDescription));
        $this->description = $newDescription; // Direct state change
    }

    public function changeDefaultAmount(Money $newAmount): void
    {
        if ($this->id === null || $this->defaultAmount === null) {
            throw new \LogicException("FeeItem must be initialized before changing default amount.");
        }
        if ($this->defaultAmount->equals($newAmount)) {
            return;
        }
        if (!$this->defaultAmount->getCurrency()->equals($newAmount->getCurrency())) {
            throw new \InvalidArgumentException('Cannot change default amount to a different currency for FeeItem.');
        }
        // $oldAmount = $this->defaultAmount; // Needed if event stores old value
        // $this->recordEvent(FeeItemDefaultAmountChangedEvent::create($this->id, $newAmount, $oldAmount));
        $this->defaultAmount = $newAmount; // Direct state change
    }
}
