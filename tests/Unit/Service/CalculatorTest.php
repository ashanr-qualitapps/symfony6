<?php

namespace App\Tests\Unit\Service;

use App\Service\Calculator;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for Calculator service.
 *
 * Unit tests do NOT boot the Symfony kernel or use the service container.
 * They test a single class in isolation for maximum speed.
 */
class CalculatorTest extends TestCase
{
    private Calculator $calculator;

    protected function setUp(): void
    {
        // Create the class under test directly - no service container needed
        $this->calculator = new Calculator();
    }

    public function testAdd(): void
    {
        $result = $this->calculator->add(5, 3);
        $this->assertSame(8, $result);
    }

    public function testAddWithNegativeNumbers(): void
    {
        $result = $this->calculator->add(-5, 3);
        $this->assertSame(-2, $result);
    }

    public function testSubtract(): void
    {
        $result = $this->calculator->subtract(10, 4);
        $this->assertSame(6, $result);
    }

    public function testMultiply(): void
    {
        $result = $this->calculator->multiply(7, 6);
        $this->assertSame(42, $result);
    }

    public function testDivide(): void
    {
        $result = $this->calculator->divide(10, 2);
        $this->assertSame(5.0, $result);
    }

    public function testDivideByZeroThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Division by zero is not allowed');

        $this->calculator->divide(10, 0);
    }

    /**
     * Data providers allow testing multiple scenarios with the same test logic
     */
    public function testAddWithDataProvider(): void
    {
        $testCases = [
            [1, 2, 3],
            [0, 0, 0],
            [-1, 1, 0],
            [100, 200, 300],
        ];

        foreach ($testCases as [$a, $b, $expected]) {
            $result = $this->calculator->add($a, $b);
            $this->assertSame($expected, $result, "Failed asserting that {$a} + {$b} = {$expected}");
        }
    }
}
