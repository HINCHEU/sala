<?php

namespace App\Domain\Academic\Models;

use App\Support\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** One subject in one grade (and track) for one year: its max score, coefficient and pass score. */
class CurriculumSubject extends Model
{
    use BelongsToSchool;

    protected $fillable = ['academic_year_id', 'grade_level_id', 'track_id', 'subject_id', 'max_score', 'coefficient', 'pass_score', 'sort'];

    protected function casts(): array
    {
        return ['max_score' => 'decimal:2', 'coefficient' => 'decimal:2', 'pass_score' => 'decimal:2'];
    }

    /** @return BelongsTo<Subject, $this> */
    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }
}
