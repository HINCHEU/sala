<?php

namespace App\Domain\Exams\Models;

use App\Domain\Exams\Enums\ScoreMark;
use App\Support\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

/**
 * One filled cell of a score sheet: a score, or an absent / exempt mark (then score is null).
 *
 * @property int $exam_id
 * @property int $enrollment_id
 * @property int $subject_id
 * @property ?string $score decimal as a string, e.g. "12.50"
 * @property ScoreMark $mark
 */
class Score extends Model
{
    use BelongsToSchool;

    protected $fillable = ['exam_id', 'enrollment_id', 'subject_id', 'score', 'mark', 'entered_by', 'updated_by'];

    protected function casts(): array
    {
        return ['score' => 'decimal:2', 'mark' => ScoreMark::class];
    }

    /** As the grid shows it: "12.5", "20", "A" or "E". */
    public function display(): string
    {
        return $this->mark->letter() ?? rtrim(rtrim((string) $this->score, '0'), '.');
    }
}
