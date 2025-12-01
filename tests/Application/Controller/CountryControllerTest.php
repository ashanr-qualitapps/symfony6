<?php

namespace App\Tests\Application\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class CountryControllerTest extends WebTestCase
{
    private ?EntityManagerInterface $em = null;
    private ?UserRepository $userRepository = null;
    private ?UserPasswordHasherInterface $passwordHasher = null;
    private ?string $authToken = null;

    protected static function getKernelClass(): string
    {
        return \App\Kernel::class;
    }

    private function authenticateUser(KernelBrowser $client): void
    {
        if ($this->authToken !== null) {
            return; // Already authenticated
        }

        $container = static::getContainer();
        $this->em = $container->get(EntityManagerInterface::class);
        $this->userRepository = $container->get(UserRepository::class);
        $this->passwordHasher = $container->get(UserPasswordHasherInterface::class);

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
        $client->request(
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

        $loginResponse = json_decode($client->getResponse()->getContent(), true);
        $this->authToken = $loginResponse['token'];
    }

    protected function tearDown(): void
    {
        if ($this->em && $this->userRepository) {
            $user = $this->userRepository->findOneByEmail('country-test@example.com');
            if ($user) {
                $this->em->remove($user);
                $this->em->flush();
            }
        }

        parent::tearDown();
    }

    public function testGetCountriesReturnsJson(): void
    {
        $client = static::createClient();
        $this->authenticateUser($client);
        $client->request('GET', '/api/countries', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->authToken
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('Content-Type', 'application/json');

        $data = json_decode($client->getResponse()->getContent(), true);

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
        $client = static::createClient();
        $this->authenticateUser($client);
        $client->request('GET', '/api/countries', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->authToken
        ]);

        $this->assertResponseStatusCodeSame(200);
    }

    public function testGetCountryCodesReturnsJson(): void
    {
        $client = static::createClient();
        $this->authenticateUser($client);
        $client->request('GET', '/api/country-codes', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->authToken
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('Content-Type', 'application/json');

        $data = json_decode($client->getResponse()->getContent(), true);

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
        $client = static::createClient();
        $this->authenticateUser($client);
        $client->request('GET', '/api/country-codes', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->authToken
        ]);

        $this->assertResponseStatusCodeSame(200);
    }
}
