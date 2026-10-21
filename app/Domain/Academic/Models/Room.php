<?php

namespace App\Domain\Academic\Models;

use App\Support\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class Room extends Model
{
    use BelongsToSchool;

    protected $fillable = ['name', 'building', 'capacity'];
}
