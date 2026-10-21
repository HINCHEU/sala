<?php

namespace App\Http\Requests;

use App\Support\Tenancy\CurrentSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;
use Illuminate\Validation\Rules\Unique;

/**
 * Base for forms that touch school-owned tables: plain exists/unique rules would see every school's rows,
 * so a form could link another school's record or collide with its codes.
 */
abstract class SchoolRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // route middleware authorises
    }

    protected function schoolId(): int
    {
        return app(CurrentSchool::class)->idOrFail();
    }

    protected function existsInSchool(string $table): Exists
    {
        return Rule::exists($table, 'id')->where('school_id', $this->schoolId());
    }

    protected function uniqueInSchool(string $table, string $column, ?Model $ignore = null): Unique
    {
        return Rule::unique($table, $column)->where('school_id', $this->schoolId())->ignore($ignore);
    }
}
