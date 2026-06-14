# Aurora Deployment Runbook

This project has two deployable apps:

- `aurora-backend`: Laravel API, Telegram webhook, queue jobs, Gemini integration.
- `aurora-frontend`: Nuxt dashboard and public landing page.

## Recommended Trial Stack

- Frontend: Vercel Hobby.
- Backend API: Railway or Render using `aurora-backend/Dockerfile`.
- Database: managed PostgreSQL from the same platform or Supabase.
- Storage: start with a platform persistent volume for `storage/app`; move to S3/R2 before serious production use.

## Required Backend Environment

Set these on the backend service:

```env
APP_NAME=Aurora
APP_ENV=production
APP_KEY=base64:...
APP_DEBUG=false
APP_URL=https://YOUR-BACKEND-DOMAIN
FRONTEND_URL=https://YOUR-FRONTEND-DOMAIN

AURORA_DASHBOARD_TOKEN=generate-a-long-random-value

DB_CONNECTION=pgsql
DB_URL=postgresql://...

QUEUE_CONNECTION=database
CACHE_STORE=database
SESSION_DRIVER=database
FILESYSTEM_DISK=local

TELEGRAM_BOT_TOKEN=...
TELEGRAM_WEBHOOK_SECRET=generate-a-long-random-value

GEMINI_API_KEY=...
GEMINI_MODEL_TEXT=gemini-2.5-flash
GEMINI_MODEL_VISION=gemini-2.5-flash
```

Generate `APP_KEY` locally or on the platform:

```bash
php artisan key:generate --show
```

## Backend Services

Create one web service from `aurora-backend`. The start script runs both the Laravel HTTP server and a lightweight queue worker in the same container. This keeps uploaded images available to queued AI/publish jobs on trial hosting.

Build:

```bash
docker build .
```

Start command:

```bash
./deploy/start.sh
```

Do not create a separate worker service for the trial deploy unless you also move image storage to S3/R2 or another shared storage service.

## Frontend Environment

Set these on Vercel:

```env
NUXT_PUBLIC_API_BASE=https://YOUR-BACKEND-DOMAIN/api/v1
NUXT_PUBLIC_SITE_URL=https://YOUR-FRONTEND-DOMAIN
```

Build command:

```bash
npm run build
```

Root directory:

```text
aurora-frontend
```

## Telegram Webhook

After backend deploy, set the webhook:

```powershell
Invoke-RestMethod -Method Post -Uri "https://api.telegram.org/botBOT_TOKEN/setWebhook" -Body @{
  url = "https://YOUR-BACKEND-DOMAIN/webhook/telegram"
  secret_token = "TELEGRAM_WEBHOOK_SECRET"
}
```

Check it:

```powershell
Invoke-RestMethod -Uri "https://api.telegram.org/botBOT_TOKEN/getWebhookInfo"
```

Do not commit or paste real tokens into chat.

## Dashboard Login After Deploy

Open:

```text
https://YOUR-FRONTEND-DOMAIN/login
```

Enter:

- `User ID`: your Telegram-connected user id, locally this has usually been `2`.
- `Dashboard access token`: the same value as `AURORA_DASHBOARD_TOKEN`.

## Google Search

Deploying does not guarantee Google indexing. After the frontend has a real domain:

1. Add the domain to Google Search Console.
2. Verify domain ownership.
3. Submit:

```text
https://YOUR-FRONTEND-DOMAIN/sitemap.xml
```

Dashboard routes are excluded in `robots.txt`; the public `/` landing page is indexable.

## Production Checklist

- Backend URL opens `/api/v1/health`.
- Frontend URL opens `/`.
- `/login` accepts dashboard token.
- Queue worker is running inside the backend service.
- PostgreSQL migrations completed.
- Telegram webhook points to production backend.
- Bot can create drafts.
- Web dashboard can create, approve, and publish drafts.
- Connected channel is visible as publish destination.
