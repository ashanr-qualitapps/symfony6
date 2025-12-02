<?php

// src/Service/MessageGenerator.php
namespace App\Service;

use Psr\Log\LoggerInterface;

class MessageGenerator
{
    public function __construct(
        private LoggerInterface $logger,
    ) {
    }

    public function getSystemUpdateMessage(): string
    {
        $this->logger->info('Update Executed!');
        return 'System Updated!';
    }
}
