# 0007 — M6 results engine choices

Date: 2026-10-26 · Status: accepted

## Decisions

1. **The engine is three pure classes.** `ResultCalculator` (totals, averages, pass), `Ranker` (competition ranking 1, 2, 2, 4) and `MentionResolver` (mention band) take arrays and return arrays, so every rule is unit-tested without a database (`tests/Unit/Results`). `ClassResults` feeds them from the database; the live preview and the lock job both use it, so what a teacher sees while the exam is open is exactly what locking writes.
2. **Rounded once, ranked as printed.** The average is rounded half up to two decimals at the end, and ranks and pass / fail compare the rounded value, so the printed sheet, the rank and the pass line always agree (99.99 ÷ 4 = 24.9975 prints 25.00 and passes a 25.00 line).
3. **Absent, exempt, missing.** Absent counts 0 (and, with `absentSubjectPolicy = exclude_from_rank`, the student keeps an average but gets no rank). Exempt subjects are left out of the total, and the divisor shrinks by the share of max score they remove. A cell not entered yet only exists while an exam is open: it counts 0 and is highlighted.
4. **The pass line is the same average made from the curriculum's pass scores.** No separate setting; it follows the school's curriculum and average method.
5. **Results are versioned.** Locking writes all rows of a new version in one transaction and then points `exams.results_version` at it. A correction writes the next version; earlier versions stay as the record of what was published. The plan suggested a batch of per-class jobs then a grade-ranking job; one job per exam is simpler, atomic, and fast enough at school size (a 1,800-student grade is a few thousand rows).
6. **Locking checks first.** Every class needs a curriculum, and a divisor when the school averages by divisor, before the exam can be locked — otherwise the job would fail after the scores were sealed. Publishing waits until a version exists.
7. **Corrections after a lock are requests.** The subject's teacher (or the office) asks with a reason and a value checked like a typed one; one pending request per cell. Only the director (`exams.approve-changes`) decides; approving applies the value, logs old and new, and recalculates.
8. **Semester and year results are computed, not stored.** Semester = mean of the term's monthly exam averages and the semester exam, weighted by `semesterMonthlyWeight` / `semesterExamWeight`; year = semester 1 and 2 weighted by `annualS1Weight` / `annualS2Weight`. A missing part hands its weight to the other. Working them out from the current exam versions each time means they can never lag behind a correction.

## Golden file

`tests/Feature/Results/GoldenSheetTest.php` runs a whole sheet through entry, lock and the job, and compares every total, average, rank, mention and pass with `tests/Fixtures/results/grade7-monthly.php`. That fixture is hand-worked in the MoEYS format until the pilot school provides a real, anonymised paper sheet; replacing the file is all it takes.

## Not yet

- Promotion decisions from the year result (M12) and PDFs of these sheets (M7).
- Notifying parents when results are published (M10, Telegram).
