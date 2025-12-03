<?php

namespace App\Tests\Application\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class CountryControllerTest extends WebTestCase
{
    private ?KernelBrowser $client = null;
    private ?EntityManagerInterface $em = null;
    private ?UserPasswordHasherInterface $passwordHasher = null;
    private ?string $authToken = null;

    protected static function getKernelClass(): string
    {
        return \App\Kernel::class;
    }

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $container = static::getContainer();
        $this->em = $container->get(EntityManagerInterface::class);
        $this->passwordHasher = $container->get(UserPasswordHasherInterface::class);

        // Clean up any leftover test data first
        $this->cleanupTestUser();

        // Create and authenticate test user
        $this->authenticateUser();
    }

    private function cleanupTestUser(): void
    {
        $conn = $this->em->getConnection();
        $email = 'country-test@example.com';
        $sql = sprintf("DELETE FROM api_tokens WHERE user_id IN (SELECT id FROM users WHERE email = '%s')", $email);
        $conn->executeStatement($sql);

        $sql = sprintf("DELETE FROM users WHERE email = '%s'", $email);
        $conn->executeStatement($sql);
    }

    private function authenticateUser(): void
    {
        // Create a test user
        $user = new User();
        $user->setEmail('country-test@example.com');
        $user->setRoles(['ROLE_USER']);
        $hashedPassword = $this->passwordHasher->hashPassword($user, 'TestPassword123!');
        $user->setPassword($hashedPassword);
        $user->setCreatedAt(new \DateTime());

        $this->em->persist($user);
        $this->em->flush();

        // Login to get token
        $this->client->request(
            'POST',
            '/api/login',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'email' => 'country-test@example.com',
                'password' => 'TestPassword123!'
            ])
        );

        $loginResponse = json_decode($this->client->getResponse()->getContent(), true);
        $this->authToken = $loginResponse['token'];
    }

    protected function tearDown(): void
    {
        $this->cleanupTestUser();
        parent::tearDown();
    }

    public function testGetCountriesReturnsJson(): void
    {
        $this->client->request('GET', '/api/countries', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->authToken
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('Content-Type', 'application/json');

        $data = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertIsArray($data);
        $this->assertCount(193, $data); // We have 193 countries

        // Check first country
        $this->assertArrayHasKey('name', $data[0]);
        $this->assertArrayHasKey('code', $data[0]);
        $this->assertArrayHasKey('telephoneCode', $data[0]);
        $this->assertArrayHasKey('flag', $data[0]);
        $this->assertSame('Afghanistan', $data[0]['name']);
        $this->assertSame('AF', $data[0]['code']);
        $this->assertSame('+93', $data[0]['telephoneCode']);
        $this->assertSame('🇦🇫', $data[0]['flag']); // Flag emoji for AF
    }

    public function testGetCountriesReturnsCorrectStatusCode(): void
    {
        $this->client->request('GET', '/api/countries', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->authToken
        ]);

        $this->assertResponseStatusCodeSame(200);
    }

    public function testGetCountryCodesReturnsJson(): void
    {
        $this->client->request('GET', '/api/country-codes', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->authToken
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('Content-Type', 'application/json');

        $data = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertIsArray($data);
        $this->assertCount(193, $data); // We have 193 countries

        // Check first country
        $this->assertArrayHasKey('name', $data[0]);
        $this->assertArrayHasKey('code', $data[0]);
        $this->assertArrayHasKey('telephoneCode', $data[0]);
        $this->assertArrayHasKey('flag', $data[0]);
        $this->assertSame('Afghanistan', $data[0]['name']);
        $this->assertSame('AF', $data[0]['code']);
        $this->assertSame('+93', $data[0]['telephoneCode']);
        $this->assertSame('https://flagcdn.com/w320/af.png', $data[0]['flag']); // Flag URL for AF
    }

    public function testGetCountryCodesReturnsCorrectStatusCode(): void
    {
        $this->client->request('GET', '/api/country-codes', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->authToken
        ]);

        $this->assertResponseStatusCodeSame(200);
    }
}
