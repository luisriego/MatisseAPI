<?php

namespace App\Tests\Controller\Admin;

use App\Entity\IncomeType;
use App\Entity\User;
use App\Repository\IncomeTypeRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class IncomeTypeControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private ?IncomeTypeRepository $incomeTypeRepository;
    private ?UserRepository $userRepository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = static::createClient();
        $this->incomeTypeRepository = $this->client->getContainer()->get(IncomeTypeRepository::class);
        $this->userRepository = $this->client->getContainer()->get(UserRepository::class);

        // Ensure admin user is logged in for all tests
        $adminUser = $this->userRepository->findOneByUsername('jane_admin');
        if ($adminUser) {
            $this->client->loginUser($adminUser);
        }
    }

    public function testIndex(): void
    {
        $this->client->request('GET', '/en/admin/income-type/');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Income Type List');
    }

    public function testNew(): void
    {
        $this->client->request('GET', '/en/admin/income-type/new');
        $this->assertResponseIsSuccessful();

        $this->client->submitForm('Save', [
            'income_type[name]' => 'Test Income Type',
            'income_type[description]' => 'Test Description',
        ]);

        $this->assertResponseRedirects('/en/admin/income-type/');
        $this->client->followRedirect();

        $incomeType = $this->incomeTypeRepository->findOneBy(['name' => 'Test Income Type']);
        $this->assertNotNull($incomeType);
    }

    public function testEdit(): void
    {
        // Create a new income type to edit
        $incomeType = new IncomeType();
        $incomeType->setName('Original Name');
        $incomeType->setDescription('Original Description');
        
        $entityManager = $this->client->getContainer()->get('doctrine')->getManager();
        $entityManager->persist($incomeType);
        $entityManager->flush();

        $this->client->request('GET', '/en/admin/income-type/' . $incomeType->getId() . '/edit');
        $this->assertResponseIsSuccessful();

        $this->client->submitForm('Update', [
            'income_type[name]' => 'Updated Name',
        ]);

        $this->assertResponseRedirects('/en/admin/income-type/');
        $this->client->followRedirect();
        
        $updatedIncomeType = $this->incomeTypeRepository->find($incomeType->getId());
        $this->assertSame('Updated Name', $updatedIncomeType->getName());
    }

    public function testDelete(): void
    {
        // Create a new income type to delete
        $incomeType = new IncomeType();
        $incomeType->setName('To Be Deleted');
        $incomeType->setDescription('This will be deleted');

        $entityManager = $this->client->getContainer()->get('doctrine')->getManager();
        $entityManager->persist($incomeType);
        $entityManager->flush();
        
        $incomeTypeId = $incomeType->getId();

        $this->client->request('GET', '/en/admin/income-type/' . $incomeTypeId . '/edit'); 
        $this->assertResponseIsSuccessful();

        $crawler = $this->client->getCrawler();
        $deleteForm = $crawler->filter('form button[type="submit"]')->reduce(function ($node) {
            return str_contains($node->text(null, true), 'Delete');
        })->form();
        
        $this->client->submit($deleteForm);

        $this->assertResponseRedirects('/en/admin/income-type/');
        $this->client->followRedirect();

        $this->assertNull($this->incomeTypeRepository->find($incomeTypeId));
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

        $urls = [
            '/en/admin/income-type/',
            '/en/admin/income-type/new',
            '/en/admin/income-type/1/edit', 
        ];

        foreach ($urls as $url) {
            $this->client->request('GET', $url);
            $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
        }
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->client->getContainer()->get('doctrine')->getManager()->clear();
        $this->incomeTypeRepository = null; 
        $this->userRepository = null; 
    }
}
