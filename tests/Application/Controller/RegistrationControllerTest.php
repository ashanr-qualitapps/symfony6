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

    public function testRegisterCreatesUser(): void
    {
        $client = static::createClient();

        $payload = [
            'email' => 'test_register@example.com',
            'password' => 'changeme'
        ];

        $client->request('POST', '/api/register', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode($payload));

        $this->assertResponseStatusCodeSame(201);

        // Verify user exists in DB
        $container = static::getContainer();
        /** @var UserRepository $repo */
        $repo = $container->get(UserRepository::class);
        $user = $repo->findOneByEmail('test_register@example.com');
        $this->assertNotNull($user, 'User should be present in database after registration');

        // Cleanup
        $em = $container->get('doctrine')->getManager();
        $em->remove($user);
        $em->flush();
    }
}
