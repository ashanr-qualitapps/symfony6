# Symfony 6 Application with Docker

A Symfony 6.x application created with Symfony Flex, Docker, and complete testing setup.

## Features

- Symfony 6.x with Flex
- PHP 8.2 FPM
- Nginx web server
- MySQL 8.0 database
- Composer for dependency management
- Symfony Test Pack included
- PHPUnit Bridge for testing and deprecation reporting
- Xdebug for debugging and code coverage

## Prerequisites

- Docker
- Docker Compose

## Installation

1. **Build and start the Docker containers:**

```bash
docker-compose up -d --build
```

2. **Install Symfony and dependencies:**

```bash
# Enter the PHP container
docker-compose exec php bash

# Create Symfony application with webapp structure
composer create-project symfony/skeleton:"6.4.*" tmp
mv tmp/* tmp/.* . 2>/dev/null || true
rm -rf tmp

# Install webapp dependencies and test pack
composer require webapp
composer require --dev symfony/test-pack
composer require --dev symfony/phpunit-bridge

# Generate APP_SECRET
php -r "echo bin2hex(random_bytes(16)) . PHP_EOL;" > .app_secret
```

Or use the provided setup script (see below).

## Quick Setup Script

Run this inside the PHP container to set up everything:

```bash
docker-compose exec php bash -c "composer create-project symfony/skeleton:\"6.4.*\" tmp && shopt -s dotglob && mv tmp/* . && rm -rf tmp && composer require webapp && composer require --dev symfony/test-pack symfony/phpunit-bridge"
```

## Usage

### Access the application

- **Web application:** http://localhost:8080
- **Database:** localhost:3306

### Docker Commands

```bash
# Start containers
docker-compose up -d

# Stop containers
docker-compose down

# View logs
docker-compose logs -f

# Enter PHP container
docker-compose exec php bash

# Run Composer commands
docker-compose exec php composer install

# Run Symfony console commands
docker-compose exec php php bin/console

# Clear cache
docker-compose exec php php bin/console cache:clear
```

### Testing

```bash
# Run all tests
docker-compose exec php php bin/phpunit

# Run tests with coverage (requires Xdebug)
docker-compose exec php php bin/phpunit --coverage-html var/coverage

# Run specific test
docker-compose exec php php bin/phpunit tests/Controller/DefaultControllerTest.php
```

### Database

```bash
# Create database
docker-compose exec php php bin/console doctrine:database:create

# Create migration
docker-compose exec php php bin/console make:migration

# Run migrations
docker-compose exec php php bin/console doctrine:migrations:migrate
```

## Project Structure

```
.
├── bin/                    # Executable files (console)
├── config/                 # Configuration files
├── docker/                 # Docker configuration
│   ├── nginx/             # Nginx configuration
│   └── php/               # PHP configuration
├── migrations/            # Database migrations
├── public/                # Public web directory
│   └── index.php         # Front controller
├── src/                   # Application source code
├── tests/                 # Tests directory
├── var/                   # Generated files (cache, logs)
├── vendor/                # Composer dependencies
├── .env                   # Environment variables
├── docker-compose.yml     # Docker Compose configuration
├── Dockerfile            # PHP container definition
└── composer.json         # PHP dependencies
```

## Development

### Create a Controller

```bash
docker-compose exec php php bin/console make:controller DefaultController
```

### Create an Entity

```bash
docker-compose exec php php bin/console make:entity
```

### Install Additional Packages

```bash
# Install Doctrine ORM (if not already installed)
docker-compose exec php composer require symfony/orm-pack

# Install Maker Bundle for code generation
docker-compose exec php composer require --dev symfony/maker-bundle

# Install Security component
docker-compose exec php composer require symfony/security-bundle
```

## Testing Setup

The project includes:

- **Symfony Test Pack:** Provides browser kit, CSS selector, PHPUnit bridge, and DOM crawler
- **PHPUnit Bridge:** Catches deprecations and provides extra testing helpers
- **Code Coverage:** Available via Xdebug

### Writing Tests

Example functional test:

```php
<?php
// tests/Controller/DefaultControllerTest.php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class DefaultControllerTest extends WebTestCase
{
    public function testIndex(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Welcome');
    }
}
```

## Troubleshooting

### Permission Issues

```bash
# Fix permissions
docker-compose exec php chown -R www-data:www-data /var/www/html
```

### Clear All Caches

```bash
docker-compose exec php php bin/console cache:clear
docker-compose exec php php bin/console cache:warmup
```

### Rebuild Containers

```bash
docker-compose down
docker-compose up -d --build --force-recreate
```

## Code Quality & Architecture (Deptrac)

Deptrac is a static analysis tool that helps you enforce layered architecture and dependency rules in your codebase. It's already included as a development dependency in this project (`deptrac/deptrac`) and can be run via the Makefile target or Composer script.

### Why use Deptrac
- Enforce a layered architecture (eg. controllers -> services -> repositories)
- Prevent direct dependencies between layers that should remain isolated
- Fail CI builds when a violation is introduced

### Run Deptrac
- Via Makefile:

```bash
make deptrac
```

- Via Composer script (present in `composer.json`):

```bash
composer deptrac
```

- Directly with the binary (local):

```bash
./vendor/bin/deptrac analyze --formatter=console --report-uncovered
```

- Inside Docker:

```bash
docker-compose exec php ./vendor/bin/deptrac analyze --formatter=console --report-uncovered
```

### Configuration
Deptrac looks for `deptrac.yaml` in the project root by default. If your project does not include this file, you can create a simple starter configuration to get going. Example `deptrac.yaml`:

```yaml
paths:
    - src
exclude_files:
    - tests/*

layers:
    Controller:
        collectors:
            - type: className
                regex: '^App\\\\Controller\\\\'
    Service:
        collectors:
            - type: className
                regex: '^App\\\\Service\\\\'
    Repository:
        collectors:
            - type: className
                regex: '^App\\\\Repository\\\\'
    Entity:
        collectors:
            - type: className
                regex: '^App\\\\Entity\\\\'

ruleset:
    Controller:
        - Service
        - Repository
        - Entity
    Service:
        - Repository
        - Entity
    Repository:
        - Entity
    Entity: []
```

This sample demonstrates a strict layering approach:
- Controllers can depend on Services, Repositories, and Entities
- Services can depend on Repositories and Entities
- Repositories can depend only on Entities
- Entities should not depend on any of the above

Adjust the regex and rules to fit your application's architecture. Run Deptrac after updating the config to verify the rules.

### CI Integration
Add a CI step that runs Deptrac and fails the job if a violation is detected, for example:

```yaml
# GitHub Actions example step
- name: Run Deptrac
    run: composer deptrac
```

Or in Docker-based CI jobs:

```yaml
- name: Run Deptrac
    run: docker-compose exec php ./vendor/bin/deptrac analyze --formatter=console --report-uncovered
```

Deptrac exits with a non-zero code if violations are found, causing the CI job to fail.


## Code Quality & Security Tools

This project uses several tools to ensure code quality, maintainability, security, and consistent style. Below is a quick reference to run them locally and in CI.

Tools included in this repository:
- PHP_CodeSniffer (phpcs): Check coding standards.
- PHP CS Fixer (phpcbf via composer script `cs-fix`): Automatically fix style problems.
- PHPStan: Static analysis (see `phpstan.dist.neon`).
- PHP Mess Detector (phpmd): Find potential bugs and suboptimal code.
- PHPUnit: Unit and integration testing.
- Deptrac: Layered architecture enforcement.
- Composer Audit: Checks known vulnerabilities in Composer packages.
- Symfony Security Checker: Checks project configuration security issues.
- Snyk (CI): Optional dependency scanning service used in CI.

Useful Makefile targets and composer scripts:

- Makefile targets (run inside repo root):

```bash
# Run all linting tools (includes PHPStan, PHPMD, PHPCS and Deptrac)
make lint

# Run single checks
make cs-check
make cs-fix
make phpstan
make phpmd
make deptrac

# Run tests
make test
make test-coverage

# Security checks
make security-check
make composer-audit
```

- Composer scripts (use inside PHP container or locally if dependencies installed):
```bash
composer cs-check
composer cs-fix
composer deptrac
```

CLI commands (run inside the PHP container via Docker Compose):
```bash
docker-compose exec php ./vendor/bin/phpcs --standard=phpcs.xml.dist src/ tests/
docker-compose exec php ./vendor/bin/phpcbf --standard=phpcs.xml.dist src/ tests/
docker-compose exec php ./vendor/bin/phpstan analyse --configuration=phpstan.dist.neon --memory-limit=1G
docker-compose exec php ./vendor/bin/phpmd src/ text phpmd.xml.dist
docker-compose exec php php bin/phpunit --configuration phpunit.dist.xml
docker-compose exec php ./vendor/bin/deptrac analyze --formatter=console --report-uncovered
docker-compose exec php composer audit
docker-compose exec php symfony check:security
```

CI Integration tips:
- Add `make lint` to your CI pipeline to ensure code quality checks run on every PR.
- Add `composer deptrac` to CI to enforce architectural constraints.
- Use `composer audit` and Snyk in CI to scan dependencies for vulnerabilities.
- Fail the CI job when critical checks fail (e.g., `--fail-on-uncovered` or `--fail-on-violation` for Deptrac, `--level` for PHPStan if configured).

Quick fixes:
- Use `make cs-fix` to auto-correct coding standard violations.
- Use `composer deptrac` to detect architecture violations and then update layers or code accordingly.

Notes:
- The Makefile runs these tools inside the project's PHP container to ensure the same environment as CI.
- Adjust rules in `deptrac.yaml` and `phpstan.dist.neon` as your architecture evolves.

## License

This project is open-source and available under the MIT License.
