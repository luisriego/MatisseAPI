<?php

namespace App\Tests\Controller\Admin;

use App\Entity\ExpenseType;
use App\Entity\User;
use App\Repository\ExpenseTypeRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class ExpenseTypeControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private ?ExpenseTypeRepository $expenseTypeRepository;
    private ?UserRepository $userRepository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = static::createClient();
        $this->expenseTypeRepository = $this->client->getContainer()->get(ExpenseTypeRepository::class);
        $this->userRepository = $this->client->getContainer()->get(UserRepository::class);

        // Ensure admin user is logged in for all tests
        $adminUser = $this->userRepository->findOneByUsername('jane_admin');
        if ($adminUser) {
            $this->client->loginUser($adminUser);
        }
    }

    public function testIndex(): void
    {
        $this->client->request('GET', '/en/admin/expense-type/');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Expense Type List');
    }

    public function testNew(): void
    {
        $this->client->request('GET', '/en/admin/expense-type/new');
        $this->assertResponseIsSuccessful();

        $this->client->submitForm('Save', [
            'expense_type[name]' => 'Test Expense Type',
            'expense_type[description]' => 'Test Description',
        ]);

        $this->assertResponseRedirects('/en/admin/expense-type/');
        $this->client->followRedirect();

        $expenseType = $this->expenseTypeRepository->findOneBy(['name' => 'Test Expense Type']);
        $this->assertNotNull($expenseType);
    }

    public function testEdit(): void
    {
        // Create a new expense type to edit
        $expenseType = new ExpenseType();
        $expenseType->setName('Original Name');
        $expenseType->setDescription('Original Description');
        
        $entityManager = $this->client->getContainer()->get('doctrine')->getManager();
        $entityManager->persist($expenseType);
        $entityManager->flush();

        $this->client->request('GET', '/en/admin/expense-type/' . $expenseType->getId() . '/edit');
        $this->assertResponseIsSuccessful();

        $this->client->submitForm('Update', [
            'expense_type[name]' => 'Updated Name',
        ]);

        $this->assertResponseRedirects('/en/admin/expense-type/');
        $this->client->followRedirect();
        
        $updatedExpenseType = $this->expenseTypeRepository->find($expenseType->getId());
        $this->assertSame('Updated Name', $updatedExpenseType->getName());
    }

    public function testDelete(): void
    {
        // Create a new expense type to delete
        $expenseType = new ExpenseType();
        $expenseType->setName('To Be Deleted');
        $expenseType->setDescription('This will be deleted');

        $entityManager = $this->client->getContainer()->get('doctrine')->getManager();
        $entityManager->persist($expenseType);
        $entityManager->flush();
        
        $expenseTypeId = $expenseType->getId();

        $this->client->request('GET', '/en/admin/expense-type/' . $expenseTypeId . '/edit'); // Navigating to edit page to get the delete form
        $this->assertResponseIsSuccessful();

        // Ensure the token is present if CSRF protection is enabled for this form
        // If your delete form is within the edit page and has a specific ID or button name, adjust accordingly.
        // This assumes the delete button is part of a form that can be submitted directly.
        // If it's a link or requires JavaScript, this approach might need adjustment or a different testing strategy (e.g., Panther).
        $crawler = $this->client->getCrawler();
        // Looking for a form that contains a button with type submit and text containing 'Delete'
        // This is a common pattern for delete forms. Adjust if your form is different.
        $deleteForm = $crawler->filter('form button[type="submit"]')->reduce(function ($node) {
            return str_contains($node->text(null, true), 'Delete');
        })->form();
        
        $this->client->submit($deleteForm);

        $this->assertResponseRedirects('/en/admin/expense-type/');
        $this->client->followRedirect();

        $this->assertNull($this->expenseTypeRepository->find($expenseTypeId));
    }

    public function testAccessDeniedForRegularUsers(): void
    {
        // Log out admin user
        $this->client->getCookieJar()->clear();
        
        // Log in as a regular user
        $regularUser = $this->userRepository->findOneByUsername('john_user');
        if ($regularUser) {
            $this->client->loginUser($regularUser);
        } else {
            // If john_user does not exist, create one for the test or skip
            $this->markTestSkipped('Regular user john_user not found for testing access denial.');
            return;
        }

        $urls = [
            '/en/admin/expense-type/',
            '/en/admin/expense-type/new',
            // Assuming an expense type with ID 1 exists for edit/delete checks
            // Adjust if necessary or create one for this test
            '/en/admin/expense-type/1/edit', 
        ];

        foreach ($urls as $url) {
            $this->client->request('GET', $url);
            $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
        }
        
        // Test POST request for delete as well, if applicable and ID is known/static for test
        // This might require creating an entity first if ID 1 is not guaranteed
        // $this->client->request('POST', '/en/admin/expense-type/1/delete');
        // $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        // Clear the entity manager to avoid conflicts between tests
        $this->client->getContainer()->get('doctrine')->getManager()->clear();
        $this->expenseTypeRepository = null; // Avoid memory leaks
        $this->userRepository = null; // Avoid memory leaks
    }
}
