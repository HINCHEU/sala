<?php

namespace App\Domain\Exams\Policies;

use App\Domain\Academic\Models\Classroom;
use App\Domain\Academic\Models\Subject;
use App\Domain\Exams\Enums\ExamStatus;
use App\Domain\Exams\Models\Exam;
use App\Domain\School\Enums\Permission;
use App\Domain\Teaching\Enums\ApproverType;
use App\Domain\Teaching\Models\ApprovalAssignment;
use App\Domain\Teaching\Models\TeachingAssignment;
use App\Models\User;

class ExamPolicy
{
    /** Staff who manage exams, teach, or were chosen to approve a grade's scores. */
    public function viewAny(User $user): bool
    {
        return $user->can(Permission::ManageExams->value)
            || $user->isTeacher()
            || $user->teacherProfile() !== null
            || ApprovalAssignment::where('approver_type', ApproverType::User)->where('user_id', $user->id)->exists();
    }

    /** Managers see every exam; others see the ones past draft that touch a class they teach or approve. */
    public function view(User $user, Exam $exam): bool
    {
        if ($user->can(Permission::ManageExams->value)) {
            return true;
        }
        if ($exam->status === ExamStatus::Draft) {
            return false;
        }

        $classrooms = $exam->classrooms()->get();
        $teacher = $user->teacherProfile();

        if ($teacher && (
            TeachingAssignment::whereIn('classroom_id', $classrooms->modelKeys())->where('teacher_id', $teacher->id)->exists()
            || $classrooms->contains('homeroom_teacher_id', $teacher->id)
        )) {
            return true;
        }

        return $classrooms->contains(fn (Classroom $classroom) => $this->approveSubjectScores($user, $exam, $classroom));
    }

    public function manage(User $user): bool
    {
        return $user->can(Permission::ManageExams->value);
    }

    public function enterScores(User $user, Exam $exam, Classroom $classroom, Subject $subject): bool
    {
        if (! $exam->isOpen()) {
            return false;
        }

        if (! $exam->classrooms()->where('classrooms.id', $classroom->id)->exists()) {
            return false;
        }

        if ($user->can(Permission::ManageExams->value)) {
            return true;
        }

        $teacher = $user->teacherProfile();
        if (! $teacher) {
            return false;
        }

        return TeachingAssignment::where('classroom_id', $classroom->id)
            ->where('subject_id', $subject->id)
            ->where('teacher_id', $teacher->id)
            ->exists();
    }

    public function approveSubjectScores(User $user, Exam $exam, Classroom $classroom): bool
    {
        if ($user->can(Permission::ManageExams->value)) {
            return true;
        }

        $approval = ApprovalAssignment::where('academic_year_id', $classroom->academic_year_id)
            ->where('grade_level_id', $classroom->grade_level_id)
            ->first();

        // A chosen person or role approves whether or not they also teach.
        if ($approval?->approver_type === ApproverType::User) {
            return (int) $approval->user_id === (int) $user->id;
        }
        if ($approval?->approver_type === ApproverType::Role && $approval->role) {
            return $user->hasRole($approval->role);
        }

        $teacher = $user->teacherProfile();

        return $teacher !== null && (int) $classroom->homeroom_teacher_id === (int) $teacher->id;
    }

    /** Who may look at a class's score sheet: whoever enters, approves or manages it. */
    public function viewScores(User $user, Exam $exam, Classroom $classroom): bool
    {
        if (! $this->view($user, $exam) || ! $exam->classrooms()->where('classrooms.id', $classroom->id)->exists()) {
            return false;
        }
        if ($user->can(Permission::ManageExams->value) || $this->approveSubjectScores($user, $exam, $classroom)) {
            return true;
        }

        $teacher = $user->teacherProfile();

        return $teacher !== null && TeachingAssignment::where('classroom_id', $classroom->id)->where('teacher_id', $teacher->id)->exists();
    }
}
