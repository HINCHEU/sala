<?php

namespace App\Domain\Academic\Models;

use App\Domain\Academic\Enums\GradeStage;
use App\Support\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class GradeLevel extends Model
{
    use BelongsToSchool;

    protected $fillable = ['number', 'name_km', 'name_en', 'stage', 'has_tracks'];

    protected function casts(): array
    {
        return ['stage' => GradeStage::class, 'has_tracks' => 'boolean'];
    }
}
