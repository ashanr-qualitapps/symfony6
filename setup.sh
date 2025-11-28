#!/bin/bash

# Symfony 6 Setup Script for Docker Environment
# This script sets up a complete Symfony 6 application with testing capabilities

set -e

echo "🚀 Setting up Symfony 6 application..."

# Check if we're inside the container
if [ ! -f /.dockerenv ]; then
    echo "❌ This script should be run inside the PHP Docker container"
    echo "Run: docker-compose exec php bash setup.sh"
    exit 1
fi

# Create Symfony project
echo "📦 Creating Symfony 6 skeleton..."
if [ ! -f "composer.json" ]; then
    composer create-project symfony/skeleton:"6.4.*" tmp
    shopt -s dotglob
    mv tmp/* . 2>/dev/null || true
    rm -rf tmp
else
    echo "⚠️  composer.json already exists, skipping skeleton creation"
fi

# Install webapp recipe (includes Twig, Asset Mapper, etc.)
echo "🌐 Installing webapp dependencies..."
composer require webapp --no-interaction

# Install development dependencies
echo "🧪 Installing test pack and PHPUnit bridge..."
composer require --dev symfony/test-pack --no-interaction
composer require --dev symfony/phpunit-bridge --no-interaction

# Install Maker Bundle for development
echo "🔧 Installing Maker Bundle..."
composer require --dev symfony/maker-bundle --no-interaction

# Install Doctrine ORM
echo "💾 Installing Doctrine ORM..."
composer require symfony/orm-pack --no-interaction

# Generate APP_SECRET if needed
if grep -q "changeme_generate_a_new_secret_for_production" .env 2>/dev/null; then
    echo "🔐 Generating APP_SECRET..."
    NEW_SECRET=$(php -r "echo bin2hex(random_bytes(16));")
    sed -i "s/changeme_generate_a_new_secret_for_production/$NEW_SECRET/g" .env
fi

# Create database
echo "🗄️  Creating database..."
php bin/console doctrine:database:create --if-not-exists --no-interaction

# Set proper permissions
echo "🔒 Setting permissions..."
chmod -R 777 var/

echo ""
echo "✅ Symfony 6 application setup complete!"
echo ""
echo "📝 Next steps:"
echo "   - Access the application at http://localhost:8080"
echo "   - Run tests with: php bin/phpunit"
echo "   - Create a controller: php bin/console make:controller"
echo "   - Create an entity: php bin/console make:entity"
echo ""
echo "🎉 Happy coding!"
