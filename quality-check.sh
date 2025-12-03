#!/bin/bash

# Symfony 6 Code Quality Tools Runner
# This script runs various code quality tools and provides a comprehensive report

set -e  # Exit on any error

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
DOCKER_COMPOSE="docker-compose"
PHP_CONTAINER="php"
COVERAGE_DIR="var/coverage"
LOG_FILE="var/logs/quality-check-$(date +%Y%m%d-%H%M%S).log"

# Initialize variables
RUN_IN_DOCKER=true
GENERATE_COVERAGE=false
VERBOSE=false
EXIT_CODE=0
FAILED_TOOLS=()

# Functions
print_header() {
    echo -e "${BLUE}================================================${NC}"
    echo -e "${BLUE}  Symfony 6 Code Quality Check${NC}"
    echo -e "${BLUE}================================================${NC}"
    echo ""
}

print_section() {
    echo -e "${YELLOW}[$1]${NC} $2"
}

print_success() {
    echo -e "${GREEN}✓${NC} $1"
}

print_error() {
    echo -e "${RED}✗${NC} $1"
}

print_info() {
    echo -e "${BLUE}ℹ${NC} $1"
}

run_command() {
    local tool_name="$1"
    local command="$2"
    local description="$3"

    print_section "$tool_name" "$description"

    if [ "$VERBOSE" = true ]; then
        echo "Command: $command"
    fi

    if eval "$command" 2>&1; then
        print_success "$tool_name completed successfully"
        return 0
    else
        local exit_code=$?
        print_error "$tool_name failed (exit code: $exit_code)"
        FAILED_TOOLS+=("$tool_name")
        EXIT_CODE=$exit_code
        return $exit_code
    fi
}

show_usage() {
    echo "Usage: $0 [OPTIONS]"
    echo ""
    echo "Options:"
    echo "  -l, --local          Run tools locally (not in Docker)"
    echo "  -c, --coverage       Generate test coverage report"
    echo "  -v, --verbose        Show verbose output"
    echo "  -h, --help           Show this help message"
    echo ""
    echo "Examples:"
    echo "  $0                    # Run all tools in Docker"
    echo "  $0 --local           # Run all tools locally"
    echo "  $0 --coverage        # Run with coverage report"
    echo "  $0 --local --verbose # Run locally with verbose output"
}

# Parse command line arguments
while [[ $# -gt 0 ]]; do
    case $1 in
        -l|--local)
            RUN_IN_DOCKER=false
            shift
            ;;
        -c|--coverage)
            GENERATE_COVERAGE=true
            shift
            ;;
        -v|--verbose)
            VERBOSE=true
            shift
            ;;
        -h|--help)
            show_usage
            exit 0
            ;;
        *)
            echo "Unknown option: $1"
            show_usage
            exit 1
            ;;
    esac
done

# Setup command prefix
if [ "$RUN_IN_DOCKER" = true ]; then
    CMD_PREFIX="$DOCKER_COMPOSE exec -T $PHP_CONTAINER"
    print_info "Running tools in Docker containers"
else
    CMD_PREFIX=""
    print_info "Running tools locally"
fi

# Create log directory if it doesn't exist
mkdir -p var/logs

# Start logging
exec > >(tee -a "$LOG_FILE") 2>&1

print_header
echo "Started at: $(date)"
echo "Log file: $LOG_FILE"
echo ""

# ===========================================
# PHP UNIT TESTS
# ===========================================
print_section "Database Check" "Verifying test database 'symfony6_test' exists"

if $CMD_PREFIX php bin/console dbal:run-sql "SELECT 1" --env=test 2>&1; then
    print_success "Test database 'symfony6_test' exists"
else
    print_error "Test database 'symfony6_test' does not exist. Aborting."
    exit 1
fi


print_section "Database" "Setting up test database"

# Create login_attempts table BEFORE migrations for both dev and test environments
# Dev environment
$CMD_PREFIX php bin/console dbal:run-sql "CREATE TABLE IF NOT EXISTS login_attempts (id INT AUTO_INCREMENT NOT NULL, identifier VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE utf8mb4_unicode_ci, attempts INT DEFAULT 0 NOT NULL, last_attempt DATETIME NOT NULL, PRIMARY KEY(id), UNIQUE INDEX UNIQ_login_attempts_identifier (identifier)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB" || true

# Test environment
$CMD_PREFIX php bin/console dbal:run-sql "CREATE TABLE IF NOT EXISTS login_attempts (id INT AUTO_INCREMENT NOT NULL, identifier VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE utf8mb4_unicode_ci, attempts INT DEFAULT 0 NOT NULL, last_attempt DATETIME NOT NULL, PRIMARY KEY(id), UNIQUE INDEX UNIQ_login_attempts_identifier (identifier)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB" --env=test || true

# Run migrations AFTER creating the table
$CMD_PREFIX php bin/console doctrine:migrations:migrate --env=test --no-interaction || true

# Update schema to match entities (excluding extra tables like login_attempts)
$CMD_PREFIX php bin/console doctrine:schema:update --force --env=test || true

# Verify database schema
print_section "Database" "Verifying test database schema"
if $CMD_PREFIX php bin/console doctrine:schema:validate --env=test; then
    print_success "Database schema validation passed"
else
    print_error "Database schema validation failed"
    FAILED_TOOLS+=("Database Schema")
    EXIT_CODE=1
fi

if [ "$GENERATE_COVERAGE" = true ]; then
    run_command "PHPUnit" "$CMD_PREFIX php bin/phpunit --coverage-html=$COVERAGE_DIR --coverage-text" "Running PHPUnit tests with coverage"
    if [ $? -eq 0 ] && [ -d "$COVERAGE_DIR" ]; then
        print_info "Coverage report generated in: $COVERAGE_DIR/index.html"
    fi
else
    run_command "PHPUnit" "$CMD_PREFIX php bin/phpunit" "Running PHPUnit tests"
fi

# ===========================================
# PHP CODE SNIFFER
# ===========================================
run_command "PHPCS" "$CMD_PREFIX ./vendor/bin/phpcs --standard=phpcs.xml.dist src/ tests/" "Checking code style with PHP CodeSniffer"

# ===========================================
# PHP STAN
# ===========================================
run_command "PHPStan" "$CMD_PREFIX ./vendor/bin/phpstan analyse --configuration=phpstan.dist.neon --memory-limit=1G" "Running static analysis with PHPStan"

# ===========================================
# PHP MESS DETECTOR
# ===========================================
run_command "PHPMD" "$CMD_PREFIX ./vendor/bin/phpmd src/ text phpmd.xml.dist" "Running code quality analysis with PHP Mess Detector"

# ===========================================
# DEPTRAC
# ===========================================
run_command "Deptrac" "$CMD_PREFIX ./vendor/bin/deptrac analyze" "Running layered architecture analysis with Deptrac"

# ===========================================
# SECURITY CHECKS
# ===========================================
print_section "Security" "Running security checks"

# Symfony Security Checker
if run_command "Symfony Security" "$CMD_PREFIX symfony check:security" "Checking Symfony security advisories"; then
    print_success "Symfony security check passed"
else
    print_error "Symfony security check failed"
fi

# Composer Audit
if run_command "Composer Audit" "$CMD_PREFIX composer audit" "Running Composer security audit"; then
    print_success "Composer audit passed"
else
    print_error "Composer audit failed"
fi

# ===========================================
# SUMMARY
# ===========================================
echo ""
echo -e "${BLUE}================================================${NC}"
echo -e "${BLUE}  SUMMARY${NC}"
echo -e "${BLUE}================================================${NC}"

if [ ${#FAILED_TOOLS[@]} -eq 0 ]; then
    echo -e "${GREEN}✓ All code quality checks passed!${NC}"
    echo ""
    print_info "Your code meets all quality standards."
else
    echo -e "${RED}✗ Some quality checks failed!${NC}"
    echo ""
    echo "Failed tools:"
    for tool in "${FAILED_TOOLS[@]}"; do
        echo -e "  ${RED}• $tool${NC}"
    done
    echo ""
    print_info "Please fix the issues above before proceeding."
fi

echo "Finished at: $(date)"
echo "Total execution time: $SECONDS seconds"
echo "Detailed log: $LOG_FILE"

# Exit with appropriate code
if [ ${#FAILED_TOOLS[@]} -eq 0 ]; then
    exit 0
else
    exit 1
fi