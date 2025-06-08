<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use App\Domain\Model\AggregateRoot;
use App\Domain\ValueObject\CondominiumId;
use App\Domain\ValueObject\ExpenseCategoryId;
use App\Domain\Event\ExpenseCategoryCreatedEvent;
// Placeholder for ExpenseCategoryNameChangedEvent

class ExpenseCategory extends AggregateRoot // Removed final
{
    private ?ExpenseCategoryId $id = null;
    private ?CondominiumId $condominiumId = null;
    private string $name = '';

    public static function createNew(ExpenseCategoryId $id, CondominiumId $condominiumId, string $name): self
    {
        $category = new self();
        $category->id = $id;
        $category->condominiumId = $condominiumId;
        $category->name = $name;
        $category->recordEvent(ExpenseCategoryCreatedEvent::create($id, $condominiumId, $name));
        return $category;
    }

    public function __construct()
    {
        // For reconstitution
    }

    protected function apply(\App\Domain\Event\DomainEventInterface $event): void
    {
        if ($event instanceof ExpenseCategoryCreatedEvent) {
            $this->applyExpenseCategoryCreated($event);
        }
        // Add other event applications like ExpenseCategoryNameChangedEvent if defined
        // elseif ($event instanceof ExpenseCategoryNameChangedEvent) { ... }
        else {
            throw new \LogicException("Cannot apply unknown event " . $event::eventType() . " to ExpenseCategory.");
        }
    }

    private function applyExpenseCategoryCreated(ExpenseCategoryCreatedEvent $event): void
    {
        $this->id = new ExpenseCategoryId($event->getAggregateId());
        $this->condominiumId = $event->getCondominiumId();
        $this->name = $event->getName();
    }

    public function getId(): ExpenseCategoryId
    {
        return $this->id;
    }

    public function getCondominiumId(): ?CondominiumId
    {
        return $this->condominiumId;
    }

    public function getName(): string
    {
        return $this->name;
    }

    // Method to update name would go here, recording an event
    public function changeName(string $newName): void
    {
        if ($this->id === null) {
            throw new \LogicException("ExpenseCategory must be initialized before changing name.");
        }
        if ($this->name === $newName || empty($newName)) {
            return;
        }
        // $oldName = $this->name; // Needed if event stores old value
        // $this->recordEvent(ExpenseCategoryNameChangedEvent::create($this->id, $newName, $oldName));
        $this->name = $newName; // Direct state change
    }
}
