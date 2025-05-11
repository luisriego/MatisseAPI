<?php

declare(strict_types=1);

namespace App\Event\Slip;

use App\Entity\Event;
use App\Repository\EventRepository;
use Symfony\Component\Uid\Uuid;

readonly class SlipsWasGeneratedHandler
{
    public function __construct(
        private readonly EventRepository $eventRepository,
    ) {
    }

    public function __invoke(SlipsWasGenerated $event): void
    {
        $eventEntity = new Event(
            Uuid::v4()->toRfc4122(),
            $event->aggregateId(),
            SlipsWasGenerated::eventName(),
            $event->toPrimitives()
        );

        $this->eventRepository->save($eventEntity, true);
    }
}
