# Credit Card Comparison Platform

A Symfony-based web application for comparing credit cards with features like filtering, user-specific edits, and detailed card information.

## Quick Start

1. **Clone the Repository**
   ```bash
   git clone [your-repository-url]
   cd symfony-docker
   ```

2. **Create Environment File**
   ```bash
   # Create .env file from the example
   cp .env.example .env
   ```
   This will create your environment configuration file with default settings.

3. **Run the Setup Script**
   ```bash
   # Make the script executable
   chmod +x setup.sh
   
   # Run the setup script
   ./setup.sh
   ```

   The setup script supports the following options:
   - `--rebuild`: Completely rebuild the containers (useful for major changes)
   - `--skip-db`: Skip database setup and user creation
   - `--skip-data`: Skip fetching credit card data

   Examples:
   ```bash
   # Normal setup
   ./setup.sh

   # Rebuild everything from scratch
   ./setup.sh --rebuild

   # Skip database setup
   ./setup.sh --skip-db

   # Skip fetching credit card data
   ./setup.sh --skip-data

   # Rebuild and skip database setup
   ./setup.sh --rebuild --skip-db
   ```

   This will automatically:
   - Build and start Docker containers
   - Create the database
   - Run migrations
   - Install dependencies
   - Create an admin user
   - Fetch initial credit card data
   - Set up proper permissions
   - Verify the application status

   Default admin credentials:
   - Email: admin@example.com
   - Password: admin123
