<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

class CountryController extends AbstractController
{
    #[Route('/api/countries', name: 'api_countries', methods: ['GET'])]
    public function getCountries(): JsonResponse
    {
        $countries = [
            ['name' => 'United States', 'code' => 'US', 'telephoneCode' => '+1'],
            ['name' => 'United Kingdom', 'code' => 'GB', 'telephoneCode' => '+44'],
            ['name' => 'Germany', 'code' => 'DE', 'telephoneCode' => '+49'],
            ['name' => 'France', 'code' => 'FR', 'telephoneCode' => '+33'],
            ['name' => 'Italy', 'code' => 'IT', 'telephoneCode' => '+39'],
            ['name' => 'Spain', 'code' => 'ES', 'telephoneCode' => '+34'],
            ['name' => 'Canada', 'code' => 'CA', 'telephoneCode' => '+1'],
            ['name' => 'Australia', 'code' => 'AU', 'telephoneCode' => '+61'],
            ['name' => 'Japan', 'code' => 'JP', 'telephoneCode' => '+81'],
            ['name' => 'China', 'code' => 'CN', 'telephoneCode' => '+86'],
        ];

        return $this->json($countries);
    }
}