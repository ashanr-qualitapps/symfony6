<?php

namespace App\Tests\Application\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class RealtimeControllerTest extends WebTestCase
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
        $user->setEmail('realtime-test@example.com');
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
                'email' => 'realtime-test@example.com',
                'password' => 'TestPassword123!'
            ])
        );

        $loginResponse = json_decode($client->getResponse()->getContent(), true);
        $this->authToken = $loginResponse['token'];
    }

    protected function tearDown(): void
    {
        if ($this->em && $this->userRepository) {
            $user = $this->userRepository->findOneByEmail('realtime-test@example.com');
            if ($user) {
                $this->em->remove($user);
                $this->em->flush();
            }
        }

        parent::tearDown();
    }

    public function testRealtimeUpdateWithValidData(): void
    {
        $client = static::createClient();
        $this->authenticateUser($client);

        $client->request('POST', '/api/realtime/update', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->authToken
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
    }

    public function testRealtimeUpdateWithInvalidData(): void
    {
        $client = static::createClient();
        $this->authenticateUser($client);

        $client->request('POST', '/api/realtime/update', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->authToken
        ], json_encode([
            'invalid' => 'data'
        ]));

        $this->assertResponseStatusCodeSame(400);

        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $data);
    }

    public function testRealtimeDemo(): void
    {
        $client = static::createClient();
        $this->authenticateUser($client);

        $client->request('GET', '/api/realtime/demo', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->authToken
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
    }
}
