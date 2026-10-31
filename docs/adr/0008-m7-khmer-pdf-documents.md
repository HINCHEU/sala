# 0008 — M7 Khmer PDF document choices

Date: 2026-10-27 · Status: accepted

## Decisions

1. **The fonts live in the repo.** `resources/fonts/pdf` holds the Battambang and Moul files from `@fontsource`, because `node_modules` never reaches the app image. `PdfRenderer` sends them with each request, and the logo goes as a data URI, so Gotenberg needs no access to the app or the file store.
2. **Rendering waits for the fonts.** The layout sets `window.fontsReady` after `document.fonts.ready` and Gotenberg waits for it; otherwise a fast render can print fallback glyphs.
3. **Requests go through Laravel's HTTP client.** `gotenberg-php` builds the request and `Http` sends it, so tests can use `Http::fake` as the plan asks.
4. **One document row per class, kind and results version.** `exam_documents` holds the status; the file is a `StoredFile` it owns. Asking again regenerates the file (a new logo, say) and replaces the old one. A correction makes a new version, so earlier documents stay on record and the page offers only the current version's.
5. **Whole-class report cards are one HTML document.** Each card starts a new page, so one Chromium render gives the merged PDF. Rendering cards separately and merging them would add a second engine step and gain nothing.
6. **One student's card renders during the request and is not stored.** It takes seconds, and nothing needs to keep it until parents get cards in M11.
7. **Printing needs final results.** `Exam::hasFinalResults()` requires a lock, a calculated version, and no approved correction newer than the last calculation: at that moment the score has changed but the stored results have not. The job also drops a request whose version has been replaced.
8. **Printed numbers are the results page's numbers.** Both use `ClassResults::sheet()`. Documents leave out students enrolled after the lock, who have no stored result.
9. **Whoever may see a class's score sheet may print it** (`ExamPolicy::viewScores`).
10. **The job timeout (80 s) stays below the queue's `retry_after` (90 s)**, so a slow render is not started twice.

## Assumptions to confirm with the pilot school

- The honor roll lists class ranks 1 to 5, ties included (`ExamDocuments::HONOR_ROLL_RANKS`).
- The date is printed as a Gregorian date in Khmer. The lunar date line is left blank to fill in by hand: a wrong lunar date on an official paper is worse than a blank one.
- The Khmer labels and the signature roles (homeroom teacher, academic office, director, guardian) need staff approval against the paper originals.

## Not yet

- Documents for semester and year results; this milestone covers single exams.
- Keeping student cards for parents (M11) and QR verification (M15).
