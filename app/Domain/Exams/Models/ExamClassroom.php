<?php

namespace App\Domain\Exams\Models;

use App\Domain\Academic\Models\Classroom;
use App\Support\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $school_id
 * @property int $exam_id
 * @property int $classroom_id
 * @property-read ?Exam $exam
 * @property-read ?Classroom $classroom
 */
class ExamClassroom extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'exam_id',
        'classroom_id',
    ];

    /** @return BelongsTo<Exam, $this> */
    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }

    /** @return BelongsTo<Classroom, $this> */
    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class);
    }
}
