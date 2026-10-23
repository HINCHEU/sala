<?php

namespace App\Domain\Teaching\Models;

use App\Domain\Academic\Models\AcademicYear;
use App\Domain\Academic\Models\Classroom;
use App\Domain\Academic\Models\Subject;
use App\Domain\People\Models\Teacher;
use App\Support\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $school_id
 * @property int $academic_year_id
 * @property int $classroom_id
 * @property int $subject_id
 * @property int $teacher_id
 * @property ?int $hours_per_week
 * @property-read ?AcademicYear $academicYear
 * @property-read ?Classroom $classroom
 * @property-read ?Subject $subject
 * @property-read ?Teacher $teacher
 */
class TeachingAssignment extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'academic_year_id',
        'classroom_id',
        'subject_id',
        'teacher_id',
        'hours_per_week',
    ];

    protected function casts(): array
    {
        return [
            'hours_per_week' => 'integer',
        ];
    }

    /** @return BelongsTo<AcademicYear, $this> */
    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    /** @return BelongsTo<Classroom, $this> */
    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class);
    }

    /** @return BelongsTo<Subject, $this> */
    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    /** @return BelongsTo<Teacher, $this> */
    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }
}
