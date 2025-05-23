<?php

declare(strict_types=1);

namespace App\Event\Slip;

use App\Bus\DomainEvent;

readonly class SlipsWasGenerated extends DomainEvent
{
    public function __construct(
        string $aggregateId,
        public array $slips,
        string $eventId,
        string $occurredOn)
    {
        parent::__construct($aggregateId, $eventId, $occurredOn);
    }

    public static function fromPrimitives(string $aggregateId, array $body, string $eventId, string $occurredOn,): DomainEvent
    {
        return new self($aggregateId, $body['slips'], $eventId, $occurredOn);
    }

    public static function eventName(): string
    {
        return 'slip.was_generated';
    }

    public function toPrimitives(): array
    {
        return [
            'slips' => $this->slips,
        ];
    }
}
