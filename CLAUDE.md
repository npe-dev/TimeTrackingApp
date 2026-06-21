# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Personal time tracking application with Kanban-style task management, time entries, reports, and automations. Single-user focused.

## Tech Stack

- **Backend**: Laravel 13 (PHP 8.3+), SQLite database, Sanctum **SPA cookie/session auth**
- **Frontend**: Vue 3 SPA with Vue Router, Axios, Tailwind CSS 4
- **Build**: Vite 8 with `@vitejs/plugin-vue`
- **Docker**: PHP-FPM + Nginx + Node (build watcher), accessible at `localhost:8077`

## Commands

```bash
# Full dev environment (server + queue + logs + vite HMR)
composer dev

# First-time setup
composer setup

# Run tests (uses in-memory SQLite)
composer test

# Run a single test file
php artisan test --filter=ExampleTest

# Lint/format PHP
./vendor/bin/pint

# Build frontend assets
npm run build

# Vite dev server only
npm run dev
```

## Architecture

### Backend

- **SPA catch-all**: `routes/web.php` serves the Vue app for all non-API routes
- **API routes**: `routes/api.php` — all under `/api`, Sanctum-protected except login/register and background status
- **Domain models**: Project → Board → Column → Task (with TimeEntry, ChecklistItem, TaskLabel, TaskLink, GlobalLabel)
- **AutomationService** (`app/Services/AutomationService.php`): handles automation rule execution
- **Tests**: PHPUnit, configured in `phpunit.xml` with SQLite `:memory:` for testing

### Frontend

- **Entry**: `resources/js/app.js` — configures axios (`baseURL: /api`), defines routes, auth navigation guard
- **Pages**: `resources/js/pages/` — Timer (home), Tasks (Kanban board), Reports, Settings, Automations, Login, Register
- **Composables**: `resources/js/composables/` — `useApi`, `useAuth`, `useProjects`, `useTimer`, `useBackground`
- **Layout**: Single `AppLayout.vue` wrapping authenticated pages
- **Path alias**: `@` maps to `resources/js/`

### Docker

Three services: `timetracking_app` (PHP-FPM), `timetracking_web` (Nginx on port 8077), `timetracking_node` (builds + watches frontend). Entrypoint auto-installs composer deps, creates SQLite DB, and runs migrations when `AUTO_MIGRATE=true`.
