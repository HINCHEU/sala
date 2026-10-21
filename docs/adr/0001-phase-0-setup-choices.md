# 0001 — Phase 0 setup choices

Date: 2026-10-21 · Status: accepted

The architecture plan's Decisions table already records the product decisions. This ADR records the small technical choices made while setting up the repository, so later work does not undo them by accident.

## Decisions

1. **Inertia 2, pinned.** `inertiajs/inertia-laravel:^2` and `@inertiajs/vue3:^2`, as the plans specify. Moving to a newer major is its own ADR.
2. **No starter kit.** Laravel 12's Vue kit is TypeScript-only; Inertia, Vue and Tailwind were added by hand in plain JavaScript.
3. **One Compose file for production, one override for development.** `compose.yaml` is what staging and production run; `compose.override.yaml` adds the live source mount, Vite, MinIO and Mailpit and is skipped by `docker compose -f compose.yaml`.
4. **Fonts are self-hosted through npm (`@fontsource/*`).** Vite bundles Kantumruy Pro (interface), Noto Sans Khmer (fallback), Battambang, Siemreap and Moul (documents). Browsers only download the faces a page uses. Gotenberg gets the same files in M7.
5. **Telescope is local-only.** It is a dev dependency, excluded from package discovery and registered in `AppServiceProvider` only when installed and `APP_ENV=local`.
6. **Permission teams are schools.** `spatie/laravel-permission` runs with `teams = true` and `team_foreign_key = school_id`.
7. **Two media disks, never a provider name in code.** `media_private` and `media_public` switch between local disk and any S3-compatible store with `MEDIA_DRIVER`; a separate `backups` disk targets the off-server bucket.
8. **Seed data follows the tables.** Phase 0 seeds a single demo login. Demo schools, the MoEYS subject template, users per role and the 200 Khmer-named students are seeded in the milestone that creates their tables (M1–M3).

## Consequences

- Local PHP on Windows lacks `pcntl`/`posix`, which Horizon needs; run Composer there with `--ignore-platform-req=ext-pcntl --ignore-platform-req=ext-posix`, or use the Docker container.
- Staging deploy is wired but inert until the droplet exists and `STAGING_HOST`, `STAGING_USER` and `STAGING_SSH_KEY` are set in the repository's `staging` environment.
