<?php

declare(strict_types=1);

namespace App\Infrastructure\Projection;

use App\Domain\Event\DomainEventInterface;
use Psr\Log\LoggerInterface; // Optional: for logging dispatch activity or errors

class ProjectionManager
{
    /** @var ProjectorInterface[] */
    private array $projectors = [];
    private ?LoggerInterface $logger;

    /**
     * @param ProjectorInterface[] $projectors
     * @param LoggerInterface|null $logger
     */
    public function __construct(array $projectors, ?LoggerInterface $logger = null)
    {
        foreach ($projectors as $projector) {
            if (!$projector instanceof ProjectorInterface) {
                throw new \InvalidArgumentException('All projectors must implement ProjectorInterface.');
            }
            $this->projectors[] = $projector;
        }
        $this->logger = $logger;
    }

    public function addProjector(ProjectorInterface $projector): void
    {
        $this->projectors[] = $projector;
    }

    /**
     * Dispatches events to all suitable projectors.
     *
     * @param DomainEventInterface ...$events
     */
    public function dispatch(DomainEventInterface ...$events): void
    {
        foreach ($events as $event) {
            $this->logger?->debug('Dispatching event: ' . $event::eventType() . ' with ID ' . $event->getEventId());
            foreach ($this->projectors as $projector) {
                if ($projector->supports($event)) {
                    try {
                        $this->logger?->debug('Projector ' . get_class($projector) . ' supports event ' . $event::eventType());
                        $projector->project($event);
                    } catch (\Throwable $e) {
                        // Handle projection errors (e.g., log, retry queue, etc.)
                        // For now, we'll log and continue, but a real app might need more robust error handling.
                        $this->logger?->error(
                            'Error projecting event ' . $event::eventType() . ' with ' . get_class($projector) . ': ' . $e->getMessage(),
                            ['exception' => $e, 'event_id' => $event->getEventId()]
                        );
                        // Depending on strategy, you might re-throw or collect errors.
                    }
                }
            }
        }
    }
}
