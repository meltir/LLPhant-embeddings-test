---
name: pgvector
description: Sets up a vector store with PostgreSQL (pgvector) in a Docker Compose stack, including PHP container with pdo_pgsql, Adminer for database management, and required Doctrine packages for PHP integration.
---

# PGVector Docker Setup Skill

Sets up a complete vector store environment using PostgreSQL with the pgvector extension in a Docker Compose stack.

## Services

The stack consists of three services:

1. **db** — PostgreSQL with pgvector extension
2. **php** — PHP CLI container with pdo_pgsql driver
3. **adminer** — Web-based database administration interface

## docker-compose.yml

```yaml
services:
  db:
    image: pgvector/pgvector:pg17
    container_name: pgvector-db
    environment:
      POSTGRES_USER: postgres
      POSTGRES_PASSWORD: password
      POSTGRES_DB: example_db
    ports:
      - "5432:5432"
    volumes:
      - pgdata:/var/lib/postgresql
      - ./postgres/schema.sql:/docker-entrypoint-initdb.d/schema.sql

  php:
    build: .
    container_name: pgvector-php
    environment:
      DB_HOST: db
      DB_PORT: 5432
      DB_USER: postgres
      DB_PASSWORD: password
      DB_NAME: example_db
    volumes:
      - .:/app
    working_dir: /app
    command: sleep infinity
    depends_on:
      - db

  adminer:
    image: adminer
    container_name: pgvector-adminer
    ports:
      - "8080:8080"
    depends_on:
      - db

volumes:
  pgdata:
```

## Dockerfile

```dockerfile
FROM php:8.4-cli
RUN apt-get update && apt-get install -y libpq-dev && rm -rf /var/lib/apt/lists/* \
    && docker-php-ext-install pdo_pgsql
```

## Initial Schema

Create `postgres/schema.sql` to initialize the database with the vector extension:

```sql
-- Enable pgvector extension
CREATE EXTENSION IF NOT EXISTS vector;

-- Create sample table
CREATE TABLE items (
    id SERIAL PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    item_data JSONB,
    embedding vector(1536) -- vector data
);
```

## Required Doctrine Packages

Add these to `composer.json`:

```json
{
    "require": {
        "doctrine/orm": "^2.20.0",
        "doctrine/dbal": "^3.8|^4.0"
    }
}
```

Install with:

```bash
composer install
```

## Environment Variables

The PHP container uses these environment variables for the Doctrine connection:

| Variable | Value | Description |
|---|---|---|
| `DB_HOST` | `db` | Docker service name for the database |
| `DB_PORT` | `5432` | PostgreSQL port |
| `DB_USER` | `postgres` | Database username |
| `DB_PASSWORD` | `password` | Database password |
| `DB_NAME` | `example_db` | Database name |

## Usage

### Start the stack

```bash
docker compose up -d
```

### Run PHP commands inside the container

```bash
docker exec -it pgvector-php php your_script.php
```

### Access Adminer

Open `http://localhost:8080` in a browser. Use the following credentials:

- System: `Adminer`
- Server: `db`
- Username: `postgres`
- Password: `password`
- Database: `example_db`

### Stopping the stack

```bash
docker compose down
```

To remove data volumes as well:

```bash
docker compose down -v
```

## Doctrine DBAL Connection Example

```php
<?php
use Doctrine\DBAL\DriverManager;

$connectionParams = [
    'dbname' => getenv('DB_NAME') ?: 'example_db',
    'user' => getenv('DB_USER') ?: 'postgres',
    'password' => getenv('DB_PASSWORD') ?: 'password',
    'host' => getenv('DB_HOST') ?: 'db',
    'port' => (int)(getenv('DB_PORT') ?: 5432),
    'driver' => 'pdo_pgsql',
];

$conn = DriverManager::getConnection($connectionParams);
```

## Notes

- The `pgvector/pgvector:pg17` image includes the pgvector extension pre-installed for PostgreSQL 17
- The schema SQL file is mounted into `/docker-entrypoint-initdb.d/` so it runs automatically on first startup
- The `pgdata` named volume persists database data across container restarts
- Adminer is a lightweight alternative to phpMyAdmin, specifically useful for PostgreSQL databases
- The PHP container uses `sleep infinity` as its command to keep it running for interactive use; replace with your actual application command in production
