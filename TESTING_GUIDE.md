# Testing Guide for Symfony 6

This guide explains the testing structure and best practices for this Symfony 6 application.

## 📁 Test Directory Structure

```
tests/
├── Unit/                   # Fast, isolated tests (no Symfony kernel)
│   └── Service/
│       └── CalculatorTest.php
├── Integration/            # Tests with service container and dependencies
│   └── Service/
│       └── GreetingServiceTest.php
└── Application/            # Full request/response cycle tests
    └── Controller/
        └── HomeControllerTest.php
```

## 🧪 Test Types

### 1. Unit Tests (`tests/Unit/`)

**Purpose:** Test a single class or method in complete isolation.

**Characteristics:**
- ✅ Do NOT boot the Symfony kernel
- ✅ Do NOT use the service container
- ✅ Create class instances directly (`new ClassName()`)
- ✅ Very fast execution
- ✅ Extend `PHPUnit\Framework\TestCase`

**Example:**
```php
<?php

namespace App\Tests\Unit\Service;

use App\Service\Calculator;
use PHPUnit\Framework\TestCase;

class CalculatorTest extends TestCase
{
    private Calculator $calculator;

    protected function setUp(): void
    {
        $this->calculator = new Calculator();
    }

    public function testAdd(): void
    {
        $result = $this->calculator->add(5, 3);
        $this->assertSame(8, $result);
    }
}
```

**When to use:**
- Testing business logic without dependencies
- Testing utilities, helpers, value objects
- Testing algorithms and calculations
- When speed is critical

### 2. Integration Tests (`tests/Integration/`)

**Purpose:** Test how components work together with their real dependencies.

**Characteristics:**
- ✅ Boot the Symfony kernel
- ✅ Use the service container
- ✅ Get services via `static::getContainer()->get()`
- ✅ Test with real dependencies (database, logger, etc.)
- ✅ Extend `Symfony\Bundle\FrameworkBundle\Test\KernelTestCase`

**Example:**
```php
<?php

namespace App\Tests\Integration\Service;

use App\Service\GreetingService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class GreetingServiceTest extends KernelTestCase
{
    private GreetingService $service;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        $this->service = $container->get(GreetingService::class);
    }

    public function testGreet(): void
    {
        $result = $this->service->greet('john');
        $this->assertSame('Hello, John!', $result);
    }
}
```

**When to use:**
- Testing services with dependencies
- Testing database repositories
- Testing services that use other services
- Validating autowiring and service configuration

### 3. Application/Functional Tests (`tests/Application/`)

**Purpose:** Test the full HTTP request/response cycle.

**Characteristics:**
- ✅ Boot the Symfony kernel
- ✅ Simulate HTTP requests
- ✅ Test routing, controllers, and templates
- ✅ Use test client for making requests
- ✅ Extend `Symfony\Bundle\FrameworkBundle\Test\WebTestCase`

**Example:**
```php
<?php

namespace App\Tests\Application\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class HomeControllerTest extends WebTestCase
{
    public function testHomePage(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Welcome');
    }
}
```

**When to use:**
- Testing controllers and routes
- Testing form submissions
- Testing authentication and authorization
- Testing API endpoints
- End-to-end workflow testing

## 🚀 Running Tests

### Run All Tests
```bash
docker-compose exec php php bin/phpunit
```

### Run Specific Test Directory
```bash
# Unit tests only
docker-compose exec php php bin/phpunit tests/Unit

# Integration tests only
docker-compose exec php php bin/phpunit tests/Integration

# Application tests only
docker-compose exec php php bin/phpunit tests/Application
```

### Run Specific Test Class
```bash
docker-compose exec php php bin/phpunit tests/Unit/Service/CalculatorTest.php
```

### Run Specific Test Method
```bash
docker-compose exec php php bin/phpunit --filter testAdd
```

### Run with Code Coverage
```bash
# HTML report (opens in browser)
docker-compose exec php php bin/phpunit --coverage-html var/coverage

# Text report in terminal
docker-compose exec php php bin/phpunit --coverage-text
```

### Run with Verbose Output
```bash
docker-compose exec php php bin/phpunit --verbose
```

### Run Tests in Testdox Format (readable output)
```bash
docker-compose exec php php bin/phpunit --testdox
```

## 📋 PHPUnit Auto-Discovery

PHPUnit automatically discovers and runs any class:
- Located in the `tests/` directory
- With a class name ending in `Test`
- Example: `CalculatorTest.php`, `HomeControllerTest.php`

**File naming conventions:**
```
src/Service/Calculator.php        → tests/Unit/Service/CalculatorTest.php
src/Service/GreetingService.php   → tests/Integration/Service/GreetingServiceTest.php
src/Controller/HomeController.php → tests/Application/Controller/HomeControllerTest.php
```

## ✅ Common Assertions

### Basic Assertions
```php
$this->assertTrue($value);
$this->assertFalse($value);
$this->assertNull($value);
$this->assertSame($expected, $actual);      // Strict equality (===)
$this->assertEquals($expected, $actual);     // Loose equality (==)
$this->assertCount(3, $array);
```

### String Assertions
```php
$this->assertStringContainsString('hello', $string);
$this->assertStringStartsWith('Hello', $string);
$this->assertStringEndsWith('world', $string);
$this->assertMatchesRegularExpression('/pattern/', $string);
```

### Array Assertions
```php
$this->assertArrayHasKey('key', $array);
$this->assertContains('value', $array);
$this->assertEmpty($array);
$this->assertNotEmpty($array);
```

### Exception Assertions
```php
$this->expectException(\InvalidArgumentException::class);
$this->expectExceptionMessage('error message');
```

### HTTP Assertions (WebTestCase)
```php
$this->assertResponseIsSuccessful();           // 2xx status
$this->assertResponseStatusCodeSame(200);
$this->assertResponseRedirects('/path');
$this->assertSelectorExists('h1');
$this->assertSelectorTextContains('h1', 'Welcome');
$this->assertResponseHeaderSame('Content-Type', 'application/json');
```

## 🎯 Best Practices

### 1. One Assertion Per Test (Generally)
```php
// Good
public function testAddReturnsCorrectSum(): void
{
    $result = $this->calculator->add(2, 3);
    $this->assertSame(5, $result);
}

public function testAddHandlesNegativeNumbers(): void
{
    $result = $this->calculator->add(-2, 3);
    $this->assertSame(1, $result);
}
```

### 2. Use Descriptive Test Names
```php
// Good - describes what is being tested
public function testDivideByZeroThrowsException(): void

// Bad - vague
public function testDivide(): void
```

### 3. Arrange-Act-Assert Pattern
```php
public function testGreetReturnsFormattedMessage(): void
{
    // Arrange - set up test data
    $name = 'john';
    
    // Act - execute the code under test
    $result = $this->service->greet($name);
    
    // Assert - verify the result
    $this->assertSame('Hello, John!', $result);
}
```

### 4. Use setUp() for Common Setup
```php
protected function setUp(): void
{
    $this->calculator = new Calculator();
    // This runs before each test method
}
```

### 5. Test Edge Cases and Error Conditions
```php
public function testDivideByZeroThrowsException(): void
{
    $this->expectException(\InvalidArgumentException::class);
    $this->calculator->divide(10, 0);
}
```

## 🔧 PHPUnit Configuration

The configuration is in `phpunit.xml.dist`:

```xml
<?xml version="1.0" encoding="UTF-8"?>
<phpunit xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:noNamespaceSchemaLocation="vendor/phpunit/phpunit/phpunit.xsd"
         colors="true"
         bootstrap="tests/bootstrap.php">
    <testsuites>
        <testsuite name="Project Test Suite">
            <directory>tests</directory>
        </testsuite>
    </testsuites>
</phpunit>
```

## 📊 Code Coverage

Generate code coverage reports to see which code is tested:

```bash
# HTML report
docker-compose exec php php bin/phpunit --coverage-html var/coverage

# Then open var/coverage/index.html in your browser
```

Aim for:
- **80%+ coverage** for business logic
- **100% coverage** for critical code paths
- Don't obsess over 100% - focus on meaningful tests

## 🐛 Debugging Tests

### Add Debug Output
```php
public function testSomething(): void
{
    $result = $this->calculator->add(2, 3);
    dump($result);  // Symfony's dump function
    var_dump($result);  // PHP's var_dump
    
    $this->assertSame(5, $result);
}
```

### Run Single Test with Verbose Output
```bash
docker-compose exec php php bin/phpunit --filter testAdd --verbose
```

### Use PHPUnit's --debug Flag
```bash
docker-compose exec php php bin/phpunit --debug
```

## 📚 Additional Resources

- [Symfony Testing Documentation](https://symfony.com/doc/current/testing.html)
- [PHPUnit Documentation](https://phpunit.de/documentation.html)
- [PHPUnit Assertions](https://phpunit.de/manual/current/en/appendixes.assertions.html)
- [Symfony Test Assertions](https://symfony.com/doc/current/testing.html#assertions)

## 🎓 Quick Reference

| Test Type | Extends | Boots Kernel? | Use Case |
|-----------|---------|---------------|----------|
| Unit | `TestCase` | ❌ | Single class, no dependencies |
| Integration | `KernelTestCase` | ✅ | Services with dependencies |
| Application | `WebTestCase` | ✅ | Controllers, HTTP requests |

---

**Start testing with:**
```bash
docker-compose exec php php bin/phpunit --testdox
```
