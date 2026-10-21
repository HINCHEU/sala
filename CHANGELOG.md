# Changelog

## Unreleased

- M2: academic years with semesters (one current per school), grade levels, tracks, subjects and rooms, a one-click MoEYS template, the per-year curriculum grid with divisor and copy-from-year, mention bands per stage, and classes per year. Deletes of anything in use are refused. Route model binding now runs after the school is resolved, so another school's records 404.
- M1: schools and memberships with per-school roles (spatie teams), tenant scoping (`BelongsToSchool`, `CurrentSchool`, `ResolveSchool`), login by email or +855 phone, password reset by email or by the director, enforced 2FA for director / academic office / accountant, platform school creation with director invitation links, school settings (logo, brand colour, report header, `SchoolSettings`), school and language switchers, admin and teacher layouts.
- Phase 0: Laravel 12 + Inertia 2 + Vue 3 + Tailwind 4 scaffold, Docker Compose stack, CI, Khmer fonts and localisation, demo login.
