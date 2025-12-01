# Symfony 6 Docker Development Makefile

.PHONY: help build up down restart logs shell composer test db-create db-migrate clean

help: ## Show this help
	@echo "Symfony 6 Docker Commands:"
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-16s\033[0m %s\n", $$1, $$2}'

build: ## Build Docker containers
	docker-compose build

up: ## Start Docker containers
	docker-compose up -d

down: ## Stop Docker containers
	docker-compose down

restart: down up ## Restart Docker containers

logs: ## View Docker logs
	docker-compose logs -f

shell: ## Enter PHP container shell
	docker-compose exec php bash

composer: ## Install Composer dependencies
	docker-compose exec php composer install

setup: up ## Setup Symfony application
	docker-compose exec php bash setup.sh

test: ## Run PHPUnit tests
	docker-compose exec php php bin/phpunit

test-coverage: ## Run tests with coverage report
	docker-compose exec php php bin/phpunit --coverage-html var/coverage

db-create: ## Create database
	docker-compose exec php php bin/console doctrine:database:create

db-migrate: ## Run database migrations
	docker-compose exec php php bin/console doctrine:migrations:migrate --no-interaction

cache-clear: ## Clear Symfony cache
	docker-compose exec php php bin/console cache:clear

clean: down ## Clean up containers and volumes
	docker-compose down -v
	rm -rf var/cache/* var/log/*

install: build up setup ## Full installation (build, up, setup)

# ===========================================
# CODE QUALITY
# ===========================================

cs-check: ## Check code style with PHP_CodeSniffer
	docker-compose exec php ./vendor/bin/phpcs --standard=phpcs.xml.dist src/ tests/

cs-fix: ## Fix code style with PHP_CodeSniffer
	docker-compose exec php ./vendor/bin/phpcbf --standard=phpcs.xml.dist src/ tests/

phpstan: ## Run PHPStan static analysis
	docker-compose exec php ./vendor/bin/phpstan analyse --configuration=phpstan.dist.neon --memory-limit=1G

phpmd: ## Run PHP Mess Detector
	docker-compose exec php ./vendor/bin/phpmd src/ text phpmd.xml.dist

lint: cs-check phpstan phpmd ## Run all linting tools

# ===========================================
# SECURITY
# ===========================================

security-check: ## Run Symfony security checker
	docker-compose exec php symfony check:security

composer-audit: ## Run Composer security audit
	docker-compose exec php composer audit

security-all: security-check composer-audit ## Run all security checks

# ===========================================
# PRODUCTION
# ===========================================

build-prod: ## Build production Docker image
	docker build -f Dockerfile.prod -t symfony6-app:latest .

up-prod: ## Start production containers
	docker-compose -f docker-compose.prod.yml up -d

down-prod: ## Stop production containers
	docker-compose -f docker-compose.prod.yml down

logs-prod: ## View production logs
	docker-compose -f docker-compose.prod.yml logs -f

# ===========================================
# CI/CD
# ===========================================

ci: lint test security-all ## Run full CI pipeline locally

ci-quick: cs-check phpstan test ## Run quick CI checks
