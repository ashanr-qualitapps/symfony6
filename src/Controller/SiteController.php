<?php

// src/Controller/SiteController.php
namespace App\Controller;

use App\Service\SiteUpdateManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

// ...

class SiteController extends AbstractController
{
    public function new(SiteUpdateManager $siteUpdateManager): Response
    {
        if ($siteUpdateManager->notifyOfSiteUpdate()) {
            $this->addFlash('success', 'Notification mail was sent successfully.');
        } else {
            $this->addFlash('error', 'Failed to send notification mail.');
        }
        return $this->redirectToRoute('app_home');
    }
}
