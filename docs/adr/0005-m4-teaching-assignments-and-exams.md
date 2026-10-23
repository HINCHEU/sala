# 0005 — M4 teaching assignments and exam setup choices

Date: 2026-10-24 · Status: accepted

## Decisions

1. **Teaching assignment is the security boundary for score entry.** `teaching_assignments` maps `(school_id, academic_year_id, classroom_id, subject_id)` to a `teacher_id` with `hours_per_week`. Each classroom's valid subjects are defined by its curriculum (grade level and track). A unique `(classroom_id, subject_id)` constraint ensures exactly one assigned teacher per subject at any given time.
2. **Copying assignments from prior academic years.** Academic offices can duplicate teaching assignments from the prior academic year into the current year with one click. Matching is performed by classroom grade and name, retaining assignments only for teachers and subjects that are still active in the school.
3. **Approver configuration per grade level.** By default, scores are approved by each classroom's homeroom teacher (`scoreApproverRole = 'homeroom'`). `approval_assignments` allows grade-level overrides: assigning approval rights to a designated role (`Role::AcademicOffice`, `Role::Director`) or a specific designated user (e.g. department head).
4. **Homeroom teacher role synchronization.** When a classroom assigns, updates, or clears its `homeroom_teacher_id`, the system synchronizes Spatie roles: if the teacher has an active user account in the school, `Role::HomeroomTeacher` is granted. If the teacher no longer serves as homeroom teacher for any classroom in that school, the role is revoked.
5. **Exam lifecycle state machine.** Exam progression strictly follows:
   `draft → open → approved → locked → published`.
   - `draft`: Created by Academic Office/Director; target classrooms/grades and schedule set.
   - `open`: Score entry unlocked for assigned teachers; initializes tracking records (`exam_subject_status`) across participating classrooms and curriculum subjects.
   - `approved`: All required subject scores have been submitted and approved.
   - `locked`: Academic Office finalizes the exam. Score editing is sealed; triggers batch result computation and rank generation (M6).
   - `published`: Results and student report cards become visible to parents and students.
   Transitions outside the allowed state machine order are rejected.
6. **Subject-level exam workflow.** Within an open exam, each `(classroom_id, subject_id)` pair is independently tracked via `exam_subject_status` (`draft → submitted → approved`, or `submitted → returned` with a mandatory reason note). A returned subject re-opens for correction by the assigned teacher before re-submission.
7. **Teacher dashboard "My Classes".** Teachers (subject and homeroom) are presented with their personalized teaching schedule for the active academic year: assigned classes, subjects, weekly hours, student tallies, and currently active exams requiring score submission.

## Not yet

- Excel score import/export and spreadsheet keyboard navigation grid: M5 score entry.
- Automatic grade-wide ranking, mention bands, and report card compilation: M6 results engine.
- PDF generation and Khmer subscript printing: M7.
