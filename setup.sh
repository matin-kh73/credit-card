#!/bin/bash

GREEN='\033[0;32m'
BLUE='\033[0;34m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m'

REBUILD=false
SKIP_DB=false
SKIP_DATA=false

while [[ $# -gt 0 ]]; do
    case $1 in
        --rebuild)
            REBUILD=true
            shift
            ;;
        --skip-db)
            SKIP_DB=true
            shift
            ;;
        --skip-data)
            SKIP_DATA=true
            shift
            ;;
        *)
            echo -e "${RED}Unknown option: $1${NC}"
            echo "Usage: $0 [--rebuild] [--skip-db] [--skip-data]"
            exit 1
            ;;
    esac
done

echo -e "${BLUE}Starting Credit Card Comparison Platform setup...${NC}"

if ! docker info > /dev/null 2>&1; then
    echo -e "${RED}Docker is not running. Please start Docker first.${NC}"
    exit 1
fi

if [ ! -f "compose.yaml" ] && [ ! -f "docker-compose.yaml" ] && [ ! -f "docker-compose.yml" ]; then
    echo -e "${RED}No Docker Compose file found. Please ensure you have one of the following files:${NC}"
    echo -e "  - compose.yaml"
    echo -e "  - docker-compose.yaml"
    echo -e "  - docker-compose.yml"
    exit 1
fi

COMPOSE_FILE="compose.yaml"
if [ ! -f "$COMPOSE_FILE" ]; then
    COMPOSE_FILE="docker-compose.yaml"
    if [ ! -f "$COMPOSE_FILE" ]; then
        COMPOSE_FILE="docker-compose.yml"
    fi
fi

echo -e "${BLUE}Using Docker Compose file: $COMPOSE_FILE${NC}"

if [ ! -f .env ]; then
    echo -e "${RED}No .env file found. Please create one with the required configuration.${NC}"
    exit 1
fi

set -a
source .env
set +a

if [ -z "$MYSQL_DATABASE" ] || [ -z "$MYSQL_USER" ] || [ -z "$MYSQL_PASSWORD" ]; then
    echo -e "${RED}Required MySQL configuration variables are missing in .env file.${NC}"
    echo -e "Please ensure the following variables are set:"
    echo -e "  - MYSQL_DATABASE"
    echo -e "  - MYSQL_USER"
    echo -e "  - MYSQL_PASSWORD"
    exit 1
fi

echo -e "${BLUE}MySQL Configuration:${NC}"
echo -e "Database: ${MYSQL_DATABASE}"
echo -e "User: ${MYSQL_USER}"
echo -e "Port: ${MYSQL_PORT:-3306}"

if [ "$REBUILD" = true ]; then
    echo -e "${YELLOW}Rebuild mode: Stopping and removing existing containers...${NC}"
    docker compose -f "$COMPOSE_FILE" down -v
    docker compose -f "$COMPOSE_FILE" rm -f
    docker compose -f "$COMPOSE_FILE" build --no-cache
fi

echo -e "${BLUE}Building and starting Docker containers...${NC}"
if [ "$REBUILD" = false ]; then
    docker compose -f "$COMPOSE_FILE" build
fi
docker compose up --pull always -d --wait

echo -e "${BLUE}Waiting for containers to be ready...${NC}"
sleep 15

if ! docker compose -f "$COMPOSE_FILE" ps | grep -q "Up"; then
    echo -e "${RED}Containers failed to start. Please check the logs.${NC}"
    docker compose -f "$COMPOSE_FILE" logs
    exit 1
fi

# Install PHP dependencies immediately after containers are up
echo -e "${BLUE}Installing PHP dependencies...${NC}"
docker compose -f "$COMPOSE_FILE" exec php composer install --no-interaction

# Verify Symfony console is available
echo -e "${BLUE}Verifying Symfony console...${NC}"
if ! docker compose -f "$COMPOSE_FILE" exec php php bin/console --version; then
    echo -e "${RED}Symfony console not found. Attempting to fix...${NC}"
    # Try to reinstall dependencies
    docker compose -f "$COMPOSE_FILE" exec php composer install --no-interaction
    # Verify again
    if ! docker compose -f "$COMPOSE_FILE" exec php php bin/console --version; then
        echo -e "${RED}Failed to install Symfony console. Please check your composer.json and try again.${NC}"
        exit 1
    fi
fi

echo -e "${BLUE}Clearing cache...${NC}"
docker compose -f "$COMPOSE_FILE" exec php php bin/console cache:clear

if [ "$SKIP_DB" = false ]; then
    echo -e "${BLUE}Setting up database...${NC}"

    echo -e "${BLUE}Debug: Checking MySQL container status...${NC}"
    docker compose -f "$COMPOSE_FILE" ps database

    echo -e "${BLUE}Debug: Checking MySQL logs...${NC}"
    docker compose -f "$COMPOSE_FILE" logs database | tail -n 20

    echo -e "${BLUE}Debug: Testing MySQL connection from PHP container...${NC}"
    docker compose -f "$COMPOSE_FILE" exec php bash -c "mysql -h database -u ${MYSQL_USER} -p${MYSQL_PASSWORD} -e 'SELECT 1;'" || {
        echo -e "${RED}Failed to connect to MySQL from PHP container${NC}"
        echo -e "${YELLOW}Debug: Checking PHP container network...${NC}"
        docker compose -f "$COMPOSE_FILE" exec php ping -c 1 database
        echo -e "${YELLOW}Debug: Checking PHP container environment...${NC}"
        docker compose -f "$COMPOSE_FILE" exec php env | grep MYSQL
    }

    echo -e "${BLUE}Waiting for MySQL to be ready...${NC}"
    until docker compose -f "$COMPOSE_FILE" exec database mysqladmin ping -h"localhost" -P"${MYSQL_PORT:-3306}" -u"${MYSQL_USER}" -p"${MYSQL_PASSWORD}" --silent; do
        echo -e "${YELLOW}Waiting for MySQL...${NC}"
        sleep 2
    done

    echo -e "${BLUE}Debug: Checking MySQL user permissions...${NC}"
    docker compose -f "$COMPOSE_FILE" exec database mysql -u"${MYSQL_USER}" -p"${MYSQL_PASSWORD}" -e "SHOW GRANTS;"

    echo -e "${BLUE}Creating database '${MYSQL_DATABASE}'...${NC}"
    docker compose -f "$COMPOSE_FILE" exec database mysql -u"${MYSQL_USER}" -p"${MYSQL_PASSWORD}" -e "CREATE DATABASE IF NOT EXISTS ${MYSQL_DATABASE};"

    # Verify Doctrine is installed
    echo -e "${BLUE}Verifying Doctrine installation...${NC}"
    if ! docker compose -f "$COMPOSE_FILE" exec php composer show doctrine/orm; then
        echo -e "${YELLOW}Installing Doctrine ORM...${NC}"
        docker compose -f "$COMPOSE_FILE" exec php composer require symfony/orm-pack --no-interaction
    fi

    # Update database URL in .env.local
    echo -e "${BLUE}Debug: Current .env.local content...${NC}"
    docker compose -f "$COMPOSE_FILE" exec php cat .env.local || true

    echo -e "${BLUE}Updating database configuration...${NC}"
    DATABASE_URL="mysql://${MYSQL_USER}:${MYSQL_PASSWORD}@database:${MYSQL_PORT:-3306}/${MYSQL_DATABASE}?serverVersion=8.0.32&charset=utf8mb4"
    docker compose -f "$COMPOSE_FILE" exec php bash -c "echo 'DATABASE_URL=\"${DATABASE_URL}\"' > .env.local"

    echo -e "${BLUE}Debug: Updated .env.local content...${NC}"
    docker compose -f "$COMPOSE_FILE" exec php cat .env.local

    echo -e "${BLUE}Clearing cache after configuration update...${NC}"
    docker compose -f "$COMPOSE_FILE" exec php php bin/console cache:clear

    echo -e "${BLUE}Debug: Testing database connection with Doctrine...${NC}"
    docker compose -f "$COMPOSE_FILE" exec php php bin/console doctrine:database:create --if-not-exists --verbose

    echo -e "${BLUE}Creating database schema...${NC}"
    docker compose -f "$COMPOSE_FILE" exec php php bin/console doctrine:schema:create --no-interaction --verbose

    echo -e "${BLUE}Running database migrations...${NC}"
    docker compose -f "$COMPOSE_FILE" exec php php bin/console doctrine:migrations:migrate --no-interaction

    echo -e "${BLUE}Verifying database connection...${NC}"
    if ! docker compose -f "$COMPOSE_FILE" exec php php bin/console doctrine:database:create --if-not-exists; then
        echo -e "${RED}Failed to connect to the database. Please check your configuration.${NC}"
        echo -e "${YELLOW}Current database configuration:${NC}"
        echo -e "Database: ${MYSQL_DATABASE}"
        echo -e "User: ${MYSQL_USER}"
        echo -e "Host: database"
        echo -e "Port: ${MYSQL_PORT:-3306}"
        echo -e "${YELLOW}Please verify these credentials in your .env file.${NC}"
        exit 1
    fi

    # Then validate the schema
    echo -e "${BLUE}Validating database schema...${NC}"
    VALIDATION_OUTPUT=$(docker compose -f "$COMPOSE_FILE" exec php php bin/console doctrine:schema:validate 2>&1)
    if echo "$VALIDATION_OUTPUT" | grep -q "\[ERROR\]"; then
        echo -e "${RED}Database validation failed. Details:${NC}"
        echo "$VALIDATION_OUTPUT"
        echo -e "${YELLOW}Attempting to fix schema...${NC}"
        docker compose -f "$COMPOSE_FILE" exec php php bin/console doctrine:schema:update --force --no-interaction
        echo -e "${BLUE}Re-validating schema...${NC}"
        if ! docker compose -f "$COMPOSE_FILE" exec php php bin/console doctrine:schema:validate; then
            echo -e "${RED}Database validation still failed after update. Please check your configuration.${NC}"
            exit 1
        fi
    else
        echo -e "${GREEN}Database schema is valid.${NC}"
    fi
fi

if [ "$SKIP_DB" = false ]; then
    echo -e "${BLUE}Creating admin user...${NC}"
    docker compose -f "$COMPOSE_FILE" exec php php bin/console app:create-user admin@example.com admin123 || true
fi

if [ "$SKIP_DATA" = false ]; then
    echo -e "${BLUE}Fetching credit card data...${NC}"
    docker compose -f "$COMPOSE_FILE" exec php php bin/console app:fetch-credit-cards
fi

echo -e "${BLUE}Verifying application status...${NC}"
if curl -s http://localhost > /dev/null; then
    echo -e "${GREEN}Application is running successfully!${NC}"
    echo -e "${BLUE}You can access the application at: http://localhost/credit-cards${NC}"
    if [ "$SKIP_DB" = false ]; then
        echo -e "${BLUE}Admin credentials:${NC}"
        echo -e "Email: admin@example.com"
        echo -e "Password: admin123"
    fi
else
    echo -e "${RED}Application might not be running properly. Please check the logs.${NC}"
    docker compose -f "$COMPOSE_FILE" logs
fi

echo -e "${GREEN}Setup completed!${NC}"
