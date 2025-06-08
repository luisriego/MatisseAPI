<?php

declare(strict_types=1);

namespace App\Infrastructure\Projection;

use App\Domain\Event\DomainEventInterface;

interface ProjectorInterface
{
    /**
     * Checks if this projector can handle the given event.
     */
    public function supports(DomainEventInterface $event): bool;

    /**
     * Projects the event to the read model.
     *
     * @throws \Exception if projection fails.
     */
    public function project(DomainEventInterface $event): void;
}
