<?php

namespace App\Controller;

use DateTimeImmutable;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Signer\Key\InMemory;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;
use Symfony\Component\Routing\Attribute\Route;

class RealtimeController extends AbstractController
{
    #[Route('/api/realtime/update', name: 'api_realtime_update', methods: ['POST'])]
    public function update(Request $request, HubInterface $hub): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!$data || !isset($data['topic']) || !isset($data['message'])) {
            return $this->json(['error' => 'Invalid data. Required: topic and message'], 400);
        }

        $topic = $data['topic'];
        $message = $data['message'];

        // Create and publish the update
        $update = new Update(
            $topic,
            json_encode($message)
        );

        $hub->publish($update);

        return $this->json(['status' => 'Update published', 'topic' => $topic]);
    }

    #[Route('/api/realtime/demo', name: 'api_realtime_demo', methods: ['GET'])]
    public function demo(HubInterface $hub): JsonResponse
    {
        // Demo: publish a random update to a demo topic
        $demoData = [
            'timestamp' => date('Y-m-d H:i:s'),
            'message' => 'Demo realtime update',
            'random' => rand(1, 100)
        ];

        $update = new Update(
            'demo-updates',
            json_encode($demoData)
        );

        $hub->publish($update);

        return $this->json(['status' => 'Demo update published', 'data' => $demoData]);
    }

    #[Route('/api/realtime/token', name: 'api_realtime_token', methods: ['GET'])]
    public function token(): JsonResponse
    {
        $secret = $_ENV['MERCURE_JWT_SECRET'] ?? '!ChangeThisMercureHubJWTSecretKey!';

        $config = Configuration::forSymmetricSigner(new Sha256(), InMemory::plainText($secret));

        $token = $config->builder()
            ->withClaim('mercure', ['subscribe' => ['*']])
            ->expiresAt(new DateTimeImmutable('+1 hour'))
            ->getToken($config->signer(), $config->signingKey());

        return $this->json(['token' => $token->toString()]);
    }
}
