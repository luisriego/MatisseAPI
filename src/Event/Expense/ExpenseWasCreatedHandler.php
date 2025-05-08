<?php

declare(strict_types=1);

namespace App\Event\Expense;

use App\Entity\Event;
use App\Repository\EventRepository;
use Symfony\Component\Uid\Uuid;

readonly class ExpenseWasCreatedHandler
{
    public function __construct(
        private EventRepository $eventRepository,
    ) {}

    public function __invoke(ExpenseWasCreated $event): void
    {
        $eventEntity = new Event(
            Uuid::v4()->toRfc4122(),
            'CONDO',
            ExpenseWasCreated::eventName(),
            $event->toPrimitives()
        );

        $this->eventRepository->save($eventEntity, true);
    }
}