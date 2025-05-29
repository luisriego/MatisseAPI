<?php

namespace App\Tests\Command;

use App\Command\CondoGenerateMonthlySlipsCommand;
use App\Service\SlipGenerationService;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

class CondoGenerateMonthlySlipsCommandTest extends KernelTestCase
{
    private SlipGenerationService $slipGenerationServiceMock;

    protected function setUp(): void
    {
        parent::setUp();
        $this->slipGenerationServiceMock = $this->createMock(SlipGenerationService::class);
    }

    public function testExecute(): void
    {
        self::bootKernel();
        $application = new Application(self::$kernel);
        $application->add(new CondoGenerateMonthlySlipsCommand($this->slipGenerationServiceMock));

        $command = $application->find('condo:generate-monthly-slips');
        $commandTester = new CommandTester($command);

        $year = date('Y');
        $month = date('m');

        $this->slipGenerationServiceMock
            ->expects($this->once())
            ->method('generateSlipsForMonth')
            ->with((int)$year, (int)$month);

        $commandTester->execute([
            'year' => $year,
            'month' => $month,
        ]);

        $commandTester->assertCommandIsSuccessful();
        $output = $commandTester->getDisplay();
        $this->assertStringContainsString("Successfully generated slips for {$month}/{$year}", $output);
    }

    public function testExecuteWithInvalidDate(): void
    {
        self::bootKernel();
        $application = new Application(self::$kernel);
        $application->add(new CondoGenerateMonthlySlipsCommand($this->slipGenerationServiceMock));

        $command = $application->find('condo:generate-monthly-slips');
        $commandTester = new CommandTester($command);

        $this->slipGenerationServiceMock
            ->expects($this->never())
            ->method('generateSlipsForMonth');

        $commandTester->execute([
            'year' => 'invalid-year',
            'month' => 'invalid-month',
        ]);

        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('Invalid year or month provided.', $output);
        $this->assertSame(1, $commandTester->getStatusCode()); // Assert failure
    }
    
    public function testExecuteUsesCurrentMonthAndYearIfNotProvided(): void
    {
        self::bootKernel();
        $application = new Application(self::$kernel);
        $application->add(new CondoGenerateMonthlySlipsCommand($this->slipGenerationServiceMock));

        $command = $application->find('condo:generate-monthly-slips');
        $commandTester = new CommandTester($command);

        $currentYear = (int)date('Y');
        $currentMonth = (int)date('m');

        $this->slipGenerationServiceMock
            ->expects($this->once())
            ->method('generateSlipsForMonth')
            ->with($currentYear, $currentMonth);

        $commandTester->execute([]); // No arguments provided

        $commandTester->assertCommandIsSuccessful();
        $output = $commandTester->getDisplay();
        $this->assertStringContainsString("Successfully generated slips for {$currentMonth}/{$currentYear}", $output);
    }
}
