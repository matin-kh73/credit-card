#!/bin/bash

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

print_status() {
    echo -e "${GREEN}[✓] $1${NC}"
}

print_error() {
    echo -e "${RED}[✗] $1${NC}"
}

print_warning() {
    echo -e "${YELLOW}[!] $1${NC}"
}

ask_confirmation() {
    read -p "Do you want to $1? (y/n) " -n 1 -r
    echo
    if [[ $REPLY =~ ^[Yy]$ ]]; then
        return 0
    else
        return 1
    fi
}

check_docker() {
    if ! command -v docker &> /dev/null; then
        print_error "Docker is not installed. Please install Docker first."
        exit 1
    fi
    print_status "Docker is installed"
}

check_docker_compose() {
    if ! command -v docker-compose &> /dev/null; then
        print_error "Docker Compose is not installed. Please install Docker Compose first."
        exit 1
    fi
    print_status "Docker Compose is installed"
}

setup_env() {
    if [ ! -f .env ]; then
        print_status "Creating .env file..."
        cp .env.example .env
        print_status "Created .env file from .env.example"
    else
        print_warning ".env file already exists"
    fi
}

setup_test_env() {
    print_status "Setting up test environment..."

    if [ ! -f .env.test ]; then
        print_status "Creating .env.test file..."
        cp .env.test.example .env.test
        print_status "Created .env.test file from .env.test.example"
    else
        print_warning ".env.test file already exists"
    fi

    if ! grep -q "APP_ENV=test" .env.test; then
        echo "APP_ENV=test" >> .env.test
    fi

    print_status "Setting up test database..."

    print_status "Waiting for database to be ready..."
    until docker-compose exec -T database mysqladmin ping -h"localhost" -P"3306" -u"root" -p"toor" --silent; do
        echo -n "."
        sleep 1
    done
    echo

    docker-compose exec -T php bin/console doctrine:database:create --if-not-exists --env=test
    if [ $? -ne 0 ]; then
        print_error "Failed to create test database"
        exit 1
    fi

    docker-compose exec -T php bin/console doctrine:migrations:migrate --no-interaction --env=test
    if [ $? -ne 0 ]; then
        print_error "Failed to run migrations on test database"
        exit 1
    fi

    print_status "Clearing cache for test environment..."
    docker-compose exec -T php bin/console cache:clear --env=test
    if [ $? -ne 0 ]; then
        print_error "Failed to clear cache for test environment"
        exit 1
    fi

    print_status "Verifying test environment..."
    if ! docker-compose exec -T php bin/console debug:container --env=test; then
        print_error "Test environment verification failed"
        exit 1
    fi

    print_status "Test database setup completed"
}

start_containers() {
    print_status "Building and starting Docker containers..."
	docker compose up --pull always -d --wait
    print_status "Docker containers are running"
}

wait_for_services() {
    print_status "Waiting for services to be ready..."
    sleep 15
    print_status "Services are ready"
}

install_dependencies() {
    print_status "Installing PHP dependencies..."
    docker compose exec -T php composer install --no-interaction
    print_status "PHP dependencies installed"
}

setup_database() {
    print_status "Setting up main database..."
    docker-compose exec -T php bin/console doctrine:database:create --if-not-exists
    docker-compose exec -T php bin/console doctrine:migrations:migrate --no-interaction

    print_status "Creating default user..."
    docker-compose exec -T php bin/console app:create-user admin@example.com admin123
    print_status "Default user created with email: admin@example.com and password: admin123"

    print_status "Fetching credit card data..."
    docker-compose exec -T php bin/console app:fetch-credit-cards

    print_status "Main database setup completed"
}

run_tests() {
    print_status "Running tests..."
    docker-compose exec -T php bin/phpunit
    if [ $? -eq 0 ]; then
        print_status "All tests passed!"
    else
        print_error "Some tests failed. Please check the output above."
        exit 1
    fi
}

main() {
    print_status "Starting project setup..."

    check_docker
    check_docker_compose

    setup_env

    start_containers

    wait_for_services

    install_dependencies

    setup_database

    if ask_confirmation "setup test environment and run tests"; then
        setup_test_env
        run_tests
    else
        print_warning "Skipping test environment setup and tests"
    fi

    print_status "Setup completed successfully!"
    print_status "You can now access the application at https://localhost/credit-cards"
}

main
