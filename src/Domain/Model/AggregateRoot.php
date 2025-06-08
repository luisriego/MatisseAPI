<?php

declare(strict_types=1);

namespace App\Domain\Model;

use App\Domain\Event\DomainEventInterface;

abstract class AggregateRoot
{
    /** @var DomainEventInterface[] */
    private array $domainEvents = [];

    protected function recordEvent(DomainEventInterface $event): void
    {
        $this->domainEvents[] = $event;
    }

    /** @return DomainEventInterface[] */
    public function pullDomainEvents(): array
    {
        $events = $this->domainEvents;
        $this->domainEvents = []; // Clear after pulling
        return $events;
    }

    /**
     * Applies an event to the aggregate, changing its state.
     * This method is typically called during reconstitution or directly after recording an event
     * if state is not deferred until event sourcing handlers run.
     * For this project, we apply changes directly in command methods AND record events,
     * so `apply` is primarily for reconstitution.
     */
    abstract protected function apply(DomainEventInterface $event): void;

    /**
     * Reconstitutes the aggregate from a history of domain events.
     *
     * @param DomainEventInterface ...$history A list of domain events.
     * @return static The reconstituted aggregate.
     * @throws \InvalidArgumentException If history is empty or first event is not a creation event.
     */
    public static function reconstituteFromHistory(DomainEventInterface ...$history): static
    {
        if (empty($history)) {
            throw new \InvalidArgumentException('Cannot reconstitute aggregate from empty history.');
        }

        // The first event usually creates the aggregate and sets its ID.
        // We need a way to instantiate the aggregate.
        // One common way is to use `new static()` and then apply events.
        // This requires the constructor to be callable without arguments, or
        // to have a specific constructor for reconstitution, or use reflection.

        // For simplicity, let's assume the constructor can be called to create a "blank"
        // aggregate or that the first event's apply method handles ID setting on a new static().
        // A more robust solution might involve reflection if constructor signatures are complex
        // or a dedicated static factory method on the aggregate for instantiation without events.

        // Let's assume the first event will correctly set the ID via its apply method.
        // This requires the aggregate to have a default constructor or a constructor
        // that can handle being called without the ID initially if the first event sets it.
        // This is a tricky part. Most event-sourced aggregates would have a constructor
        // that takes the ID, and the creation event would pass this ID to the constructor
        // when being replayed.

        // A common pattern for reconstitution:
        // 1. Create an empty instance (e.g. new static() if constructor allows, or reflection).
        // 2. Call apply for each event.
        // The constructor of the aggregate might need to be adapted to not record a "Created" event
        // when it's being reconstituted.

        // A simplified approach for now: Assume the first event in history is a "creation" type event.
        // The `apply` method for this creation event will set the ID and initial state.
        // This requires the specific aggregate's `apply` method for its creation event
        // to correctly initialize the aggregate, including its ID.

        $instance = new static(); // This assumes a constructor that allows this.
                                 // Or, a more complex instantiation is needed if constructor requires args.
                                 // For our entities, ID is usually a constructor arg.
                                 // This will be a challenge for current entity constructors.

        // Let's refine this: the `apply` method for the creation event MUST set the ID.
        // The constructor for an AggregateRoot being reconstituted should not re-record a creation event.
        // We can add a flag to the constructor or use a different instantiation path.

        // Alternative: use reflection to create instance without calling constructor, then apply properties.
        // Or, a static factory method for reconstitution on each aggregate.
        // For now, let's make `reconstituteFromHistory` on `AggregateRoot` a bit more generic
        // and expect concrete aggregates to provide a way to be instantiated for reconstitution.
        // The most straightforward is often `new static($idFromFirstEvent)` then apply rest.

        // Simplification: Assume the concrete Aggregate has a constructor that can be called,
        // and its `apply` method for the *CreationEvent* initializes the ID.
        // This is a common pattern. The constructor itself should NOT record an event when called
        // during reconstitution. This might mean having a flag or a separate reconstitution factory.

        // Let's assume the creation event's `apply` method handles ID.
        // And the aggregate constructor is modified not to record event if an ID is passed (implies reconstitution).

        $firstEvent = array_shift($history); // Get the first event

        // How to get ID from firstEvent to pass to constructor?
        // Need a generic way if we want `new static($id, ...)`
        // $aggregateId = $firstEvent->getAggregateId(); // This is string, need VO.

        // This part is highly dependent on specific aggregate constructor signatures.
        // A truly generic `reconstituteFromHistory` on `AggregateRoot` is hard without conventions.
        // We will assume the concrete aggregate's constructor is adaptable or its `apply` for creation event sets the ID.
        // For now, let's use `new static()` and the `apply` method of the creation event must set the ID.
        // This means constructors of aggregates like Condominium, Unit, Owner need to be callable without arguments
        // or have a mechanism to bypass event recording and initial state setting when being reconstituted.

        // Let's assume a protected constructor for AggregateRoot or a specific instantiation for reconstitution.
        // For now, we'll rely on the fact that our entities will have their ID set by their respective "Created" events' apply methods.

        $instance = new static(); // THIS IS THE PROBLEMATIC LINE without constructor changes.
                                  // We will need to adjust entity constructors.

        $instance->apply($firstEvent);
        foreach ($history as $event) {
            $instance->apply($event);
        }
        $instance->domainEvents = []; // Clear any events recorded during apply (shouldn't happen if apply only mutates state)
        return $instance;
    }
}
