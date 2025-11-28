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
        $client->request('GET', '/api/countries');

        $this->assertResponseStatusCodeSame(200);
    }

    public function testGetCountryCodesReturnsJson(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/country-codes');

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
        $client->request('GET', '/api/country-codes');

        $this->assertResponseStatusCodeSame(200);
    }
}