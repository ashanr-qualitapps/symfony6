<?php

namespace App\Tests\Integration\Service;

use App\Service\GreetingService;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Integration test for GreetingService.
 *
 * Integration tests boot the Symfony kernel and can access services.
 * This test creates the service manually but uses real dependencies from the container.
 */
class GreetingServiceTest extends KernelTestCase
{
    protected static function getKernelClass(): string
    {
        return \App\Kernel::class;
    }

    private GreetingService $greetingService;

    protected function setUp(): void
    {
        // Boot the Symfony kernel
        self::bootKernel();

        // Get the container
        $container = static::getContainer();

        // Get the logger from the container (real dependency)
        $logger = $container->get(LoggerInterface::class);

        // Create the service with the real logger
        $this->greetingService = new GreetingService($logger);
    }

    public function testGreetReturnsCorrectMessage(): void
    {
        $result = $this->greetingService->greet('john');

        $this->assertSame('Hello, John!', $result);
    }

    public function testGreetCapitalizesName(): void
    {
        $result = $this->greetingService->greet('alice');

        $this->assertSame('Hello, Alice!', $result);
    }

    public function testGreetWithTimeReturnsMessage(): void
    {
        $result = $this->greetingService->greetWithTime('bob');

        // Assert it contains the name
        $this->assertStringContainsString('Bob', $result);

        // Assert it starts with "Good"
        $this->assertStringStartsWith('Good', $result);

        // Assert it contains a time of day
        $this->assertMatchesRegularExpression(
            '/Good (morning|afternoon|evening|night), Bob!/',
            $result
        );
    }

    public function testServiceWorksWithRealLogger(): void
    {
        // Test that the service works correctly with the real logger from the container
        $result = $this->greetingService->greet('test');
        $this->assertIsString($result);
        $this->assertStringContainsString('Hello', $result);
    }
}
