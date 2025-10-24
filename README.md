# Dastyar

---

## Tech Stack
- **Backend:** PHP 8.2+, Laravel 12
- **Runtime & Performance:** Octane (RoadRunner), Horizon (queues), Redis
- **Bot:** `irazasyed/telegram-bot-sdk`
- **Admin:** Filament v4
- **Build:** Vite + Tailwind CSS v4, Axios
- **Observability:** Laravel Telescope
- **Container:** Multi-stage Dockerfile (Composer, Node build, PHP CLI + Supervisor)

---

## High-level Architecture

### HTTP + API
- `routes/web.php` – shortlink handler → Telegram deep links (`/s/{slug}`).
- `routes/v1/api.php` – Telegram webhook endpoint:  
  `POST /api/v1/telegram/webhook/{TELEGRAM_BOT_SECRET}`

### Telegram Flow
1. Webhook payload hits `App\Http\Controllers\Telegram\WebhookController`
2. Normalized to `TelegramUpdateDTO`
3. Passed through a pipeline of handlers (spam checks, state machine, commands, etc.)
4. Domain services send replies via the Telegram SDK

### Middleware
- `VerifyTelegramIp` whitelists Telegram CIDR ranges
- `AlwaysOkWebhook` always returns HTTP 200 even on internal errors

### Domain & Admin
- Models: `User`, `UserLink`, `LinkResult`, `TopupRequest`, `WalletTransaction`, `SupportTicket`, `Server`, `MediaFile`, `Category`, …
- Payments: strategy pattern via `Payments\PaymentMethod`
- Filament resources for support tickets & top-ups

### Background Work
- Queues and cache backed by Redis; monitored with Horizon

### Static Assets
- Vite builds to `public/build` (Tailwind v4 plugin configured in `vite.config.js`)

---

## Project Structure (selected)

```
app/
  DTOs/                    # Data transfer objects
  Filament/                # Admin panel (Filament v4)
  Http/Controllers/        # Web + Telegram controllers
  Http/Middleware/         # IP allowlist, webhook guard, etc.
  Jobs/, Notifications/    # Async tasks + user notifications
  Payments/                # Payment strategies
  Pipelines/Telegram/      # Webhook processing steps
  Services/Telegram/       # Bot services (deep links, media, admin inbox)
  Models/                  # Eloquent models
config/
  telegram.php, payment.php, horizon.php, octane.php, ...
database/migrations/       # Users, wallet, support, media, etc.
resources/                 # Blade views, Tailwind, JS, fa locale
```

---

## Key Routes
- `GET /s/{slug}` – builds a Divar link, issues a deep-link token, and redirects to the bot.
- `POST /api/v1/telegram/webhook/{token}` – webhook endpoint; `{token}` must equal `TELEGRAM_BOT_SECRET`.

---

## Environment

Copy `.env.example` to `.env` and provide at least:

```
APP_KEY=             # php artisan key:generate fills this
DB_CONNECTION=...    # your DB settings
REDIS_HOST=127.0.0.1

TELEGRAM_BOT_TOKEN=...
TELEGRAM_BOT_SECRET=...          # used in the webhook URL path
TELEGRAM_BOT_USERNAME=...
TELEGRAM_DEEPLINK_TTL=1800
TELEGRAM_CHANNEL_LOCK=off|on
TELEGRAM_CHANNEL_LINK=@your_channel

ADMIN_USER_ID=...
ADMIN_EMAIL=...
ADMIN_PASSWORD=...

TOPUP_CARD_NUMBER=....
TOPUP_CARD_HOLDER=...
```

---

## Run It (Local)

```bash
# 1) PHP dependencies
composer install

# 2) Node dependencies + assets
npm install
npm run dev        # or: npm run build

# 3) App key, database, cache/queue
php artisan key:generate
php artisan migrate
php artisan horizon   # Redis recommended for cache + queue

# 4) Start Octane (RoadRunner)
php artisan octane:start --server=roadrunner
```

Set your Telegram webhook to:

```
POST https://your-domain.tld/api/v1/telegram/webhook/{TELEGRAM_BOT_SECRET}
```

---

## Run It (Docker)

```bash
docker build -t dastyar .
docker run --env-file .env -p 8000:8000 dastyar
# Exposes 8000; Supervisor runs RoadRunner + Horizon inside
```

---

## Scripts
- **JS/CSS:** `npm run dev` / `npm run build`
- **Tests:** `composer test` (Pest)
- **Horizon:** `php artisan horizon`
- **Octane (RR):** `php artisan octane:start --server=roadrunner`

---

## Notes
- Default locale is `fa` (Persian)
- Uses Predis client for Redis
- License: MIT (see `composer.json`)

---
