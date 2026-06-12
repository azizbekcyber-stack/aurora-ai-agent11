# Aurora AI Social Media Agent

Aurora is a chat-first AI social media workspace prototype for creating, previewing, approving, and scheduling platform-ready content.

## Tech Stack

- Frontend: Nuxt 3
- Language: TypeScript
- Rendering: SSR enabled
- Styling: plain CSS
- Backend: Laravel REST API
- Database: PostgreSQL

## Current Status

This repository contains a Nuxt 3 prototype in the `frontend` folder and an initial Laravel API skeleton in the `backend` folder. The UI includes the Aurora shell with a left sidebar, main workspace, persistent right preview panel, and mock pages for AI Agent, Tasks, Calendar, Media, Brand Kit, Social Accounts, Analytics, and Settings.

AI generation, media scoring, analytics, publishing, scheduling, authentication, and social account integrations are mocked or not implemented. No real AI API calls or social platform API calls are implemented.

## Frontend Local Setup

```powershell
cd frontend
npm.cmd install
npm.cmd run dev -- --host 127.0.0.1 --port 3000
```

Open:

```text
http://127.0.0.1:3000/
```

## Build

```powershell
cd frontend
npm.cmd run build
```

## Backend

The backend is an initial Laravel REST API skeleton configured for PostgreSQL. It currently only exposes a health route; authentication, product models, real AI integration, and social publishing integrations are not implemented yet.

## Backend Local Setup

```powershell
cd backend
composer install
copy .env.example .env
php artisan key:generate
```

Update `.env` with your local PostgreSQL credentials, then run the server:

```powershell
php artisan serve --host=127.0.0.1 --port=8000
```

Health endpoint:

```text
GET http://127.0.0.1:8000/api/v1/health
```
