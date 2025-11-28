<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(): Response
    {
        return $this->render('home/index.html.twig', [
            'title' => 'Welcome to Symfony 6!',
            'message' => 'Your application is up and running.',
        ]);
    }

    #[Route('/api/health', name: 'api_health', methods: ['GET'])]
    public function health(): JsonResponse
    {
        return new JsonResponse([
            'status' => 'healthy',
            'timestamp' => time(),
        ]);
    }

    #[Route('/realtime', name: 'app_realtime')]
    public function realtime(): Response
    {
        return $this->render('realtime/index.html.twig', [
            'title' => 'Realtime Demo',
            'message' => 'Experience realtime updates with Mercure!',
            'mercure_public_url' => $_ENV['MERCURE_PUBLIC_URL'] ?? 'http://localhost:8081/.well-known/mercure',
        ]);
    }
}
