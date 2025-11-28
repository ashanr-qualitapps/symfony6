<?php

namespace App\Controller;

use Firebase\JWT\JWT;
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

        $payload = [
            'mercure' => [
                'subscribe' => ['*'], // Allow subscribing to all topics
            ],
            'exp' => time() + 3600, // Token expires in 1 hour
        ];

        $token = JWT::encode($payload, $secret, 'HS256');

        return $this->json(['token' => $token]);
    }
}