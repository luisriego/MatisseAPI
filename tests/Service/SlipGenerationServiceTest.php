<?php

namespace App\Tests\Service;

use App\Service\SlipGenerationService;
use App\Repository\ResidentRepository;
use App\Repository\RecurringExpenseRepository;
use App\Repository\SlipRepository;
use App\Service\SlipAmountCalculatorService;
use App\Service\DueDateCalculatorService;
use App\Entity\Resident;
use App\Entity\RecurringExpense;
use App\Entity\Slip;
use App\Entity\ExpenseType; // Needed for RecurringExpense
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Doctrine\Common\Collections\ArrayCollection;

class SlipGenerationServiceTest extends TestCase
{
    private SlipGenerationService $slipGenerationService;
    private ResidentRepository $residentRepository;
    private RecurringExpenseRepository $recurringExpenseRepository;
    private SlipRepository $slipRepository;
    private SlipAmountCalculatorService $slipAmountCalculatorService;
    private DueDateCalculatorService $dueDateCalculatorService;
    private EntityManagerInterface $entityManager;
    private LoggerInterface $logger;

    protected function setUp(): void
    {
        $this->residentRepository = $this->createMock(ResidentRepository::class);
        $this->recurringExpenseRepository = $this->createMock(RecurringExpenseRepository::class);
        $this->slipRepository = $this->createMock(SlipRepository::class);
        $this->slipAmountCalculatorService = $this->createMock(SlipAmountCalculatorService::class);
        $this->dueDateCalculatorService = $this->createMock(DueDateCalculatorService::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->slipGenerationService = new SlipGenerationService(
            $this->residentRepository,
            $this->recurringExpenseRepository,
            $this->slipRepository,
            $this->slipAmountCalculatorService,
            $this->dueDateCalculatorService,
            $this->entityManager,
            $this->logger
        );
    }

    public function testGenerateSlipsForMonth(): void
    {
        $year = 2023;
        $month = 12;

        // Mock Residents
        $resident1 = new Resident();
        $resident1->setName('Resident 1');
        $resident2 = new Resident();
        $resident2->setName('Resident 2');
        $activeResidents = new ArrayCollection([$resident1, $resident2]);
        $this->residentRepository->expects($this->once())
            ->method('findAllActive')
            ->willReturn($activeResidents);

        // Mock Recurring Expenses
        $expenseType = new ExpenseType();
        $expenseType->setName('Test Type');
        $recurringExpense1 = new RecurringExpense();
        $recurringExpense1->setName('Expense 1')->setAmount(100)->setExpenseType($expenseType)->setDayOfMonth(1);
        $recurringExpense2 = new RecurringExpense();
        $recurringExpense2->setName('Expense 2')->setAmount(50)->setExpenseType($expenseType)->setDayOfMonth(1);
        $activeRecurringExpenses = new ArrayCollection([$recurringExpense1, $recurringExpense2]);
        $this->recurringExpenseRepository->expects($this->once())
            ->method('findAllActive')
            ->willReturn($activeRecurringExpenses);
            
        // Mock SlipAmountCalculatorService
        // Assuming it calculates total amount per resident based on all recurring expenses
        // For simplicity, let's say each resident pays the sum of all recurring expenses (100 + 50 = 150)
        $this->slipAmountCalculatorService->expects($this->exactly(count($activeResidents)))
            ->method('calculateTotalAmount')
            ->with($this->isInstanceOf(Resident::class), $activeRecurringExpenses, $year, $month)
            ->willReturn(150.00);

        // Mock DueDateCalculatorService
        $dueDate = new \DateTimeImmutable("{$year}-{$month}-10");
        $this->dueDateCalculatorService->expects($this->exactly(count($activeResidents)))
            ->method('calculateDueDate')
            ->with($year, $month)
            ->willReturn($dueDate);
            
        // Expect persist and flush to be called for each generated slip
        $this->entityManager->expects($this->exactly(count($activeResidents)))
            ->method('persist')
            ->with($this->isInstanceOf(Slip::class));
        $this->entityManager->expects($this->once()) // Flush is called once after all persists
            ->method('flush');
            
        // Mock SlipRepository to prevent conflicts if checking for existing slips
        $this->slipRepository->expects($this->any())
            ->method('findOneBy') // Adjust if your service uses a different method to check for existing slips
            ->willReturn(null); // Simulate no existing slips

        // Call the method to test
        $this->slipGenerationService->generateSlipsForMonth($year, $month);

        // Assertions are implicitly handled by the ->expects() calls on mocks
        // If persist was called the expected number of times with Slip instances, the test is on the right track.
        // No direct assertion on return value as the method is void.
    }
    
    public function testGenerateSlipsForMonth_NoActiveResidents(): void
    {
        $this->residentRepository->expects($this->once())
            ->method('findAllActive')
            ->willReturn(new ArrayCollection());

        $this->recurringExpenseRepository->expects($this->never()) // Should not be called if no residents
            ->method('findAllActive');
        
        $this->entityManager->expects($this->never())
            ->method('persist');
        $this->entityManager->expects($this->never())
            ->method('flush');

        $this->logger->expects($this->once())
            ->method('info')
            ->with('No active residents found. No slips generated.');

        $this->slipGenerationService->generateSlipsForMonth(2023, 11);
    }

    public function testGenerateSlipsForMonth_NoRecurringExpenses(): void
    {
        $resident1 = new Resident();
        $resident1->setName('Resident 1');
        $activeResidents = new ArrayCollection([$resident1]);
        $this->residentRepository->expects($this->once())
            ->method('findAllActive')
            ->willReturn($activeResidents);

        $this->recurringExpenseRepository->expects($this->once())
            ->method('findAllActive')
            ->willReturn(new ArrayCollection());
        
        $this->entityManager->expects($this->never())
            ->method('persist');
        $this->entityManager->expects($this->never())
            ->method('flush');
        
        $this->logger->expects($this->once())
            ->method('info')
            ->with('No active recurring expenses found. No slips generated.');

        $this->slipGenerationService->generateSlipsForMonth(2023, 10);
    }
}
