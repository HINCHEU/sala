<?php

namespace App\Domain\Academic\Models;

use App\Domain\Academic\Enums\Shift;
use App\Domain\People\Models\Enrollment;
use App\Domain\People\Models\Teacher;
use App\Support\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** "7A" is a new row every year, with its own students and homeroom teacher. */
class Classroom extends Model
{
    use BelongsToSchool;

    protected $fillable = ['academic_year_id', 'grade_level_id', 'track_id', 'room_id', 'homeroom_teacher_id', 'name', 'shift', 'capacity'];

    protected function casts(): array
    {
        return ['shift' => Shift::class];
    }

    /** @return BelongsTo<GradeLevel, $this> */
    public function gradeLevel(): BelongsTo
    {
        return $this->belongsTo(GradeLevel::class);
    }

    /** @return BelongsTo<Teacher, $this> */
    public function homeroomTeacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }

    /** @return BelongsTo<Track, $this> */
    public function track(): BelongsTo
    {
        return $this->belongsTo(Track::class);
    }

    /** @return BelongsTo<Room, $this> */
    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    /** @return HasMany<Enrollment, $this> */
    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    /** @return HasMany<\App\Domain\Teaching\Models\TeachingAssignment, $this> */
    public function teachingAssignments(): HasMany
    {
        return $this->hasMany(\App\Domain\Teaching\Models\TeachingAssignment::class);
    }

    /** @return \Illuminate\Database\Eloquent\Relations\BelongsToMany<\App\Domain\Exams\Models\Exam, $this> */
    public function exams(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(\App\Domain\Exams\Models\Exam::class, 'exam_classrooms')->withTimestamps();
    }

    /** @return HasMany<\App\Domain\Exams\Models\ExamSubjectStatus, $this> */
    public function examSubjectStatuses(): HasMany
    {
        return $this->hasMany(\App\Domain\Exams\Models\ExamSubjectStatus::class);
    }
}
