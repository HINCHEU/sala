<?php

namespace App\Domain\People\Models;

use App\Domain\People\Enums\Gender;
use App\Support\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * A parent or guardian: one record per phone number per school, shared by siblings.
 *
 * @property ?Gender $gender
 * @property-read GuardianLink $pivot set when loaded through Student::guardians()
 */
class Guardian extends Model
{
    use BelongsToSchool, SoftDeletes;

    protected $fillable = ['name_km', 'name_en', 'gender', 'occupation', 'phone', 'address'];

    protected function casts(): array
    {
        return ['gender' => Gender::class];
    }

    /** @return BelongsToMany<Student, $this, GuardianLink> */
    public function students(): BelongsToMany
    {
        return $this->belongsToMany(Student::class)->using(GuardianLink::class)
            ->withPivot('relationship', 'is_primary_contact', 'can_pickup')
            ->withTimestamps();
    }
}
