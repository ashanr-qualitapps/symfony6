<?php

namespace App\Tests\Application\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class RegistrationControllerTest extends WebTestCase
{
    protected static function getKernelClass(): string
    {
        return \App\Kernel::class;
    }

    public function testRegistrationRateLimiting(): void
    {
        $client = static::createClient();
        $uniqueId = uniqid('test_', true);

        // Make 3 registration attempts (should succeed)
        for ($i = 0; $i < 3; $i++) {
            $payload = [
                'email' => 'test_rate_limit_' . $i . '@example.com',
                'password' => 'changeme',
                'test_id' => $uniqueId,
                'enable_rate_limiting' => true
            ];

            $body = json_encode($payload);
            $client->request(
                'POST',
                '/api/register',
                [],
                [],
                ['CONTENT_TYPE' => 'application/json'],
                $body
            );

            // First 3 attempts should succeed (201 Created)
            $this->assertResponseStatusCodeSame(201);

            // Cleanup the created user
            $container = static::getContainer();
            $repo = $container->get(UserRepository::class);
            $user = $repo->findOneByEmail('test_rate_limit_' . $i . '@example.com');
            if ($user) {
                $em = $container->get('doctrine')->getManager();
                $em->remove($user);
                $em->flush();
            }

            // Small delay to ensure rate limiter processes requests
            usleep(100000); // 0.1 seconds
        }

        // 4th attempt should be rate limited (429 Too Many Requests)
        $payload = [
            'email' => 'test_rate_limit_3@example.com',
            'password' => 'changeme',
            'test_id' => $uniqueId,
            'enable_rate_limiting' => true
        ];

        $body = json_encode($payload);
        $client->request(
            'POST',
            '/api/register',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            $body
        );

        $this->assertResponseStatusCodeSame(429);

        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $responseData);
        $this->assertArrayHasKey('retry_after', $responseData);
        $this->assertStringContainsString('Too many registration attempts', $responseData['error']);
        $this->assertIsInt($responseData['retry_after']);
    }
}
