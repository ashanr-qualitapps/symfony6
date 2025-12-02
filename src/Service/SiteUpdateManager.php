<?php

// src/Service/SiteUpdateManager.php
namespace App\Service;

use App\Service\MessageGenerator;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class SiteUpdateManager
{
    public function __construct(
        private MessageGenerator $messageGenerator,
        private MailerInterface $mailer,
        private string $adminEmail
    ) {
    }

    public function notifyOfSiteUpdate(): bool
    {
        $siteUpdateMessage = $this->messageGenerator->getSystemUpdateMessage();

        $email = (new Email())
            ->from('admin@example.com')
            ->to($this->adminEmail)
            ->subject('Site update just happened!')
            ->text('Someone just updated the site. We told them: ' . $siteUpdateMessage);

        $this->mailer->send($email);

        return true;
    }
}
