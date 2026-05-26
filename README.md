# ChatFlow — Enterprise Multi-Tenant AI Chatbot Platform

Production-ready Laravel platform for managing unlimited website chatbots from a centralized dashboard. Similar to Intercom, Crisp, Tidio, and Chatbase — **without** subscriptions, billing, or payment gateways.

## Stack

- **Laravel 13** (compatible with Laravel 12 architecture; requires **PHP 8.3+**)
- **MySQL 8.0**
- **Embeddable JavaScript widget** (fully dynamic, zero hardcoded content)
- **OpenAI-compatible API** for AI responses

## Features

| Area | Capabilities |
|------|----------------|
| Multi-tenancy | Organizations → unlimited websites → independent chatbots |
| Dashboard | Create websites, auto-generate bot token & embed snippet |
| Appearance | Name, avatar, colors, light/dark/auto theme, left/right position |
| Messages | Welcome, typing indicator, offline, outside-hours |
| AI | Model, temperature, confidence threshold, system prompt |
| Knowledge | Categories, articles, tags, synonyms, full-text search |
| Automation | Q&A pairs, trigger keywords, escalation rules |
| Operations | Operating hours, allowed domains, rate limits |
| i18n | Locale + per-key translations |
| Customization | Custom CSS/JS, enable/disable modules |
| Live agents | Reply from dashboard, assign conversations |
| Analytics | Widget opens, chats, events |

## Requirements

- PHP **8.3+** (8.2 minimum for Laravel 13)
- Composer 2.x
- MySQL 8.0+
- Node.js 18+ (for Vite assets, optional)

## Installation

```bash
cd e:\website\chatbot-platform\app

# Install dependencies (PHP 8.3+ required)
php ../composer.phar install --no-dev

# Environment
copy .env.example .env
# Edit .env: set DB_* for MySQL, APP_URL, OPENAI_API_KEY

php artisan key:generate
php artisan migrate --seed

php artisan serve
```

### MySQL `.env` example

```env
APP_NAME=ChatFlow
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=chatflow
DB_USERNAME=root
DB_PASSWORD=

OPENAI_API_KEY=sk-your-key-here
OPENAI_BASE_URL=https://api.openai.com/v1
```

## Default login (after seeding)

- **Email:** `admin@chatflow.local`
- **Password:** `password`

## Embed on any website

After creating a website in the dashboard, copy the snippet:

```html
<script src="http://localhost:8000/widget/chatbot.js" data-bot-token="bot_..." async></script>
```

The widget:

1. Calls `GET /api/widget/{token}/config`
2. Renders UI from JSON (colors, messages, suggested questions, modules)
3. Sends messages to `POST /api/widget/{token}/chat`
4. Picks up dashboard changes on next config fetch (cached ~60s by default)

## API endpoints (public widget)

| Method | Path | Description |
|--------|------|-------------|
| GET | `/api/widget/{botToken}/config` | Full chatbot configuration |
| POST | `/api/widget/{botToken}/chat` | Send message & get reply |
| GET | `/api/widget/{botToken}/history` | Conversation history |
| POST | `/api/widget/{botToken}/events` | Analytics events |

## Architecture

```
Organization
  └── Users (owner, admin, agent)
  └── Websites
        ├── ChatbotConfiguration (appearance, AI, security)
        ├── SuggestedQuestions, OperatingHours, AllowedDomains
        ├── Knowledge (categories, articles, tags, synonyms)
        ├── QaPairs, TriggerKeywords, EscalationRules, Translations
        └── Conversations → Messages
```

## Response pipeline

1. **Q&A pairs** (keyword match)
2. **Trigger keywords**
3. **Knowledge base** (full-text / LIKE search, confidence threshold)
4. **AI** (OpenAI chat completions with KB context)
5. **Fallback** offline message

## Security

- Domain allowlist (optional per bot)
- Per-IP rate limiting (minute + hour windows)
- Organization-scoped dashboard access
- Bot token authentication for widget API

## No billing

This build intentionally excludes subscriptions, plans, invoices, and payment gateways. Extend with your own billing layer if needed.

## License

MIT
