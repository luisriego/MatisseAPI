<?php

namespace App\Tests\Controller\Admin;

use App\Entity\Slip;
use App\Entity\User;
use App\Repository\SlipRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class SlipControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private ?SlipRepository $slipRepository;
    private ?UserRepository $userRepository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = static::createClient();
        $this->slipRepository = $this->client->getContainer()->get(SlipRepository::class);
        $this->userRepository = $this->client->getContainer()->get(UserRepository::class);

        // Ensure admin user is logged in for all tests
        $adminUser = $this->userRepository->findOneByUsername('jane_admin');
        if ($adminUser) {
            $this->client->loginUser($adminUser);
        }
    }

    public function testIndex(): void
    {
        $this->client->request('GET', '/en/admin/slip/');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Slip List');
    }

    public function testGenerateSlipsGet(): void
    {
        $this->client->request('GET', '/en/admin/slip/generate');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Generate Slips');
    }
    
    public function testGenerateSlipsPost(): void
    {
        // To fully test slip generation, we might need more setup,
        // e.g., ensuring there are residents, recurring expenses, etc.
        // This test will focus on the form submission and basic response.
        // A more in-depth test might require creating fixtures or specific service mocking.

        $this->client->request('GET', '/en/admin/slip/generate');
        $this->assertResponseIsSuccessful();

        // Assuming the form requires a month and year
        // The form name and fields might need adjustment based on GenerateSlipsFormType
        $crawler = $this->client->getCrawler();
        $form = $crawler->selectButton('Generate')->form(); // Adjust button text if necessary

        // Get current date to ensure the selected month/year is valid for generation
        // Or use a fixed date known to be valid for testing based on application logic
        $currentYear = (int)date('Y');
        $currentMonth = (int)date('m');

        // Example: Generate for the current month and year
        // Adjust if your form fields are named differently
        // Note: The 'month' field in Symfony forms often expects an integer (1-12)
        // and 'year' an integer (e.g., 2023).
        // The exact field names 'generate_slips_form[month]' and 'generate_slips_form[year]'
        // depend on how GenerateSlipsFormType is defined.
        $this->client->submit($form, [
            'generate_slips_form[month]' => $currentMonth, 
            'generate_slips_form[year]' => $currentYear,
        ]);

        // Check for a redirect, typically to the slip list or a success page
        $this->assertResponseRedirects('/en/admin/slip/'); 
        $this->client->followRedirect();
        
        // Optionally, assert that new slips were created if possible to check
        // This might involve querying the SlipRepository for slips matching the generated month/year
        // $generatedSlips = $this->slipRepository->findBy(['month' => $currentMonth, 'year' => $currentYear]);
        // $this->assertNotEmpty($generatedSlips, "Slips should have been generated for {$currentMonth}/{$currentYear}");
        
        // Or check for a success flash message
        $this->assertSelectorExists('.alert-success', 'A success message should be displayed after generating slips.');
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
            '/en/admin/slip/',
            '/en/admin/slip/generate', 
        ];

        foreach ($urls as $url) {
            $this->client->request('GET', $url);
            $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
        }
        
        // Test POST for generate slips as well
        $this->client->request('POST', '/en/admin/slip/generate', [
             'generate_slips_form[month]' => (int)date('m'),
             'generate_slips_form[year]' => (int)date('Y'),
        ]);
        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->client->getContainer()->get('doctrine')->getManager()->clear();
        $this->slipRepository = null; 
        $this->userRepository = null; 
    }
}
