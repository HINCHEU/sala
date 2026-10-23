<?php

namespace App\Domain\Exams\Models;

use App\Domain\Academic\Models\Classroom;
use App\Support\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExamClassroom extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'exam_id',
        'classroom_id',
    ];

    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }

    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class);
    }
}
