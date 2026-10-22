# Sala — Cambodia School Management System

Laravel 12 · Inertia 2 + Vue 3 (plain JavaScript) · Tailwind 4 · MySQL 8.4 · Redis 7 · Horizon · Gotenberg.
Khmer first, English always.

## Setup in 5 commands

Needs Docker Desktop (or Docker Engine with Compose v2).

```bash
git clone https://github.com/HINCHEU/sala.git && cd sala
cp .env.example .env
docker compose up -d --build
docker compose exec app composer setup
open http://localhost:8080        # sign in as admin@sala.test / password (demo accounts below)
```

`composer setup` installs PHP packages, generates the app key, migrates, seeds and links storage.
The `vite` container serves front-end assets with hot reload.

| Service | URL |
| --- | --- |
| App | http://localhost:8080 |
| Mailpit (dev mail) | http://localhost:8025 |
| MinIO console (dev files) | http://localhost:9001 |
| Horizon (queues) | http://localhost:8080/horizon |
| Telescope (local only) | http://localhost:8080/telescope |
| MySQL from the host | `127.0.0.1:33060` |

## Demo accounts

Every seeded password is `password`.

| Account | Sign in with |
| --- | --- |
| Platform admin (all schools) | `admin@sala.test` |
| A role in a demo school | `{role}@{school}.sala.test`, e.g. `director@demo1.sala.test`, `subject_teacher@demo2.sala.test` |
| Guardian (phone login) | `012 000 101` (demo1) or `012 000 201` (demo2) |

Directors, academic office and accountants must turn on two-step verification at first sign-in (any TOTP app).

## Everyday commands

```bash
docker compose exec app php artisan test       # Pest
docker compose exec app composer lint          # Pint + Larastan level 6
docker compose exec vite npm run lint          # ESLint + eslint-plugin-vue
docker compose exec vite npm run format        # Prettier
```

With `make` (Linux, macOS, WSL): `make help` lists everything. `make check` runs lint and tests; on a server, `make install` once, then `make deploy` (backs up first) and `make doctor`.

## How the stack is laid out

- `compose.yaml` is the production/staging stack: `nginx`, `app` (php-fpm), `worker` (Horizon + scheduler under supervisord), `mysql`, `redis`, `gotenberg`.
- `compose.override.yaml` is merged automatically in development: source mounted live, plus `vite`, `minio` and `mailpit`. Deploys run `docker compose -f compose.yaml …` so it is skipped.
- `Dockerfile` is multi-stage: composer deps → node build → slim php-fpm runtime with opcache (`app` target) and an nginx image with the built assets (`web` target).
- CI (`.github/workflows/ci.yml`): Pint → Larastan → Pest on MySQL, ESLint → Prettier → `npm run build`, then on `main` build and push images to GHCR and deploy staging over SSH once the `STAGING_*` secrets exist.

## Conventions

- `main` is always deployable. Short-lived feature branches, squash merge, conventional commits (`feat:`, `fix:`).
- JavaScript only: `.js` files and `<script setup>`; no `lang="ts"`.
- All interface text in `lang/km/*.php` and `lang/en/*.php`, used in Vue with `$t('file.key')`.
- Styling uses the Sala tokens in `resources/css/tokens.css` (see `docs/3-Sala-Design-System.md`).

## Docs

- [Architecture plan](docs/1-Architecture-Plan.md)
- [Implementation plan](docs/2-Implementation-Plan.md)
- [Sala design system](docs/3-Sala-Design-System.md)
- [Architecture decision records](docs/adr/)
