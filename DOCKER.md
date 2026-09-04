# Docker Setup

The repository ships with a complete Docker stack: Laravel 12 API (`backend/`), Vue 3 SPA (`frontend/`) and MySQL with persistent volumes.

## Quick start

```bash
# 1. (optional) create a config override file from the example
copy .env.example .env

# 2. build the images and start everything
docker compose up --build
```

| Service    | URL                          | Notes                                        |
| ---------- | ---------------------------- | -------------------------------------------- |
| Frontend   | http://localhost:5173        | Vue SPA served by nginx                      |
| Backend    | http://localhost:8000/api/…  | Laravel API (`php artisan serve`)            |
| phpMyAdmin | http://localhost:8080        | login with `laravel` / `secret` (or root)    |
| MySQL      | `localhost:3306`             | db `ecommerce`, user `laravel`, pass `secret`|

On first start the backend container waits for MySQL, generates an `APP_KEY`, runs migrations and then serves the API. A queue worker container processes the `database` queue.

## Data persistence (volumes)

| Volume            | Mounted at                    | What it stores                                      |
| ----------------- | ----------------------------- | --------------------------------------------------- |
| `db_data`         | `/var/lib/mysql` (mysql)      | **All MySQL data** – products, orders, users, carts |
| `backend_storage` | `/var/www/html/storage`       | Laravel logs, cache and uploaded files              |

Anything you change from the frontend (add a product, place an order, edit a profile…) is written through the API into MySQL, whose files live in the `db_data` volume. That means:

- `docker compose stop` / `start` / `down` / `up` → **data is kept**
- You can verify it in phpMyAdmin (http://localhost:8080) at any time
- `docker compose down -v` is the only command that **deletes** the data (removes the volumes)

## Common commands

```bash
docker compose up --build          # rebuild images and start the stack
docker compose up -d               # start in the background
docker compose ps                  # show container status / health
docker compose logs -f backend     # tail backend logs
docker compose exec backend php artisan tinker
docker compose exec backend php artisan db:seed   # seed the database
docker compose down                # stop and remove containers (keeps volumes)
docker compose down -v             # stop and DELETE volumes (fresh database)
docker compose build               # rebuild images only
```

## Configuration

All defaults live in `docker-compose.yml` and can be overridden in a root `.env` file (see `.env.example`): database credentials, ports (`MYSQL_PORT`, `BACKEND_PORT`, `FRONTEND_PORT`, `PHPMYADMIN_PORT`) and the frontend build-time `VITE_*` variables.

> The SPA is a static bundle – `VITE_*` variables are inlined at **image build time**. If you change one, rebuild with `docker compose build frontend && docker compose up -d frontend`.

If port 3306 is already used on your machine (XAMPP/Laragon MySQL), set `MYSQL_PORT=33061` in the root `.env`.

## CI/CD (GitHub Actions)

`.github/workflows/ci-cd.yml` runs on every push/PR to `main`:

1. **backend-tests** – PHP 8.3, `composer install`, Pest/PHPUnit tests
2. **frontend-build** – Node 22, `npm ci`, type-check + Vite build
3. **docker** – validates `docker-compose.yml`, builds both images, boots the full stack and smoke-tests `mysql → backend → frontend`, then (on pushes to `main`) publishes `backend` and `frontend` images to GitHub Container Registry (`ghcr.io/<owner>/<repo>/{backend,frontend}:latest` + `:sha`)

No repository secrets are required – publishing uses the built-in `GITHUB_TOKEN` with the `packages: write` permission.
