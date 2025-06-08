<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use App\Domain\Model\AggregateRoot;
use App\Domain\ValueObject\OwnerId;
use App\Domain\Event\OwnerCreatedEvent;
use App\Domain\Event\OwnerContactInfoUpdatedEvent;

class Owner extends AggregateRoot // Removed final
{
    private ?OwnerId $id = null;
    private string $name = '';
    private string $email = '';
    private string $phoneNumber = '';

    public static function createNew(OwnerId $id, string $name, string $email, string $phoneNumber): self
    {
        $owner = new self();
        $owner->id = $id;
        $owner->name = $name;
        $owner->email = $email;
        $owner->phoneNumber = $phoneNumber;
        $owner->recordEvent(OwnerCreatedEvent::create($id, $name, $email, $phoneNumber));
        return $owner;
    }

    public function __construct()
    {
        // For reconstitution
    }

    protected function apply(\App\Domain\Event\DomainEventInterface $event): void
    {
        match ($event::eventType()) {
            OwnerCreatedEvent::eventType() => $this->applyOwnerCreated($event),
            OwnerContactInfoUpdatedEvent::eventType() => $this->applyOwnerContactInfoUpdated($event),
            default => throw new \LogicException("Cannot apply unknown event " . $event::eventType())
        };
    }

    private function applyOwnerCreated(OwnerCreatedEvent $event): void
    {
        $this->id = new OwnerId($event->getAggregateId());
        $this->name = $event->getName();
        $this->email = $event->getEmail();
        $this->phoneNumber = $event->getPhoneNumber();
    }

    private function applyOwnerContactInfoUpdated(OwnerContactInfoUpdatedEvent $event): void
    {
        $this->name = $event->getNewName();
        $this->email = $event->getNewEmail();
        $this->phoneNumber = $event->getNewPhoneNumber();
    }

    public function getId(): OwnerId
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getPhoneNumber(): string
    {
        return $this->phoneNumber;
    }

    public function updateContactInfo(string $newName, string $newEmail, string $newPhoneNumber): void
    {
        if ($this->id === null) {
            throw new \LogicException("Cannot update contact info for an owner that has not been initialized.");
        }

        $changed = false;
        if ($this->name !== $newName && !empty($newName)) {
            $changed = true;
        }
        if ($this->email !== $newEmail && !empty($newEmail)) { // Basic check, real email validation is more complex
            $changed = true;
        }
        if ($this->phoneNumber !== $newPhoneNumber && !empty($newPhoneNumber)) {
            $changed = true;
        }

        if ($changed) {
            $this->recordEvent(OwnerContactInfoUpdatedEvent::create($this->id, $newName, $newEmail, $newPhoneNumber));
            // Apply changes directly
            $this->name = $newName;
            $this->email = $newEmail;
            $this->phoneNumber = $newPhoneNumber;
        }
    }
}
