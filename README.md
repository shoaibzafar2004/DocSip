# DocSip

DocSip is an AI-powered document chat platform. Upload your documents and have natural conversations with them — ask questions, extract key information, and get summaries — without buying a subscription. You only pay for what you use.

## Tech Stack

| Layer | Technology |
|---|---|
| Backend | Laravel 13, PHP 8.4 |
| Frontend | React 19, TypeScript 5.7, Inertia.js v3 |
| Styling | Tailwind CSS v4 |
| Auth | Laravel Fortify |
| Database | PostgreSQL 17 |
| Cache & Queues | Redis |
| Build Tool | Vite 8 |
| Routing (typed) | Laravel Wayfinder |
| Dev Environment | Docker via Laravel Sail |

## Prerequisites

- [Docker Desktop](https://www.docker.com/products/docker-desktop/) (or Docker Engine on Linux)
- PHP 8.4, Composer, and the Laravel installer (for running Artisan commands outside containers)

If PHP/Composer are missing, install them via [php.new](https://php.new):

```bash
# Linux
/bin/bash -c "$(curl -fsSL https://php.new/install/linux/8.4)"

# macOS
/bin/bash -c "$(curl -fsSL https://php.new/install/mac/8.4)"
```

## Local Development Setup

**1. Clone the repository**

```bash
git clone <repo-url>
cd DocSip
```

**2. Copy the environment file**

```bash
cp .env.example .env
```

**3. Install PHP dependencies**

```bash
composer install
```

**4. Build and start Docker containers**

```bash
./vendor/bin/sail up -d
```

This starts three containers: the PHP app server, PostgreSQL, and Redis.

**5. Generate application key**

```bash
./vendor/bin/sail artisan key:generate
```

**6. Run migrations**

```bash
./vendor/bin/sail artisan migrate
```

**7. Install frontend dependencies and start Vite**

```bash
./vendor/bin/sail npm install
./vendor/bin/sail npm run dev
```

The application is now available at **http://localhost**.

## Useful Sail Commands

```bash
# Start containers in the background
./vendor/bin/sail up -d

# Stop containers
./vendor/bin/sail down

# Run Artisan commands
./vendor/bin/sail artisan <command>

# Run migrations
./vendor/bin/sail artisan migrate

# Tail logs
./vendor/bin/sail artisan pail

# Open a shell inside the app container
./vendor/bin/sail shell

# Connect to PostgreSQL
./vendor/bin/sail psql
```

Add a shell alias to avoid typing the full path every time:

```bash
alias sail='./vendor/bin/sail'
```

## Running Tests

```bash
./vendor/bin/sail artisan test --compact
```

Run a specific test file:

```bash
./vendor/bin/sail artisan test --compact tests/Feature/ExampleTest.php
```

Run tests matching a name:

```bash
./vendor/bin/sail artisan test --compact --filter=testName
```

## Code Quality

**PHP formatting** (Laravel Pint):

```bash
./vendor/bin/sail php vendor/bin/pint
```

**TypeScript type checking:**

```bash
npm run types:check
```

**Linting:**

```bash
npm run lint:check
```

**Formatting:**

```bash
npm run format:check
```

## Project Structure

```
app/
├── Actions/          # Single-purpose action classes
├── Concerns/         # Shared traits (password/profile validation rules)
├── Http/
│   ├── Controllers/  # Request handlers
│   ├── Middleware/   # HTTP middleware
│   └── Requests/     # Form request validation
├── Models/           # Eloquent models
├── Services/         # Business logic services
└── Providers/        # Service providers

resources/js/
├── components/       # Reusable React components (UI primitives, shared widgets)
├── hooks/            # Custom React hooks
├── layouts/          # Page layout wrappers
├── pages/            # Inertia page components (one per route)
├── types/            # TypeScript type definitions
└── app.tsx           # Frontend entry point

routes/
├── web.php           # Web routes
└── settings.php      # Settings-related routes

docker/8.4/           # Customised Laravel Sail Dockerfile (PHP 8.4)
```

## Environment Variables

Key variables to configure in `.env`:

| Variable | Description |
|---|---|
| `APP_KEY` | Application encryption key (generated via `artisan key:generate`) |
| `DB_HOST` | Database host (`pgsql` when using Sail) |
| `DB_DATABASE` | Database name |
| `DB_USERNAME` | Database username |
| `DB_PASSWORD` | Database password |
| `REDIS_HOST` | Redis host (`redis` when using Sail) |
| `QUEUE_CONNECTION` | Queue driver (`redis`) |
| `CACHE_STORE` | Cache driver (`redis`) |

## Deployment

DocSip can be deployed to [Laravel Cloud](https://cloud.laravel.com) for managed infrastructure with automatic scaling.

```bash
cloud deploy
```
