# Credit Card Comparison Platform

A Symfony-based web application for comparing credit cards with features like filtering, user-specific edits, and detailed card information.

## Quick Start

1. **Clone the Repository**
   ```bash
   git clone [your-repository-url]
   cd credit-card
   ```

2. **Create Environment File**
   ```bash
   cp .env.example .env
   ```

3. **Run the Setup Script**
   ```bash
   chmod +x setup.sh
   
   ./setup.sh
   ```

   Default admin credentials:
   - Email: admin@example.com
   - Password: admin123

## Running Tests

You can run the tests in several ways:

1. Run all tests:
```bash
docker-compose exec php bin/phpunit
```

2. Run specific test file:
```bash
docker-compose exec php bin/phpunit tests/Unit/Service/CreditCardServiceTest.php
```

### Manual Test Setup

If you want to run tests manually, you need to:

1. Create test environment file:
```bash
cp .env.test.example .env.test
```

2. Create and migrate test database:
```bash
docker-compose exec php bin/console doctrine:database:create --if-not-exists --env=test
docker-compose exec php bin/console doctrine:migrations:migrate --no-interaction --env=test
```
