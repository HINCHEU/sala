<?php

namespace App\Domain\People\Models;

use App\Domain\Academic\Models\AcademicYear;
use App\Domain\Academic\Models\Classroom;
use App\Support\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** A student in one class for one year (one per student per year). Scores and attendance hang on this. */
class Enrollment extends Model
{
    use BelongsToSchool;

    protected $fillable = ['academic_year_id', 'classroom_id', 'student_id', 'roll_no', 'status', 'joined_at', 'left_at'];

    protected function casts(): array
    {
        return ['joined_at' => 'date:Y-m-d', 'left_at' => 'date:Y-m-d'];
    }

    /** @return BelongsTo<Student, $this> */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /** @return BelongsTo<Classroom, $this> */
    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class);
    }

    /** @return BelongsTo<AcademicYear, $this> */
    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }
}
