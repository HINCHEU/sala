<?php

namespace App\Domain\Academic\Models;

use App\Support\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

/** The មេចែក: a grade's (and track's) subject total is divided by it to get the average. */
class GradeDivisor extends Model
{
    use BelongsToSchool;

    protected $fillable = ['academic_year_id', 'grade_level_id', 'track_id', 'divisor'];

    protected function casts(): array
    {
        return ['divisor' => 'decimal:2'];
    }
}
