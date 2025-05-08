<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\EventRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EventRepository::class)]
class Event
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36, options: ['fixed' => true])]
    private string $id;

    #[ORM\Column(type: 'string', length: 36)]
    private ?string $residence = null;

    #[ORM\Column(type: 'string', length: 50)]
    private ?string $eventType = null;

    #[ORM\Column(nullable: true)]
    private ?array $payload = null;

    #[ORM\Column]
    private \DateTimeImmutable $occurredAt;

    public function __construct(
        string $id,
        string $residence,
        string $eventType,
        ?array $payload = null)
    {
        $this->id = $id;
        $this->residence = $residence;
        $this->eventType = $eventType;
        $this->payload = $payload;
        $this->occurredAt = new \DateTimeImmutable();
    }

    public function id(): ?string
    {
        return $this->id;
    }

    public function residence(): ?string
    {
        return $this->residence;
    }

    public function eventType(): ?string
    {
        return $this->eventType;
    }

    public function payload(): ?array
    {
        return $this->payload;
    }

    public function occurredAt(): ?\DateTimeImmutable
    {
        return $this->occurredAt;
    }
}
