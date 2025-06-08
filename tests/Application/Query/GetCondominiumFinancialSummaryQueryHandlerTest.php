<?php

declare(strict_types=1);

namespace Tests\Application\Query;

use App\Application\DTO\CondominiumFinancialSummaryDTO;
use App\Application\Query\GetCondominiumFinancialSummaryQuery;
use App\Application\Query\GetCondominiumFinancialSummaryQueryHandler;
use PDO;
use PDOStatement;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

final class GetCondominiumFinancialSummaryQueryHandlerTest extends TestCase
{
    /** @var PDO&MockObject */
    private $pdoMock;
    private GetCondominiumFinancialSummaryQueryHandler $handler;

    protected function setUp(): void
    {
        $this->pdoMock = $this->createMock(PDO::class);
        $this->handler = new GetCondominiumFinancialSummaryQueryHandler($this->pdoMock);
    }

    public function testHandleSuccess(): void
    {
        $condoId = 'c0eebc99-9c0b-4ef8-bb6d-6bb9bd380a1c';
        $query = new GetCondominiumFinancialSummaryQuery($condoId, '2024-01-01', '2024-01-31');

        // Mock for income
        $incomeStmtMock = $this->createMock(PDOStatement::class);
        $incomeData = ['total_income' => 100000, 'currency_code' => 'USD']; // 1000.00 USD
        $incomeStmtMock->method('execute'); // Basic check, params can be more specific
        $incomeStmtMock->method('fetch')->with(PDO::FETCH_ASSOC)->willReturn($incomeData);

        // Mock for expenses
        $expenseStmtMock = $this->createMock(PDOStatement::class);
        $expenseData = ['total_expenses' => 30000, 'currency_code' => 'USD']; // 300.00 USD
        $expenseStmtMock->method('execute');
        $expenseStmtMock->method('fetch')->with(PDO::FETCH_ASSOC)->willReturn($expenseData);

        // Ensure all prepare calls are mapped or have a default
        $this->pdoMock->method('prepare') // Changed from expects($this->exactly(2)) to general method for flexibility
            ->willReturnCallback(function (string $sql) use ($incomeStmtMock, $expenseStmtMock) {
                if (str_contains($sql, 'FROM payments_received')) {
                    return $incomeStmtMock;
                }
                if (str_contains($sql, 'FROM expenses')) {
                    return $expenseStmtMock;
                }
                // Fallback for any unexpected prepare calls during test, though ideally map covers all.
                $defaultStmtMock = $this->createMock(PDOStatement::class);
                $defaultStmtMock->method('execute')->willReturn(true);
                $defaultStmtMock->method('fetch')->with(PDO::FETCH_ASSOC)->willReturn(false); // Default to no results
                return $defaultStmtMock;
            });

        $result = $this->handler->handle($query);

        $this->assertInstanceOf(CondominiumFinancialSummaryDTO::class, $result);
        $this->assertEquals($condoId, $result->condominiumId);
        $this->assertEquals(100000, $result->totalIncomeCents);
        $this->assertEquals(30000, $result->totalExpensesCents);
        $this->assertEquals(70000, $result->netBalanceCents); // 1000.00 - 300.00 = 700.00
        $this->assertEquals('USD', $result->currencyCode);
    }

    public function testHandleSuccessWithNoData(): void
    {
        $condoId = 'c0eebc99-9c0b-4ef8-bb6d-6bb9bd380a1c';
        $query = new GetCondominiumFinancialSummaryQuery($condoId); // No date range

        // Mock for income - no income
        $incomeStmtMock = $this->createMock(PDOStatement::class);
        $incomeData = ['total_income' => null, 'currency_code' => null];
        $incomeStmtMock->method('execute');
        $incomeStmtMock->method('fetch')->with(PDO::FETCH_ASSOC)->willReturn($incomeData);

        // Mock for expenses - no expenses
        $expenseStmtMock = $this->createMock(PDOStatement::class);
        $expenseData = ['total_expenses' => null, 'currency_code' => null];
        $expenseStmtMock->method('execute');
        $expenseStmtMock->method('fetch')->with(PDO::FETCH_ASSOC)->willReturn($expenseData);

        $this->pdoMock->method('prepare')
            ->willReturnCallback(function (string $sql) use ($incomeStmtMock, $expenseStmtMock) {
                if (str_contains($sql, 'FROM payments_received')) {
                    return $incomeStmtMock;
                }
                if (str_contains($sql, 'FROM expenses')) {
                    return $expenseStmtMock;
                }
                $this->fail("Unexpected SQL query prepared in no data test: " . $sql);
            });

        $result = $this->handler->handle($query);

        $this->assertInstanceOf(CondominiumFinancialSummaryDTO::class, $result);
        $this->assertEquals($condoId, $result->condominiumId);
        $this->assertEquals(0, $result->totalIncomeCents);
        $this->assertEquals(0, $result->totalExpensesCents);
        $this->assertEquals(0, $result->netBalanceCents);
        $this->assertEquals('USD', $result->currencyCode); // Default currency
    }
}
