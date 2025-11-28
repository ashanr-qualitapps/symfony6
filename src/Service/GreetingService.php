<?php

namespace App\Service;

use Psr\Log\LoggerInterface;

class GreetingService
{
    public function __construct(
        private LoggerInterface $logger
    ) {
    }

    public function greet(string $name): string
    {
        $this->logger->info('Greeting user', ['name' => $name]);

        return sprintf('Hello, %s!', ucfirst($name));
    }

    public function greetWithTime(string $name): string
    {
        $hour = (int) date('H');

        $timeOfDay = match (true) {
            $hour >= 5 && $hour < 12 => 'morning',
            $hour >= 12 && $hour < 18 => 'afternoon',
            $hour >= 18 && $hour < 22 => 'evening',
            default => 'night',
        };

        $this->logger->info('Greeting user with time', [
            'name' => $name,
            'time_of_day' => $timeOfDay,
        ]);

        return sprintf('Good %s, %s!', $timeOfDay, ucfirst($name));
    }
}
