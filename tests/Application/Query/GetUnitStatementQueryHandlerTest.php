<?php

declare(strict_types=1);

namespace Tests\Application\Query;

use App\Application\DTO\UnitLedgerAccountDetailsDTO;
use App\Application\DTO\UnitStatementDTO;
use App\Application\Query\GetUnitStatementQuery;
use App\Application\Query\GetUnitStatementQueryHandler;
use PDO;
use PDOStatement;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use DateTimeImmutable; // For creating test data

final class GetUnitStatementQueryHandlerTest extends TestCase
{
    /** @var PDO&MockObject */
    private $pdoMock;
    private GetUnitStatementQueryHandler $handler;

    protected function setUp(): void
    {
        $this->pdoMock = $this->createMock(PDO::class);
        $this->handler = new GetUnitStatementQueryHandler($this->pdoMock);
    }

    public function testHandleSuccess(): void
    {
        $unitId = 'a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11';
        $query = new GetUnitStatementQuery($unitId);

        // Mock for balance
        $balanceStmtMock = $this->createMock(PDOStatement::class);
        $balanceData = [
            'unit_id' => $unitId,
            'balance_amount_cents' => 5000,
            'balance_currency_code' => 'USD',
            'last_updated_at' => (new DateTimeImmutable())->format(\DateTimeInterface::ATOM)
        ];
        $balanceStmtMock->method('execute')->with([':unit_id' => $unitId]);
        $balanceStmtMock->method('fetch')->with(PDO::FETCH_ASSOC)->willReturn($balanceData);

        // Mock for fees
        $feesStmtMock = $this->createMock(PDOStatement::class);
        $feesData = [
            [
                'id' => 'f0eebc99-9c0b-4ef8-bb6d-6bb9bd380a1f',
                'fee_item_id' => 'item_123',
                'description' => 'Monthly Fee',
                'amount_cents' => 7500,
                'currency_code' => 'USD',
                'due_date' => '2024-03-01',
                'issued_at' => (new DateTimeImmutable('2024-02-01'))->format(\DateTimeInterface::ATOM),
                'status' => 'PENDING'
            ]
        ];
        $feesStmtMock->method('execute')->with([':unit_id' => $unitId]);
        // Configure fetch to return rows one by one, then false
        $feesStmtMock->method('fetch')->with(PDO::FETCH_ASSOC)
            ->will($this->onConsecutiveCalls(...array_merge($feesData, [false])));


        // Mock for payments
        $paymentsStmtMock = $this->createMock(PDOStatement::class);
        $paymentsData = [
            [
                'id' => 'p0eebc99-9c0b-4ef8-bb6d-6bb9bd380a1p',
                'amount_cents' => 2500,
                'currency_code' => 'USD',
                'payment_date' => '2024-02-15',
                'payment_method' => 'Card',
                'received_at' => (new DateTimeImmutable('2024-02-15'))->format(\DateTimeInterface::ATOM)
            ]
        ];
        $paymentsStmtMock->method('execute')->with([':unit_id' => $unitId]);
        $paymentsStmtMock->method('fetch')->with(PDO::FETCH_ASSOC)
            ->will($this->onConsecutiveCalls(...array_merge($paymentsData, [false])));

        // Configure PDO mock to return specific statement mocks
        $this->pdoMock->method('prepare')
            ->willReturnCallback(function (string $sql) use ($balanceStmtMock, $feesStmtMock, $paymentsStmtMock) {
                if (str_contains($sql, 'FROM unit_ledger_accounts')) {
                    return $balanceStmtMock;
                }
                if (str_contains($sql, 'FROM fees_issued')) {
                    return $feesStmtMock;
                }
                if (str_contains($sql, 'FROM payments_received')) {
                    return $paymentsStmtMock;
                }
                $this->fail("Unexpected SQL query prepared in GetUnitStatementQueryHandlerTest: " . $sql);
            });

        $result = $this->handler->handle($query);

        $this->assertInstanceOf(UnitStatementDTO::class, $result);
        $this->assertEquals($unitId, $result->unitId);
        $this->assertInstanceOf(UnitLedgerAccountDetailsDTO::class, $result->currentBalance);
        $this->assertEquals(5000, $result->currentBalance->balanceAmountCents);
        $this->assertCount(1, $result->feesIssued);
        $this->assertEquals('Monthly Fee', $result->feesIssued[0]->description);
        $this->assertCount(1, $result->paymentsReceived);
        $this->assertEquals(2500, $result->paymentsReceived[0]->amountCents);
    }

    public function testHandleUnitLedgerNotFoundThrowsException(): void
    {
        $unitId = 'non_existent_unit_id';
        $query = new GetUnitStatementQuery($unitId);

        $balanceStmtMock = $this->createMock(PDOStatement::class);
        $balanceStmtMock->method('execute')->with([':unit_id' => $unitId]);
        $balanceStmtMock->method('fetch')->with(PDO::FETCH_ASSOC)->willReturn(false); // No balance record

        $this->pdoMock->method('prepare')->willReturn($balanceStmtMock);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage("Unit ledger account for Unit ID {$unitId} not found.");
        $this->handler->handle($query);
    }
}
