<?php

namespace App\Domain\Academic\Models;

use App\Support\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class Track extends Model
{
    use BelongsToSchool;

    protected $fillable = ['code', 'name_km', 'name_en'];
}
