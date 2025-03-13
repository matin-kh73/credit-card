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
