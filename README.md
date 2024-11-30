# RapidCents API Transactions

A robust Laravel-based API transaction processing system with Docker containerization.

## System Requirements

- Docker
- Docker Compose
- Git

## Quick Start

1. Clone the repository
```bash
git clone git@github.com:yourusername/api_transactions.git

1. Start the Docker environment
docker-compose up -d

3. Enter the app container
docker-compose exec app bash

4. Install dependencies inside container
docker-compose exec app bash

5. Configure environment
cp .env.example .env
php artisan key:generate

6. Run migrations
php artisan migrate

