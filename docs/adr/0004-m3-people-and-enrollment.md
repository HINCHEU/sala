# 0004 — M3 people and enrollment choices

Date: 2026-10-23 · Status: accepted

## Decisions

1. **One guardian per phone number per school.** `guardians` has a unique `(school_id, phone)` key, and adding a guardian whose phone is already known links that guardian instead of making a copy. This is how siblings share their parents; the form looks the phone up first and says so. The key covers soft-deleted rows too, so a removed guardian is restored rather than duplicated.
2. **Roll numbers follow Khmer alphabetical order and are recomputed on every change.** Adding, moving or removing a student renumbers each class it touched (`RollNumbers`). The database sorts the Khmer names (MySQL `utf8mb4_unicode_ci`; SQLite in tests compares code points, which for Khmer is the same consonant order).
3. **Moving a student keeps their enrollment row.** Only `classroom_id` changes, so anything later recorded on the enrollment (scores, attendance) moves with them. Removing deletes the row, which those tables' foreign keys will refuse once they exist; leaving mid-year is a transfer (M12).
4. **Sensitive fields are encrypted, not just hidden.** `birth_cert_no`, `id_poor_level` and `disability` use Laravel's `encrypted` cast, so they cannot be filtered in SQL. School-sized lists are filtered in PHP when fee waivers (M14) need ID Poor.
5. **The Excel import checks first and imports later.** Upload → every row is checked against the school's data and the rows above it (codes, duplicate name + date of birth, class names in the current year, phones, provinces) → the registrar sees each row's errors → a queued job imports only the good rows. Each row is its own transaction; a row that clashes with data added since the check is skipped with a reason. The job has one try, because a retry would import the same rows twice, and confirm claims the import atomically so a double click cannot queue it twice.
6. **Columns are read by position.** Schools may translate or reword the template's headings; the column order is the contract (`StudentSheet::COLUMNS`). Khmer digits are read as numbers, and dates may be Excel date cells or day/month/year text.
7. **A teacher's login is an invitation.** The teacher record exists without a user; "create sign-in link" makes an ordinary invitation (role subject teacher) carrying `teacher_id`, and accepting it links the new user to the record.

## Not yet

- Province → district → commune → village selects: district, commune and village are free text until the NCDD gazetteer is imported as a table (`Provinces` notes the shortcut).
- Granting the homeroom teacher role when a teacher is set as a class's homeroom teacher: M4's teaching assignments.
- Guardian logins: the parent portal (M11).
