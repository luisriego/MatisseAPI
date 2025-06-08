<?php

declare(strict_types=1);

namespace App\Domain\Event;

use App\Domain\ValueObject\Money;
use App\Domain\ValueObject\TransactionId; // For paymentId
use App\Domain\ValueObject\UnitId;
use App\Domain\ValueObject\Currency; // For fromPayload
use DateTimeImmutable;
use InvalidArgumentException;
use Ramsey\Uuid\Uuid;

final class PaymentReceivedOnUnitLedgerEvent implements DomainEventInterface
{
    private string $eventId;
    private UnitId $aggregateId; // UnitId
    private DateTimeImmutable $occurredOn;

    private Money $amount;
    private TransactionId $paymentId; // Using TransactionId to track the payment reference
    private DateTimeImmutable $paymentDate;
    private string $paymentMethod;

    private function __construct(
        string $eventId,
        UnitId $aggregateId,
        DateTimeImmutable $occurredOn, // This is when the event was recorded
        Money $amount,
        TransactionId $paymentId,
        DateTimeImmutable $paymentDate, // This is the actual date of payment
        string $paymentMethod
    ) {
        $this->eventId = $eventId;
        $this->aggregateId = $aggregateId;
        $this->occurredOn = $occurredOn;
        $this->amount = $amount;
        $this->paymentId = $paymentId;
        $this->paymentDate = $paymentDate;
        $this->paymentMethod = $paymentMethod;
    }

    public static function create(
        UnitId $unitId,
        Money $amount,
        TransactionId $paymentId,
        DateTimeImmutable $paymentDate,
        string $paymentMethod
    ): self {
        return new self(
            Uuid::uuid4()->toString(),
            $unitId,
            new DateTimeImmutable(), // occurredOn
            $amount,
            $paymentId,
            $paymentDate,
            $paymentMethod
        );
    }

    public function getEventId(): string
    {
        return $this->eventId;
    }

    public function getAggregateId(): string
    {
        return $this->aggregateId->toString();
    }

    public function getAggregateType(): string
    {
        return 'UnitLedgerAccount';
    }

    public static function eventType(): string
    {
        return 'PaymentReceivedOnUnitLedger';
    }

    public function getOccurredOn(): DateTimeImmutable
    {
        return $this->occurredOn;
    }

    public function getAmount(): Money
    {
        return $this->amount;
    }

    public function getPaymentId(): TransactionId
    {
        return $this->paymentId;
    }

    public function getPaymentDate(): DateTimeImmutable
    {
        return $this->paymentDate;
    }

    public function getPaymentMethod(): string
    {
        return $this->paymentMethod;
    }

    public function toPayload(): array
    {
        return [
            'amountCents' => $this->amount->getAmount(),
            'currencyCode' => $this->amount->getCurrency()->getCode(),
            'paymentId' => $this->paymentId->toString(),
            'paymentDate' => $this->paymentDate->format('Y-m-d'),
            'paymentMethod' => $this->paymentMethod,
        ];
    }

    public static function fromPayload(
        string $eventId,
        string $aggregateId, // This is the UnitId
        DateTimeImmutable $occurredOn,
        array $payload
    ): self {
        if (!isset(
            $payload['amountCents'],
            $payload['currencyCode'],
            $payload['paymentId'],
            $payload['paymentDate'],
            $payload['paymentMethod']
        )) {
            throw new InvalidArgumentException('Payload for PaymentReceivedOnUnitLedgerEvent is missing required fields.');
        }
        return new self(
            $eventId,
            new UnitId($aggregateId),
            $occurredOn,
            new Money((int)$payload['amountCents'], new Currency($payload['currencyCode'])),
            new TransactionId($payload['paymentId']),
            new DateTimeImmutable($payload['paymentDate']),
            $payload['paymentMethod']
        );
    }
}
