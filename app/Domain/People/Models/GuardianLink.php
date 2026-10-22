<?php

namespace App\Domain\People\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * How a guardian relates to one student (guardian_student).
 *
 * @property string $relationship
 * @property bool $is_primary_contact
 * @property bool $can_pickup
 */
class GuardianLink extends Pivot
{
    protected $table = 'guardian_student';

    public $incrementing = true;

    protected function casts(): array
    {
        return ['is_primary_contact' => 'boolean', 'can_pickup' => 'boolean'];
    }
}
