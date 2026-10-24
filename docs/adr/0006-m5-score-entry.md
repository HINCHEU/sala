# 0006 — M5 score entry choices (and M4 close-out)

Date: 2026-10-25 · Status: accepted

## Decisions

1. **A blank cell has no row.** `scores` holds one row per filled cell: a score, or an absent / exempt mark with a null score. Clearing a cell deletes its row, so "not entered" is never stored as 0, and "every student has a row" is exactly "the sheet is complete".
2. **One endpoint saves cells, in batches.** `PUT /exams/{exam}/scores` takes a list of cells. The grid sends every edit made in the last 500 ms together; a paste from Excel is the same request with more cells; the Excel import calls the same action (`EnterScores`). Each cell is checked on its own and answered on its own, so one bad cell never blocks the rest.
3. **The rules live in `EnterScores`, not the browser.** 0 ≤ score ≤ the curriculum's max score, at most two decimals, Khmer digits and a decimal comma accepted, A / E (or អ / ល) for absent / exempt. The grid shows the server's answer (and its spelling: "12.50" → "12.5", "a" → "A").
4. **423 Locked when the exam is not open.** Draft, approved, locked and published exams refuse writes with HTTP 423 for the whole request. A submitted or approved subject refuses its cells (per cell); a returned one opens again.
5. **Last write wins, every write is logged.** No cell locking: two people typing the same cell is rare and the later value is kept. Each real change writes `score.entered` to the activity log with the user, the old value and the new one.
6. **Submitting a subject needs a full sheet.** `TransitionSubjectExamStatus` refuses `submitted` until every enrollment of the class has a row for that subject.
7. **Who sees which columns.** The assigned teacher sees and types in their subjects; the homeroom teacher or the grade's chosen approver sees every subject of the class read-only (to review before approving); managers see and type in everything.

## M4 close-out

- Exam list and exam pages now check `ExamPolicy` (`viewAny` / `view`). Teachers do not see draft exams; guardians and students get 403.
- Subject transitions check that the subject belongs to the exam in the URL (404 otherwise), that the exam is open, and who is acting: submit and take back need entry rights, approve and return need the approver.
- A grade's chosen approver (a person or a role) may approve even if they do not teach; before, the policy stopped at "has no teacher profile".
- An exam can only be approved when every subject is approved, and locked only after approval (open → locked removed).
- The exam page gets its per-subject permissions and progress (`can_enter`, `can_approve`, `filled` / `students`) from the server instead of re-deriving them in the browser.

## Not yet

- Change requests after a lock (M6), and the results engine that reads these scores.
- Offline entry: a lost connection marks cells as not saved; they are not queued for later.
