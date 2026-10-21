# 0002 — M1 tenancy and sign-in choices

Date: 2026-10-21 · Status: accepted

## Decisions

1. **Fortify for sign-in, password reset and 2FA.** It is first-party and headless, so the Inertia pages stay ours. Self-registration, profile updates and passkeys are off: accounts come only from invitations.
2. **One login field.** `login` takes an email or a Cambodian phone in any common form; `App\Support\PhoneNumber` normalises to `+855…`. Users without email reset passwords through the director ("reset by admin"), who gets a one-time temporary password.
3. **Platform super admin is a flag, not a role.** `users.is_platform_admin` plus `Gate::before`. Spatie roles are team-scoped by `school_id`, and the super admin belongs to no school.
4. **Foreign schools are 404, never 403.** `ResolveSchool` answers 404 for a subdomain or switch target the user does not belong to, so school codes cannot be probed. A stale session school silently falls back to one the user still belongs to.
5. **Invitations store only a token hash.** The plain link is shown once to the inviter. Links expire in 7 days and work once. Only the account the invitation names (email or phone) can accept it.
6. **Brand colour must pass 4.5:1 with white.** The settings page refuses lighter colours and suggests a darker shade (`BrandColor::suggestDarker`). `primary-soft` is the colour mixed with 88% white.
7. **Logos are raster only, re-encoded to PNG ≤ 512 px.** No SVG (can carry scripts); re-encoding strips EXIF.
8. **Layouts follow roles.** Staff (director, academic office, registrar, accountant) and platform admins get the sidebar layout; teachers, guardians and students the phone-first layout until the parent portal (M11) has its own.

## Not yet

- Promotion rules and the finance module flag are kept in `SchoolSettings` but not editable until M12 / M14.
- Queued jobs restoring `CurrentSchool` from `school_id` arrive with the first job (M6 results, M7 PDFs).
- `FileStore::migrate()` / `storage:migrate` arrive when a school first moves storage provider.
