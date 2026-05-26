# AI Chatbot Hub Pro

Multi-website AI chatbot SaaS: embeddable widget, live agent inbox, CRM leads, knowledge base, webhooks, and per-site configuration.

## Quick start (local)

```powershell
composer install
cp .env.example .env
php -c php-cli.ini artisan key:generate
php -c php-cli.ini artisan migrate --force
php -c php-cli.ini artisan db:seed
npm ci && npm run build
powershell -File serve.ps1
```

- **Demo site:** http://127.0.0.1:8000/demo/skycut  
- **Dashboard:** http://127.0.0.1:8000/login — `admin@aichatbothub.local` / `password`

## Tests

```powershell
php -c php-cli.ini vendor/bin/phpunit
```

## UI framework

Reusable Blade components (`<x-dash.*>`) and CSS layers — see [docs/FRAMEWORK.md](docs/FRAMEWORK.md).

## Deploy

```powershell
powershell -File scripts/deploy-production.ps1
```

Full guide: [docs/DEPLOYMENT.md](docs/DEPLOYMENT.md)

Pre-flight: `php artisan platform:doctor`

## Docs

| Doc | Description |
|-----|-------------|
| [PLATFORM.md](docs/PLATFORM.md) | Feature map |
| [API.md](docs/API.md) | HTTP endpoints |
| [FRAMEWORK.md](docs/FRAMEWORK.md) | UI / layout framework |
| [DEPLOYMENT.md](docs/DEPLOYMENT.md) | Production setup |
