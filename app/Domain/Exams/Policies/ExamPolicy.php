<?php

namespace App\Domain\Exams\Policies;

use App\Domain\Academic\Models\Classroom;
use App\Domain\Academic\Models\Subject;
use App\Domain\Exams\Models\Exam;
use App\Domain\School\Enums\Permission;
use App\Domain\Teaching\Enums\ApproverType;
use App\Domain\Teaching\Models\ApprovalAssignment;
use App\Domain\Teaching\Models\TeachingAssignment;
use App\Models\User;

class ExamPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(Permission::ManageExams->value) || $user->isTeacher();
    }

    public function view(User $user, Exam $exam): bool
    {
        if ($user->can(Permission::ManageExams->value)) {
            return true;
        }

        if ($user->isTeacher()) {
            $teacher = $user->teacherProfile();
            if (! $teacher) {
                return false;
            }

            $examClassroomIds = $exam->classrooms()->pluck('classrooms.id');

            $teachesClassroom = TeachingAssignment::whereIn('classroom_id', $examClassroomIds)
                ->where('teacher_id', $teacher->id)
                ->exists();

            $isHomeroom = Classroom::whereIn('id', $examClassroomIds)
                ->where('homeroom_teacher_id', $teacher->id)
                ->exists();

            return $teachesClassroom || $isHomeroom;
        }

        return false;
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

        $teacher = $user->teacherProfile();
        if (! $teacher) {
            return false;
        }

        $approval = ApprovalAssignment::where('academic_year_id', $classroom->academic_year_id)
            ->where('grade_level_id', $classroom->grade_level_id)
            ->first();

        if ($approval) {
            if ($approval->approver_type === ApproverType::User) {
                return $approval->user_id === $user->id;
            }

            if ($approval->approver_type === ApproverType::Role && $approval->role) {
                return $user->hasRole($approval->role);
            }
        }

        return (int) $classroom->homeroom_teacher_id === (int) $teacher->id;
    }
}
