<?php

namespace App\Tests\Application\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class LoginControllerTest extends WebTestCase
{
    private ?KernelBrowser $client = null;
    private ?EntityManagerInterface $em = null;
    private ?UserRepository $userRepository = null;
    private ?UserPasswordHasherInterface $passwordHasher = null;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $container = static::getContainer();
        $this->em = $container->get(EntityManagerInterface::class);
        $this->userRepository = $container->get(UserRepository::class);
        $this->passwordHasher = $container->get(UserPasswordHasherInterface::class);
    }

    protected static function getKernelClass(): string
    {
        return \App\Kernel::class;
    }

    public function testLoginWithValidCredentials(): void
    {
        // Create a test user
        $user = new User();
        $user->setEmail('login-test@example.com');
        $user->setRoles(['ROLE_USER']);
        $hashedPassword = $this->passwordHasher->hashPassword($user, 'TestPassword123!');
        $user->setPassword($hashedPassword);
        $user->setCreatedAt(new \DateTime());

        $this->em->persist($user);
        $this->em->flush();

        try {
            // Attempt login
            $this->client->request(
                'POST',
                '/api/login',
                [],
                [],
                ['CONTENT_TYPE' => 'application/json'],
                json_encode([
                    'email' => 'login-test@example.com',
                    'password' => 'TestPassword123!'
                ])
            );

            $this->assertResponseIsSuccessful();
            $this->assertResponseHeaderSame('content-type', 'application/json');

            $responseData = json_decode($this->client->getResponse()->getContent(), true);
            
            $this->assertTrue($responseData['success']);
            $this->assertArrayHasKey('token', $responseData);
            $this->assertArrayHasKey('user', $responseData);
            $this->assertEquals('login-test@example.com', $responseData['user']['email']);
            $this->assertContains('ROLE_USER', $responseData['user']['roles']);
        } finally {
            // Cleanup
            $createdUser = $this->userRepository->findOneByEmail('login-test@example.com');
            if ($createdUser) {
                $this->em->remove($createdUser);
                $this->em->flush();
            }
        }
    }

    public function testLoginWithInvalidCredentials(): void
    {
        $this->client->request(
            'POST',
            '/api/login',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'email' => 'nonexistent@example.com',
                'password' => 'WrongPassword123!',
                'test_id' => uniqid('invalid_credentials_', true)
            ])
        );

        $this->assertResponseStatusCodeSame(401);
        
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $responseData);
    }

    public function testLoginWithMissingPassword(): void
    {
        $this->client->request(
            'POST',
            '/api/login',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'email' => 'test@example.com',
                'test_id' => uniqid('missing_password_', true)
            ])
        );

        // Symfony returns 400 Bad Request when password is missing
        $this->assertResponseStatusCodeSame(400);
    }

    public function testProtectedEndpointWithoutToken(): void
    {
        $this->client->request('GET', '/api/countries');
        
        // Should return 401 Unauthorized
        $this->assertResponseStatusCodeSame(401);
    }

    public function testLoginRateLimiting(): void
    {
        $uniqueId = uniqid('test_', true);

        // Make 3 login attempts (should succeed)
        for ($i = 0; $i < 3; $i++) {
            $this->client->request(
                'POST',
                '/api/login',
                [],
                [],
                ['CONTENT_TYPE' => 'application/json'],
                json_encode([
                    'email' => 'nonexistent@example.com',
                    'password' => 'WrongPassword123!',
                    'test_id' => $uniqueId, // Use same ID for all requests
                    'enable_rate_limiting' => true // Enable rate limiting for this test
                ])
            );
            
            // First 3 attempts should return 401 (invalid credentials)
            $this->assertResponseStatusCodeSame(401);
            // Small delay to ensure rate limiter processes requests
            usleep(100000); // 0.1 seconds
        }

        // 4th attempt should be rate limited (429 Too Many Requests)
        $this->client->request(
            'POST',
            '/api/login',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'email' => 'nonexistent@example.com',
                'password' => 'WrongPassword123!',
                'test_id' => $uniqueId, // Same ID to trigger rate limit
                'enable_rate_limiting' => true
            ])
        );

        $this->assertResponseStatusCodeSame(429);
        
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $responseData);
        $this->assertArrayHasKey('retry_after', $responseData);
        $this->assertStringContainsString('Too many login attempts', $responseData['error']);
        $this->assertIsInt($responseData['retry_after']);
    }
}
