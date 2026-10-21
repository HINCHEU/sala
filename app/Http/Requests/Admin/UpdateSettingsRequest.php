<?php

namespace App\Http\Requests\Admin;

use App\Domain\School\BrandColor;
use App\Domain\School\Enums\Role;
use App\Domain\School\Settings\SchoolSettings;
use App\Http\Middleware\SetLocale;
use App\Support\Tenancy\CurrentSchool;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // route middleware authorises
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $weight = ['required', 'numeric', 'between:0,1'];

        return [
            'name_km' => ['required', 'string', 'max:255'],
            'name_en' => ['required', 'string', 'max:255'],
            'report_header' => ['nullable', 'string', 'max:1000'],
            'brand_color' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
            // No SVG: it can carry scripts. Raster only, re-encoded to PNG on save.
            'logo' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:2048'],

            'settings.locale' => ['required', Rule::in(SetLocale::LOCALES)],
            'settings.averageMethod' => ['required', Rule::in(['divisor', 'weighted', 'simple'])],
            'settings.semesterMonthlyWeight' => $weight,
            'settings.semesterExamWeight' => $weight,
            'settings.annualS1Weight' => $weight,
            'settings.annualS2Weight' => $weight,
            'settings.absentSubjectPolicy' => ['required', Rule::in(['zero', 'exclude_from_rank'])],
            'settings.rankAcrossGrade' => ['required', 'boolean'],
            'settings.scoreApproverRole' => ['required', Rule::in(Role::values())],
            'settings.attendanceMode' => ['required', Rule::in(['daily', 'period', 'both'])],
            'settings.khmerNumerals' => ['required', 'boolean'],
        ];
    }

    /** @return array<int, callable> */
    public function after(): array
    {
        return [function (Validator $validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $color = $this->input('brand_color');
            if ($color && ! BrandColor::isReadable($color)) {
                $validator->errors()->add('brand_color', __('settings.brand_color_contrast', [
                    'suggestion' => BrandColor::suggestDarker($color),
                ]));
            }

            foreach ([['semesterMonthlyWeight', 'semesterExamWeight'], ['annualS1Weight', 'annualS2Weight']] as [$a, $b]) {
                // Compare in hundredths so 0.7 + 0.3 is exactly 1.
                if ((int) round(($this->input("settings.$a") + $this->input("settings.$b")) * 100) !== 100) {
                    $validator->errors()->add("settings.$a", __('settings.weights_sum'));
                }
            }
        }];
    }

    /** @return array<string, mixed> */
    public function profile(): array
    {
        $profile = $this->safe()->only(['name_km', 'name_en', 'report_header', 'brand_color']);
        $profile['brand_color'] = $profile['brand_color'] ? strtolower($profile['brand_color']) : null;

        return $profile;
    }

    public function settings(): SchoolSettings
    {
        // Merge onto the saved settings so fields this page does not edit (promotion rules, modules) survive.
        $current = app(CurrentSchool::class)->get()->settings->toArray();
        $input = $this->validated('settings');

        // Multipart forms send everything as strings ("1", "0.5").
        foreach (['semesterMonthlyWeight', 'semesterExamWeight', 'annualS1Weight', 'annualS2Weight'] as $key) {
            $input[$key] = (float) $input[$key];
        }
        foreach (['rankAcrossGrade', 'khmerNumerals'] as $key) {
            $input[$key] = $this->boolean("settings.$key");
        }

        return SchoolSettings::from([...$current, ...$input]);
    }
}
