<?php

namespace App\Domain\School\Models;

use App\Domain\School\Enums\Role;
use App\Models\User;
use App\Support\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property Role $role
 * @property-read School $school
 */
class SchoolInvitation extends Model
{
    use BelongsToSchool;

    protected $fillable = ['school_id', 'email', 'phone', 'role', 'token_hash', 'invited_by', 'expires_at'];

    protected function casts(): array
    {
        return [
            'role' => Role::class,
            'expires_at' => 'datetime',
            'accepted_at' => 'datetime',
        ];
    }

    /** Invitation links carry the plain token; only its hash is stored. */
    public static function findByToken(string $token): ?self
    {
        return static::withoutGlobalScope('school')
            ->where('token_hash', hash('sha256', $token))
            ->whereNull('accepted_at')
            ->where('expires_at', '>', now())
            ->first();
    }

    /** @param Builder<self> $query */
    public function scopePending(Builder $query): void
    {
        $query->whereNull('accepted_at')->where('expires_at', '>', now());
    }

    public function matches(User $user): bool
    {
        return ($this->email !== null && $this->email === $user->email)
            || ($this->phone !== null && $this->phone === $user->phone);
    }

    /** @return BelongsTo<User, $this> */
    public function inviter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by');
    }
}
