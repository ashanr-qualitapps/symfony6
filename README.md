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

## License

This project is open-source and available under the MIT License.
