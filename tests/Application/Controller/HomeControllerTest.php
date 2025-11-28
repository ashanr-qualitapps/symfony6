<?php

namespace App\Tests\Application\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Application/Functional test for HomeController.
 *
 * Application tests boot the Symfony kernel and test the full request/response cycle.
 * Use WebTestCase for controller tests that need to make HTTP requests.
 */
class HomeControllerTest extends WebTestCase
{
    public function testHomePageIsSuccessful(): void
    {
        // Create a test client that simulates a browser
        $client = static::createClient();

        // Make a request to the homepage
        $crawler = $client->request('GET', '/');

        // Assert the response is successful (2xx status code)
        $this->assertResponseIsSuccessful();

        // Assert the page contains expected text
        $this->assertSelectorTextContains('h1', 'Welcome to Symfony 6!');
    }

    public function testHomePageRendersCorrectTemplate(): void
    {
        $client = static::createClient();
        $client->request('GET', '/');

        $this->assertResponseIsSuccessful();

        // Check that the correct template was rendered
        $this->assertSelectorExists('h1');
        $this->assertSelectorTextContains('p', 'Your application is up and running');
    }

    public function testHealthCheckReturnsJson(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/health');

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('Content-Type', 'application/json');

        // Decode the JSON response
        $data = json_decode($client->getResponse()->getContent(), true);

        // Assert the response structure
        $this->assertArrayHasKey('status', $data);
        $this->assertArrayHasKey('timestamp', $data);
        $this->assertSame('healthy', $data['status']);
        $this->assertIsInt($data['timestamp']);
    }

    public function testHealthCheckReturnsCorrectStatusCode(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/health');

        // More explicit status code check
        $this->assertResponseStatusCodeSame(200);
    }

    public function testNonExistentRouteReturns404(): void
    {
        $client = static::createClient();
        $client->request('GET', '/this-route-does-not-exist');

        $this->assertResponseStatusCodeSame(404);
    }
}
