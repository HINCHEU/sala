# 0003 — M2 academic structure choices

Date: 2026-10-22 · Status: accepted

## Decisions

1. **The school is resolved before route model binding.** `ResolveSchool` is prepended ahead of `SubstituteBindings` in the middleware priority list. Without this, `{subject}` in a URL resolved before the tenant scope was on, and one school's academic office could edit or delete another school's record. A test in `IsolationTest` fails if the order is lost.
2. **Every school-owned form validates with school-scoped rules.** `SchoolRequest::existsInSchool()` and `uniqueInSchool()` replace plain `exists` / `unique`, which see every school's rows.
3. **Foreign keys refuse deletes; the app turns that into a message.** `Controller::destroyUnlessUsed()` catches the integrity error and flashes `academic.in_use`. Scores (M5) get the same `restrictOnDelete` keys, which is how "a subject used in scores cannot be deleted" holds.
4. **A grade's curriculum is saved as a whole.** The grid sends every taught subject; the server replaces that year + grade + track in one transaction. Copying from another year replaces the target year's curriculum and divisors. Rows belong to a year, so editing next year never changes last year.
5. **The MoEYS template only fills gaps.** It adds grades 1–12, the Grade 11–12 Science / Social tracks, 18 common subjects and default mention bands, using `firstOrCreate`, so re-running it never overwrites a school's edits. Curriculum max scores are not templated: each school enters its own.
6. **Mention bands are per stage, best first.** Default bands are out of 10 for primary and out of 50 for secondary (the usual divisor average). Overlapping bands are refused. Bands store the mention key plus Khmer and English labels so old report cards keep their wording.

## Not yet

- `academic_years.status` (open / closed) arrives with the year rollover (M12).
- `classrooms.homeroom_teacher_id` arrives with the teachers table (M3).
