#!/bin/bash

# CI/CD Pipeline Local Testing Script
# This script simulates the GitHub Actions CI/CD pipeline locally
# Run this before pushing to ensure all checks pass

set -e  # Exit on any error

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
PHP_VERSION="8.2"
COMPOSER_ALLOW_SUPERUSER=1
DOCKER_COMPOSE_FILE="docker-compose.yml"

# Function to print colored output
print_status() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

print_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

print_header() {
    echo -e "\n${BLUE}========================================${NC}"
    echo -e "${BLUE}$1${NC}"
    echo -e "${BLUE}========================================${NC}\n"
}

# Function to check if command exists
command_exists() {
    command -v "$1" >/dev/null 2>&1
}

# Function to check if Docker is running
check_docker() {
    if ! docker info >/dev/null 2>&1; then
        print_error "Docker is not running. Please start Docker and try again."
        exit 1
    fi
}

# Function to check if docker-compose is available
check_docker_compose() {
    if ! command_exists docker-compose && ! command_exists docker compose; then
        print_error "docker-compose is not installed. Please install Docker Compose."
        exit 1
    fi
}

# Function to run docker-compose commands
run_docker_compose() {
    if command_exists docker-compose; then
        docker-compose "$@"
    else
        docker compose "$@"
    fi
}

# ===========================================
# CODE QUALITY & LINTING
# ===========================================
run_code_quality() {
    print_header "CODE QUALITY & LINTING"

    print_status "Checking if Docker environment is ready..."
    check_docker
    check_docker_compose

    print_status "Starting Docker containers..."
    run_docker_compose up -d --build

    print_status "Waiting for containers to be ready..."
    sleep 10

    # Check if PHP_CodeSniffer is available
    if run_docker_compose exec -T php ./vendor/bin/phpcs --version >/dev/null 2>&1; then
        print_status "Running PHP_CodeSniffer..."
        if run_docker_compose exec -T php ./vendor/bin/phpcs --standard=phpcs.xml.dist src/ tests/ 2>/dev/null; then
            print_success "PHP_CodeSniffer passed"
        else
            print_warning "PHP_CodeSniffer found issues (non-blocking)"
        fi
    else
        print_warning "PHP_CodeSniffer not found, skipping..."
    fi

    # Check if PHP Mess Detector is available
    if run_docker_compose exec -T php ./vendor/bin/phpmd --version >/dev/null 2>&1; then
        print_status "Running PHP Mess Detector..."
        if run_docker_compose exec -T php ./vendor/bin/phpmd src/ text phpmd.xml.dist 2>/dev/null; then
            print_success "PHP Mess Detector passed"
        else
            print_warning "PHP Mess Detector found issues (non-blocking)"
        fi
    else
        print_warning "PHP Mess Detector not found, skipping..."
    fi
}

# ===========================================
# STATIC ANALYSIS
# ===========================================
run_static_analysis() {
    print_header "STATIC ANALYSIS (PHPStan)"

    if run_docker_compose exec -T php ./vendor/bin/phpstan --version >/dev/null 2>&1; then
        print_status "Running PHPStan..."
        if run_docker_compose exec -T php ./vendor/bin/phpstan analyse --configuration=phpstan.dist.neon --memory-limit=1G; then
            print_success "PHPStan analysis passed"
        else
            print_error "PHPStan analysis failed"
            return 1
        fi
    else
        print_warning "PHPStan not found, skipping..."
    fi
}

# ===========================================
# UNIT & INTEGRATION TESTS
# ===========================================
run_tests() {
    print_header "UNIT & INTEGRATION TESTS (PHPUnit)"

    print_status "Setting up test database..."
    run_docker_compose exec -T php php bin/console doctrine:database:create --env=test --if-not-exists || true
    run_docker_compose exec -T php php bin/console doctrine:migrations:migrate --env=test --no-interaction || true

    # Ensure login_attempts table exists for rate limiting tests
    run_docker_compose exec -T php php bin/console doctrine:query:sql "CREATE TABLE IF NOT EXISTS login_attempts (id INT AUTO_INCREMENT NOT NULL, identifier VARCHAR(255) NOT NULL, attempts INT NOT NULL DEFAULT 0, last_attempt DATETIME NOT NULL, PRIMARY KEY(id), UNIQUE INDEX UNIQ_login_attempts_identifier (identifier)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB" --env=test || true

    print_status "Running PHPUnit tests..."
    if run_docker_compose exec -T php ./vendor/bin/phpunit --configuration phpunit.dist.xml --coverage-text; then
        print_success "PHPUnit tests passed"
    else
        print_error "PHPUnit tests failed"
        return 1
    fi
}

# ===========================================
# SECURITY TESTS
# ===========================================
run_security_checks() {
    print_header "SECURITY SCANNING"

    # Symfony Security Checker
    if command_exists composer; then
        print_status "Running Composer Audit..."
        if composer audit --format=plain; then
            print_success "Composer Audit passed"
        else
            print_warning "Composer Audit found issues"
        fi
    fi

    # Local PHP Security Checker
    if [ -f "security-checker" ]; then
        print_status "Running Local PHP Security Checker..."
        if ./security-checker security:check composer.lock; then
            print_success "Local PHP Security Checker passed"
        else
            print_warning "Local PHP Security Checker found issues"
        fi
    else
        print_warning "Local PHP Security Checker not found, downloading..."
        if command_exists curl; then
            curl -L https://github.com/fabpot/local-php-security-checker/releases/download/v2.0.6/local-php-security-checker_2.0.6_linux_amd64 -o security-checker
            chmod +x security-checker
            if ./security-checker security:check composer.lock; then
                print_success "Local PHP Security Checker passed"
            else
                print_warning "Local PHP Security Checker found issues"
            fi
        fi
    fi
}

# ===========================================
# DEPENDENCY VULNERABILITY SCANNING
# ===========================================
run_dependency_scan() {
    print_header "DEPENDENCY VULNERABILITY SCANNING"

    # Trivy filesystem scan
    if command_exists trivy; then
        print_status "Running Trivy filesystem scan..."
        trivy fs --format table --exit-code 0 --severity CRITICAL,HIGH . || print_warning "Trivy scan completed with warnings"
    else
        print_warning "Trivy not installed, skipping filesystem scan..."
    fi

    # Snyk (if available)
    if command_exists snyk; then
        print_status "Running Snyk vulnerability scan..."
        snyk test --severity-threshold=high || print_warning "Snyk scan completed with warnings"
    else
        print_warning "Snyk not installed, skipping..."
    fi
}

# ===========================================
# ARCHITECTURE ANALYSIS
# ===========================================
run_architecture_check() {
    print_header "ARCHITECTURE ANALYSIS (Deptrac)"

    if run_docker_compose exec -T php ./vendor/bin/deptrac --version >/dev/null 2>&1; then
        print_status "Running Deptrac analysis..."
        if run_docker_compose exec -T php ./vendor/bin/deptrac analyze --config-file=deptrac.yaml; then
            print_success "Deptrac analysis passed"
        else
            print_error "Deptrac analysis failed"
            return 1
        fi
    else
        print_warning "Deptrac not found, skipping..."
    fi
}

# ===========================================
# DOCKER BUILD TEST
# ===========================================
run_docker_build_test() {
    print_header "DOCKER BUILD TEST"

    print_status "Testing Docker build..."
    if docker build -t symfony6-test .; then
        print_success "Docker build successful"

        # Run Trivy on built image
        if command_exists trivy; then
            print_status "Running Trivy on Docker image..."
            trivy image --format table --exit-code 0 --severity CRITICAL,HIGH symfony6-test || print_warning "Trivy image scan completed with warnings"
        fi

        # Clean up
        docker rmi symfony6-test || true
    else
        print_error "Docker build failed"
        return 1
    fi
}

# ===========================================
# SECRET SCANNING
# ===========================================
run_secret_scan() {
    print_header "SECRET SCANNING"

    # TruffleHog
    if command_exists trufflehog; then
        print_status "Running TruffleHog secret scan..."
        trufflehog filesystem --directory . --only-verified || print_warning "TruffleHog scan completed"
    else
        print_warning "TruffleHog not installed, skipping..."
    fi

    # Gitleaks
    if command_exists gitleaks; then
        print_status "Running Gitleaks secret scan..."
        gitleaks detect --verbose --redact || print_warning "Gitleaks scan completed"
    else
        print_warning "Gitleaks not installed, skipping..."
    fi
}

# ===========================================
# MAIN EXECUTION
# ===========================================
main() {
    local start_time=$(date +%s)
    local failed_checks=()

    print_header "CI/CD PIPELINE LOCAL TESTING"
    print_status "Starting comprehensive quality checks..."
    print_status "This may take several minutes..."

    # Run all checks
    checks=(
        "run_code_quality:Code Quality & Linting"
        "run_static_analysis:Static Analysis"
        "run_architecture_check:Architecture Analysis"
        "run_tests:Unit & Integration Tests"
        "run_security_checks:Security Scanning"
        "run_dependency_scan:Dependency Vulnerability Scanning"
        "run_secret_scan:Secret Scanning"
        "run_docker_build_test:Docker Build Test"
    )

    for check in "${checks[@]}"; do
        IFS=':' read -r func description <<< "$check"
        print_status "Running: $description"

        if ! $func; then
            failed_checks+=("$description")
            print_error "$description failed"
        else
            print_success "$description completed successfully"
        fi
        echo
    done

    # Cleanup
    print_status "Cleaning up Docker containers..."
    run_docker_compose down -v 2>/dev/null || true

    # Results
    local end_time=$(date +%s)
    local duration=$((end_time - start_time))

    print_header "CI/CD TESTING RESULTS"
    echo "Total execution time: ${duration} seconds"

    if [ ${#failed_checks[@]} -eq 0 ]; then
        print_success "All checks passed! 🎉"
        print_success "You can safely push your changes to trigger the CI/CD pipeline."
        exit 0
    else
        print_error "The following checks failed:"
        for failed_check in "${failed_checks[@]}"; do
            echo -e "  ${RED}✗${NC} $failed_check"
        done
        echo
        print_error "Please fix the issues before pushing to CI/CD."
        exit 1
    fi
}

# ===========================================
# HELP FUNCTION
# ===========================================
show_help() {
    cat << EOF
CI/CD Pipeline Local Testing Script

This script simulates the GitHub Actions CI/CD pipeline locally to ensure
all quality checks pass before pushing changes.

USAGE:
    $0 [OPTIONS]

OPTIONS:
    -h, --help          Show this help message
    --code-quality      Run only code quality checks
    --static-analysis   Run only static analysis
    --tests            Run only unit tests
    --security         Run only security checks
    --dependency-scan  Run only dependency scanning
    --architecture     Run only architecture analysis
    --docker-build     Run only Docker build test
    --secret-scan      Run only secret scanning
    --all              Run all checks (default)

EXAMPLES:
    $0                    # Run all checks
    $0 --tests           # Run only tests
    $0 --code-quality    # Run only code quality checks

REQUIREMENTS:
    - Docker and Docker Compose
    - PHP ${PHP_VERSION}
    - All quality tools (installed via composer)

EOF
}

# ===========================================
# ARGUMENT PARSING
# ===========================================
case "${1:-}" in
    -h|--help)
        show_help
        exit 0
        ;;
    --code-quality)
        run_code_quality
        ;;
    --static-analysis)
        run_static_analysis
        ;;
    --tests)
        check_docker
        check_docker_compose
        run_docker_compose up -d --build
        sleep 10
        run_tests
        run_docker_compose down -v 2>/dev/null || true
        ;;
    --security)
        run_security_checks
        ;;
    --dependency-scan)
        run_dependency_scan
        ;;
    --architecture)
        check_docker
        check_docker_compose
        run_docker_compose up -d --build
        sleep 10
        run_architecture_check
        run_docker_compose down -v 2>/dev/null || true
        ;;
    --docker-build)
        run_docker_build_test
        ;;
    --secret-scan)
        run_secret_scan
        ;;
    --all|"")
        main
        ;;
    *)
        print_error "Unknown option: $1"
        echo "Use '$0 --help' for usage information."
        exit 1
        ;;
esac