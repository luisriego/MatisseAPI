<?php

declare(strict_types=1);

namespace App\Event\Income;

use App\Entity\Event;
use App\Repository\EventRepository;
use Symfony\Component\Uid\Uuid;

readonly class IncomeWasCreatedHandler
{
    public function __construct(
        private EventRepository $eventRepository,
    ) {}

    public function __invoke(IncomeWasCreated $event): void
    {
        $eventEntity = new Event(
            Uuid::v4()->toRfc4122(),
            $event->residentId,
            IncomeWasCreated::eventName(),
            $event->toPrimitives()
        );

        $this->eventRepository->save($eventEntity, true);
    }
}