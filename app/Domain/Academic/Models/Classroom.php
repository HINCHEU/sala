<?php

namespace App\Domain\Academic\Models;

use App\Domain\Academic\Enums\Shift;
use App\Support\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** "7A" is a new row every year, with its own students and homeroom teacher. */
class Classroom extends Model
{
    use BelongsToSchool;

    protected $fillable = ['academic_year_id', 'grade_level_id', 'track_id', 'room_id', 'name', 'shift', 'capacity'];

    protected function casts(): array
    {
        return ['shift' => Shift::class];
    }

    /** @return BelongsTo<GradeLevel, $this> */
    public function gradeLevel(): BelongsTo
    {
        return $this->belongsTo(GradeLevel::class);
    }
}
