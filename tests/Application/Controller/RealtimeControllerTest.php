<?php

namespace App\Tests\Application\Controller;

use App\Entity\User;
use App\Repository\ApiTokenRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class RealtimeControllerTest extends WebTestCase
{
    private const TEST_EMAIL = 'realtime-test@example.com';
    private const TEST_PASSWORD = 'TestPassword123!';

    protected static function getKernelClass(): string
    {
        return \App\Kernel::class;
    }

    private function cleanupTestUser(EntityManagerInterface $em): void
    {
        $connection = $em->getConnection();

        // Delete tokens first using raw SQL to avoid entity manager issues
        $connection->executeStatement(
            'DELETE FROM api_tokens WHERE user_id IN (SELECT id FROM users WHERE email = :email)',
            ['email' => self::TEST_EMAIL]
        );

        // Delete user
        $connection->executeStatement(
            'DELETE FROM users WHERE email = :email',
            ['email' => self::TEST_EMAIL]
        );
    }

    private function createTestUserAndGetToken(KernelBrowser $client): string
    {
        $container = static::getContainer();
        $em = $container->get(EntityManagerInterface::class);
        $passwordHasher = $container->get(UserPasswordHasherInterface::class);

        // Clean up any existing test user first
        $this->cleanupTestUser($em);

        // Create a test user
        $user = new User();
        $user->setEmail(self::TEST_EMAIL);
        $user->setRoles(['ROLE_USER']);
        $hashedPassword = $passwordHasher->hashPassword($user, self::TEST_PASSWORD);
        $user->setPassword($hashedPassword);
        $user->setCreatedAt(new \DateTime());

        $em->persist($user);
        $em->flush();

        // Login to get token
        $client->request(
            'POST',
            '/api/login',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'email' => self::TEST_EMAIL,
                'password' => self::TEST_PASSWORD
            ])
        );

        $loginResponse = json_decode($client->getResponse()->getContent(), true);

        if (!isset($loginResponse['token'])) {
            throw new \RuntimeException('Failed to get auth token. Response: ' . json_encode($loginResponse));
        }

        return $loginResponse['token'];
    }

    public function testRealtimeUpdateWithValidData(): void
    {
        $client = static::createClient();
        $authToken = $this->createTestUserAndGetToken($client);

        try {
            $client->request('POST', '/api/realtime/update', [], [], [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $authToken
            ], json_encode([
                'topic' => 'test-topic',
                'message' => ['key' => 'value']
            ]));

            $this->assertResponseIsSuccessful();
            $this->assertResponseHeaderSame('Content-Type', 'application/json');

            $data = json_decode($client->getResponse()->getContent(), true);
            $this->assertArrayHasKey('status', $data);
            $this->assertArrayHasKey('topic', $data);
            $this->assertSame('Update published', $data['status']);
            $this->assertSame('test-topic', $data['topic']);
        } finally {
            $this->cleanupTestUser(static::getContainer()->get(EntityManagerInterface::class));
        }
    }

    public function testRealtimeUpdateWithInvalidData(): void
    {
        $client = static::createClient();
        $authToken = $this->createTestUserAndGetToken($client);

        try {
            $client->request('POST', '/api/realtime/update', [], [], [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $authToken
            ], json_encode([
                'invalid' => 'data'
            ]));

            $this->assertResponseStatusCodeSame(400);

            $data = json_decode($client->getResponse()->getContent(), true);
            $this->assertArrayHasKey('error', $data);
        } finally {
            $this->cleanupTestUser(static::getContainer()->get(EntityManagerInterface::class));
        }
    }

    public function testRealtimeDemo(): void
    {
        $client = static::createClient();
        $authToken = $this->createTestUserAndGetToken($client);

        try {
            $client->request('GET', '/api/realtime/demo', [], [], [
                'HTTP_AUTHORIZATION' => 'Bearer ' . $authToken
            ]);

            $this->assertResponseIsSuccessful();
            $this->assertResponseHeaderSame('Content-Type', 'application/json');

            $data = json_decode($client->getResponse()->getContent(), true);
            $this->assertArrayHasKey('status', $data);
            $this->assertArrayHasKey('data', $data);
            $this->assertSame('Demo update published', $data['status']);
            $this->assertArrayHasKey('timestamp', $data['data']);
            $this->assertArrayHasKey('message', $data['data']);
            $this->assertArrayHasKey('random', $data['data']);
        } finally {
            $this->cleanupTestUser(static::getContainer()->get(EntityManagerInterface::class));
        }
    }
}
