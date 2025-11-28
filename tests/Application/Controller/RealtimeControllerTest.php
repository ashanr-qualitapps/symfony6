<?php

namespace App\Tests\Application\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;

class RealtimeControllerTest extends WebTestCase
{
    public function testRealtimeUpdateWithValidData(): void
    {
        $client = static::createClient();

        // Mock the HubInterface
        $mockHub = $this->createMock(HubInterface::class);
        $mockHub->expects($this->once())
            ->method('publish')
            ->willReturn('update-id');

        self::getContainer()->set(HubInterface::class, $mockHub);

        $client->request('POST', '/api/realtime/update', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
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

        // Mock the HubInterface
        $mockHub = $this->createMock(HubInterface::class);
        $mockHub->expects($this->never())
            ->method('publish');

        self::getContainer()->set(HubInterface::class, $mockHub);

        $client->request('POST', '/api/realtime/update', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'invalid' => 'data'
        ]));

        $this->assertResponseStatusCodeSame(400);

        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $data);
    }

    public function testRealtimeDemo(): void
    {
        $client = static::createClient();

        // Mock the HubInterface
        $mockHub = $this->createMock(HubInterface::class);
        $mockHub->expects($this->once())
            ->method('publish')
            ->willReturn('update-id');

        self::getContainer()->set(HubInterface::class, $mockHub);

        $client->request('GET', '/api/realtime/demo');

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