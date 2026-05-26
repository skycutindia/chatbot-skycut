# Production deployment

## Requirements

- PHP 8.3+ with: `openssl`, `mbstring`, `pdo_mysql` (or `pdo_sqlite`), `curl`, `fileinfo`, `json`, `zip`
- MySQL 8+ (recommended) or SQLite for small installs
- Node 20+ (build assets once)
- Composer 2.x
- Optional: Redis, Supervisor (queues), Nginx/Apache, Reverb (live agent WebSockets)

## 1. Build & configure

```bash
composer install --no-dev --optimize-autoloader
cp .env.example .env
php artisan key:generate
```

Set production `.env`:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_DATABASE=chatbot_hub
DB_USERNAME=...
DB_PASSWORD=...

SESSION_DRIVER=database
SESSION_SECURE_COOKIE=true
CACHE_STORE=database
QUEUE_CONNECTION=database

OPENAI_API_KEY=sk-...

REVERB_APP_ID=...
REVERB_APP_KEY=...
REVERB_APP_SECRET=...
REVERB_HOST=your-domain.com
REVERB_PORT=443
REVERB_SCHEME=https
```

```bash
php artisan migrate --force
php artisan db:seed   # optional demo data
npm ci && npm run build
php artisan storage:link
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan platform:doctor
```

## 2. Web server (Nginx)

Point document root to `/public`:

```nginx
server {
    listen 443 ssl http2;
    server_name your-domain.com;
    root /var/www/chatbot-hub/public;

    index index.php;
    client_max_body_size 20M;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~* \.(js|css|png|jpg|jpeg|gif|ico|svg|woff2)$ {
        expires 7d;
        access_log off;
    }
}
```

## 3. Queue & scheduler

```bash
# Supervisor: php artisan queue:work --sleep=3 --tries=3
# Crontab: * * * * * php /var/www/chatbot-hub/artisan schedule:run
```

## 4. Reverb (optional live inbox)

```bash
php artisan reverb:start
```

Set `BROADCAST_CONNECTION=reverb` and Vite `VITE_REVERB_*` to match public URL.

## 5. Widget on client sites

Embed (replace token):

```html
<script src="https://your-domain.com/widget/loader.js"
        data-bot-token="YOUR_BOT_TOKEN"
        async></script>
```

Or download plugin ZIP from dashboard → **Plugin**.

CDN: `data-cdn-base="https://cdn.your-domain.com"` on the script tag.

## 6. Windows local dev

```powershell
powershell -File serve.ps1
```

Do **not** use plain `php artisan serve` on Windows without `php-cli.ini` — enable extensions via `php-cli.ini` or `serve.ps1`.

## 7. Health checks

- `GET /up` — Laravel default
- `GET /api/health` — JSON `{ status, database, timestamp }`

## Post-deploy

- HTTPS only (`APP_URL` https — app forces HTTPS in production)
- Restrict `storage/` and `.env` from web
- Set `LOG_LEVEL=warning` or `error`
- Configure mail for invites (`MAIL_*`)
