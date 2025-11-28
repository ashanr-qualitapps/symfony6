<?php

namespace App\Tests\Application\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class CountryControllerTest extends WebTestCase
{
    public function testGetCountriesReturnsJson(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/countries');

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('Content-Type', 'application/json');

        $data = json_decode($client->getResponse()->getContent(), true);

        $this->assertIsArray($data);
        $this->assertCount(10, $data); // We have 10 countries

        // Check first country
        $this->assertArrayHasKey('name', $data[0]);
        $this->assertArrayHasKey('code', $data[0]);
        $this->assertArrayHasKey('telephoneCode', $data[0]);
        $this->assertSame('United States', $data[0]['name']);
        $this->assertSame('US', $data[0]['code']);
        $this->assertSame('+1', $data[0]['telephoneCode']);
    }

    public function testGetCountriesReturnsCorrectStatusCode(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/countries');

        $this->assertResponseStatusCodeSame(200);
    }
}