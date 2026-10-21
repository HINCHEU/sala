<?php

namespace App\Support\Tenancy;

use App\Domain\School\Models\School;
use RuntimeException;

/**
 * The school the current request (or queued job) acts for. Registered as a scoped singleton,
 * so it resets between requests and jobs.
 */
class CurrentSchool
{
    private ?School $school = null;

    public function set(?School $school): void
    {
        $this->school = $school;
        setPermissionsTeamId($school?->id);
    }

    public function get(): ?School
    {
        return $this->school;
    }

    public function id(): ?int
    {
        return $this->school?->id;
    }

    public function idOrFail(): int
    {
        return $this->id() ?? throw new RuntimeException('No current school: school-owned records cannot be created here.');
    }

    /**
     * Run a callback as another school, then restore the previous one.
     *
     * @template T
     *
     * @param  callable(): T  $callback
     * @return T
     */
    public function runAs(School $school, callable $callback): mixed
    {
        $previous = $this->school;
        $this->set($school);

        try {
            return $callback();
        } finally {
            $this->set($previous);
        }
    }
}
