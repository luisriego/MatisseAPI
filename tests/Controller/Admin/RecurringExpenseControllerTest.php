<?php

namespace App\Tests\Controller\Admin;

use App\Entity\RecurringExpense;
use App\Entity\User;
use App\Repository\RecurringExpenseRepository;
use App\Repository\UserRepository;
use App\Repository\ExpenseTypeRepository; // Required for creating a valid RecurringExpense
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class RecurringExpenseControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private ?RecurringExpenseRepository $recurringExpenseRepository;
    private ?UserRepository $userRepository;
    private ?ExpenseTypeRepository $expenseTypeRepository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = static::createClient();
        $this->recurringExpenseRepository = $this->client->getContainer()->get(RecurringExpenseRepository::class);
        $this->userRepository = $this->client->getContainer()->get(UserRepository::class);
        $this->expenseTypeRepository = $this->client->getContainer()->get(ExpenseTypeRepository::class); // Initialize ExpenseTypeRepository

        // Ensure admin user is logged in for all tests
        $adminUser = $this->userRepository->findOneByUsername('jane_admin');
        if ($adminUser) {
            $this->client->loginUser($adminUser);
        }
    }

    public function testIndex(): void
    {
        $this->client->request('GET', '/en/admin/recurring-expense/');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Recurring Expense List');
    }

    public function testNew(): void
    {
        // Get an existing ExpenseType or create one if necessary
        $expenseType = $this->expenseTypeRepository->findOneBy([]) ?? $this->createExpenseType();

        $this->client->request('GET', '/en/admin/recurring-expense/new');
        $this->assertResponseIsSuccessful();

        $this->client->submitForm('Save', [
            'recurring_expense[name]' => 'Test Recurring Expense',
            'recurring_expense[description]' => 'Test Description',
            'recurring_expense[amount]' => 100.50,
            'recurring_expense[dayOfMonth]' => 15,
            'recurring_expense[expenseType]' => $expenseType->getId(), // Assign a valid ExpenseType ID
        ]);

        $this->assertResponseRedirects('/en/admin/recurring-expense/');
        $this->client->followRedirect();

        $recurringExpense = $this->recurringExpenseRepository->findOneBy(['name' => 'Test Recurring Expense']);
        $this->assertNotNull($recurringExpense);
    }

    public function testEdit(): void
    {
        $expenseType = $this->expenseTypeRepository->findOneBy([]) ?? $this->createExpenseType();
        // Create a new recurring expense to edit
        $recurringExpense = new RecurringExpense();
        $recurringExpense->setName('Original Name');
        $recurringExpense->setDescription('Original Description');
        $recurringExpense->setAmount(50.00);
        $recurringExpense->setDayOfMonth(10);
        $recurringExpense->setExpenseType($expenseType); // Set a valid ExpenseType
        
        $entityManager = $this->client->getContainer()->get('doctrine')->getManager();
        $entityManager->persist($recurringExpense);
        $entityManager->flush();

        $this->client->request('GET', '/en/admin/recurring-expense/' . $recurringExpense->getId() . '/edit');
        $this->assertResponseIsSuccessful();

        $this->client->submitForm('Update', [
            'recurring_expense[name]' => 'Updated Name',
        ]);

        $this->assertResponseRedirects('/en/admin/recurring-expense/');
        $this->client->followRedirect();
        
        $updatedRecurringExpense = $this->recurringExpenseRepository->find($recurringExpense->getId());
        $this->assertSame('Updated Name', $updatedRecurringExpense->getName());
    }

    public function testDelete(): void
    {
        $expenseType = $this->expenseTypeRepository->findOneBy([]) ?? $this->createExpenseType();
        // Create a new recurring expense to delete
        $recurringExpense = new RecurringExpense();
        $recurringExpense->setName('To Be Deleted');
        $recurringExpense->setDescription('This will be deleted');
        $recurringExpense->setAmount(25.00);
        $recurringExpense->setDayOfMonth(5);
        $recurringExpense->setExpenseType($expenseType); // Set a valid ExpenseType

        $entityManager = $this->client->getContainer()->get('doctrine')->getManager();
        $entityManager->persist($recurringExpense);
        $entityManager->flush();
        
        $recurringExpenseId = $recurringExpense->getId();

        $this->client->request('GET', '/en/admin/recurring-expense/' . $recurringExpenseId . '/edit'); 
        $this->assertResponseIsSuccessful();

        $crawler = $this->client->getCrawler();
        $deleteForm = $crawler->filter('form button[type="submit"]')->reduce(function ($node) {
            return str_contains($node->text(null, true), 'Delete');
        })->form();
        
        $this->client->submit($deleteForm);

        $this->assertResponseRedirects('/en/admin/recurring-expense/');
        $this->client->followRedirect();

        $this->assertNull($this->recurringExpenseRepository->find($recurringExpenseId));
    }

    public function testAccessDeniedForRegularUsers(): void
    {
        $this->client->getCookieJar()->clear();
        
        $regularUser = $this->userRepository->findOneByUsername('john_user');
        if ($regularUser) {
            $this->client->loginUser($regularUser);
        } else {
            $this->markTestSkipped('Regular user john_user not found for testing access denial.');
            return;
        }

        // Create a dummy recurring expense for edit/delete URL or use a known ID
        $expenseType = $this->expenseTypeRepository->findOneBy([]) ?? $this->createExpenseType(true); // Pass true if it's ok to persist for this test
        $tempRecurringExpense = new RecurringExpense();
        $tempRecurringExpense->setName('Temporary Expense for Access Test');
        $tempRecurringExpense->setAmount(1);
        $tempRecurringExpense->setDayOfMonth(1);
        $tempRecurringExpense->setExpenseType($expenseType);
        $entityManager = $this->client->getContainer()->get('doctrine')->getManager();
        $entityManager->persist($tempRecurringExpense);
        $entityManager->flush();
        $tempRecurringExpenseId = $tempRecurringExpense->getId();


        $urls = [
            '/en/admin/recurring-expense/',
            '/en/admin/recurring-expense/new',
            '/en/admin/recurring-expense/' . $tempRecurringExpenseId . '/edit', 
        ];

        foreach ($urls as $url) {
            $this->client->request('GET', $url);
            $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
        }
    }

    private function createExpenseType(bool $persist = false): \App\Entity\ExpenseType
    {
        $expenseType = new \App\Entity\ExpenseType();
        $expenseType->setName('Test Expense Type for Recurring');
        $expenseType->setDescription('Created for testing recurring expenses');
        
        if ($persist) {
            $entityManager = $this->client->getContainer()->get('doctrine')->getManager();
            $entityManager->persist($expenseType);
            $entityManager->flush();
        }
        return $expenseType;
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->client->getContainer()->get('doctrine')->getManager()->clear();
        $this->recurringExpenseRepository = null; 
        $this->userRepository = null;
        $this->expenseTypeRepository = null;
    }
}
