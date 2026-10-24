<?php

namespace App\Domain\Results\Models;

use App\Domain\Exams\Models\Exam;
use App\Domain\People\Models\Enrollment;
use App\Support\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One student's result in one exam, for one calculation version. Read the exam's current version
 * (exams.results_version); older versions stay as the record of what was published before a correction.
 *
 * @property int $exam_id
 * @property int $enrollment_id
 * @property int $classroom_id
 * @property int $version
 * @property string $total
 * @property string $average
 * @property ?int $rank_class
 * @property ?int $rank_grade
 * @property ?string $mention
 * @property bool $passed
 */
class ExamResult extends Model
{
    use BelongsToSchool;

    public $timestamps = false;

    protected $fillable = [
        'exam_id', 'enrollment_id', 'classroom_id', 'version', 'total', 'average', 'rank_class', 'rank_grade',
        'mention', 'passed', 'absent_count', 'exempt_count', 'computed_at',
    ];

    protected function casts(): array
    {
        return ['total' => 'decimal:2', 'average' => 'decimal:2', 'passed' => 'boolean', 'computed_at' => 'datetime'];
    }

    /** @return BelongsTo<Exam, $this> */
    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }

    /** @return BelongsTo<Enrollment, $this> */
    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(Enrollment::class);
    }
}
