<?php

namespace App\Domain\Academic\Models;

use App\Domain\Academic\Enums\Shift;
use App\Domain\Exams\Models\Exam;
use App\Domain\Exams\Models\ExamSubjectStatus;
use App\Domain\People\Models\Enrollment;
use App\Domain\People\Models\Teacher;
use App\Domain\Teaching\Models\TeachingAssignment;
use App\Support\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * "7A" is a new row every year, with its own students and homeroom teacher.
 *
 * @property int $id
 * @property int $school_id
 * @property int $academic_year_id
 * @property int $grade_level_id
 * @property ?int $track_id
 * @property ?int $room_id
 * @property ?int $homeroom_teacher_id
 * @property string $name
 * @property Shift $shift
 * @property ?int $capacity
 * @property-read ?GradeLevel $gradeLevel
 * @property-read ?Teacher $homeroomTeacher
 * @property-read ?Track $track
 * @property-read ?Room $room
 * @property-read Collection<int, Enrollment> $enrollments
 * @property-read Collection<int, TeachingAssignment> $teachingAssignments
 * @property-read Collection<int, Exam> $exams
 * @property-read Collection<int, ExamSubjectStatus> $examSubjectStatuses
 */
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

    /** @return HasMany<TeachingAssignment, $this> */
    public function teachingAssignments(): HasMany
    {
        return $this->hasMany(TeachingAssignment::class);
    }

    /** @return BelongsToMany<Exam, $this> */
    public function exams(): BelongsToMany
    {
        return $this->belongsToMany(Exam::class, 'exam_classrooms')->withTimestamps();
    }

    /** @return HasMany<ExamSubjectStatus, $this> */
    public function examSubjectStatuses(): HasMany
    {
        return $this->hasMany(ExamSubjectStatus::class);
    }
}
