<?php

namespace App\Domain\Exams\Models;

use App\Domain\Academic\Models\AcademicYear;
use App\Domain\Academic\Models\Classroom;
use App\Domain\Academic\Models\Term;
use App\Domain\Exams\Enums\ExamStatus;
use App\Domain\Exams\Enums\ExamType;
use App\Support\Tenancy\BelongsToSchool;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $school_id
 * @property int $academic_year_id
 * @property ?int $term_id
 * @property string $name
 * @property ExamType $type
 * @property ?int $month
 * @property ?Carbon $start_date
 * @property ?Carbon $end_date
 * @property ExamStatus $status
 * @property ?string $description
 * @property-read ?AcademicYear $academicYear
 * @property-read ?Term $term
 * @property-read Collection<int, Classroom> $classrooms
 * @property-read Collection<int, ExamClassroom> $examClassrooms
 * @property-read Collection<int, ExamSubjectStatus> $subjectStatuses
 */
class Exam extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'academic_year_id',
        'term_id',
        'name',
        'type',
        'month',
        'start_date',
        'end_date',
        'status',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'type' => ExamType::class,
            'status' => ExamStatus::class,
            'month' => 'integer',
            'start_date' => 'date:Y-m-d',
            'end_date' => 'date:Y-m-d',
        ];
    }

    /** @return BelongsTo<AcademicYear, $this> */
    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    /** @return BelongsTo<Term, $this> */
    public function term(): BelongsTo
    {
        return $this->belongsTo(Term::class);
    }

    /** @return BelongsToMany<Classroom, $this> */
    public function classrooms(): BelongsToMany
    {
        return $this->belongsToMany(Classroom::class, 'exam_classrooms')
            ->withPivot('school_id')
            ->withTimestamps();
    }

    /**
     * @param  list<int>  $classroomIds
     */
    public function syncClassrooms(array $classroomIds): void
    {
        $this->classrooms()->sync(
            array_fill_keys($classroomIds, ['school_id' => $this->school_id])
        );
    }

    /** @return HasMany<ExamClassroom, $this> */
    public function examClassrooms(): HasMany
    {
        return $this->hasMany(ExamClassroom::class);
    }

    /** @return HasMany<ExamSubjectStatus, $this> */
    public function subjectStatuses(): HasMany
    {
        return $this->hasMany(ExamSubjectStatus::class);
    }

    public function isOpen(): bool
    {
        return $this->status === ExamStatus::Open;
    }

    public function isLocked(): bool
    {
        return in_array($this->status, [ExamStatus::Locked, ExamStatus::Published], true);
    }
}
