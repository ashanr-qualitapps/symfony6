<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
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

    #[Route('/api/health', name: 'app_health')]
    public function health(): Response
    {
        return $this->json([
            'status' => 'healthy',
            'timestamp' => time(),
        ]);
    }
}
