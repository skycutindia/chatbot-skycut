# API reference (stub)

Public widget and authenticated dashboard JSON endpoints. All widget routes use the website `bot_token` in the path and run `ResolveWebsiteByBotToken` + `ValidateWidgetDomain` middleware unless noted.

Base URL: `APP_URL` (e.g. `https://your-app.test`).

## Health (`routes/api.php`)

| Method | Path | Purpose |
|--------|------|---------|
| GET | `/health` | Uptime monitor JSON: `status`, `database`, `timestamp`. Returns `503` if DB unreachable. |

Laravel also exposes `GET /up` (framework health route).

## Widget loader (`public/widget/loader.js`)

Async loader used by the embed snippet. Optional attributes on the loader `<script>` tag:

| Attribute | Purpose |
|-----------|---------|
| `data-bot-token` | Bot token when loading `chatbot.js` directly from embed page |
| `data-cdn-base` | Base URL for widget assets (e.g. `https://cdn.example.com/widget/`). When set, `chatbot.js` is loaded from this path instead of the loader script directory. |

Example with CDN:

```html
<script src="https://YOUR-DOMAIN/widget/loader.js" data-cdn-base="https://cdn.example.com/widget/" async></script>
<script>aichatbot('init',{ website_key:'bot_xxx' });</script>
```

## Widget API (`routes/api.php`)

Prefix: `/api/widget/{botToken}/`

| Method | Path | Purpose |
|--------|------|---------|
| GET | `/config` | Public bot config (appearance, modules, Q&A, version). Cached; `version` bumps on dashboard save. |
| POST | `/start` | Start conversation / pre-chat / greeting |
| POST | `/chat` | Visitor message + AI reply |
| POST | `/close` | Archive conversation |
| POST | `/handoff` | Request live agent |
| POST | `/read` | Mark agent messages read |
| POST | `/reactions` | Message reaction |
| POST | `/attachments` | Upload visitor attachment |
| GET | `/attachments/{attachment}` | Download attachment |
| GET | `/poll` | Poll new messages (visitor) |
| GET | `/history` | Conversation history |
| POST | `/events` | Analytics / widget events |

### Example

```http
GET /api/widget/bot_xxx/config?visitor_id=v_abc
Accept: application/json
```

Response includes `modules` (e.g. `widget_draggable`, `widget_fullscreen`), `appearance`, `version`, and `api.chat` / `api.events` URLs.

## WhatsApp webhook

| Method | Path | Purpose |
|--------|------|---------|
| GET/POST | `/api/webhooks/whatsapp/{organizationSlug}` | Meta WhatsApp webhook |

## Dashboard JSON (`routes/web.php`, session auth + CSRF)

These return JSON when called with `Accept: application/json` and `X-Requested-With: XMLHttpRequest` (typical inbox polling).

| Method | Path | Name | Purpose |
|--------|------|------|---------|
| GET | `/inbox/poll` | `inbox.poll` | Inbox list sync, `awaiting` count, `queue_stats` |
| POST | `/inbox/bulk` | `inbox.bulk` | Bulk assign / close / star / pin / tag |
| POST | `/inbox/presence` | `inbox.presence` | Agent presence heartbeat |
| POST | `/inbox/push/subscribe` | `inbox.push.subscribe` | Web Push subscription (PWA) |
| POST | `/inbox/push/unsubscribe` | `inbox.push.unsubscribe` | Remove push subscription |
| GET | `/inbox/mentions/search` | `inbox.mentions.search` | @mention autocomplete |
| GET | `/websites/{website}/conversations/{conversation}/messages` | `websites.conversations.messages` | Poll messages in active chat (`?after_id=`) |
| POST | `/inbox/{conversation}/reply` | `inbox.reply` | Agent reply |
| POST | `/inbox/{conversation}/attachments` | `inbox.attachments.upload` | Agent attachment |

## HTML-only dashboard routes (not JSON APIs)

Website CRUD, Q&A, knowledge, webhooks config, unanswered bulk (`POST /websites/{website}/unanswered/bulk`), embed download, analytics exports — see `routes/web.php` and `docs/PLATFORM.md`.

## Auth

- **Widget:** `bot_token` + optional domain allowlist (`require_domain_validation` + `allowed_domains`; checks `Origin` / `Referer` host) + rate limits (`WidgetRateLimitService`).
- **Dashboard:** Laravel session (`auth` middleware), role gates (`EnsureRole`).

## Widget loader (`public/widget/loader.js`)

Async loader for `chatbot.js`. Paste the embed snippet from the dashboard or use:

```html
<script src="https://YOUR-DOMAIN/widget/loader.js" data-bot-token="bot_xxx" async></script>
```

| Attribute | Purpose |
|-----------|---------|
| `data-bot-token` | Required on `chatbot.js`; loader copies it when injecting the main script |
| `data-cdn-base` | Optional CDN base URL for static assets (e.g. `https://cdn.example.com/widget/`). When set, the loader loads `{base}chatbot.js` instead of the path relative to `loader.js` |

Example with CDN:

```html
<script
  src="https://YOUR-DOMAIN/widget/loader.js"
  data-bot-token="bot_xxx"
  data-cdn-base="https://cdn.example.com/widget/"
  async
></script>
```

## Rate limiting

When per-minute or per-hour limits are exceeded, widget endpoints return **429** with:

```json
{
  "error": "rate_limit_exceeded",
  "message": "You're sending messages too quickly. Please wait a moment and try again."
}
```

The widget shows `message` in the chat panel when a visitor sends too fast.

## Related

- Architecture: [PLATFORM.md](./PLATFORM.md)
- Embed snippet: `Website::embedSnippet()` / websites embed page
