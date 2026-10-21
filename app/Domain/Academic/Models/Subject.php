<?php

namespace App\Domain\Academic\Models;

use App\Domain\Academic\Enums\SubjectCategory;
use App\Support\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class Subject extends Model
{
    use BelongsToSchool;

    protected $fillable = ['code', 'name_km', 'name_en', 'category'];

    protected function casts(): array
    {
        return ['category' => SubjectCategory::class];
    }
}
