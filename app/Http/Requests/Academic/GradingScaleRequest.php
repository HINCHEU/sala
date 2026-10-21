<?php

namespace App\Http\Requests\Academic;

use App\Domain\Academic\Enums\Mention;
use App\Http\Requests\SchoolRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class GradingScaleRequest extends SchoolRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'bands' => ['required', 'array', 'min:1', 'max:10'],
            'bands.*.min' => ['required', 'numeric', 'min:0', 'max:1000'],
            'bands.*.max' => ['required', 'numeric', 'gt:bands.*.min', 'max:1000'],
            'bands.*.mention' => ['required', Rule::enum(Mention::class)],
            'bands.*.mention_km' => ['required', 'string', 'max:50'],
            'bands.*.mention_en' => ['required', 'string', 'max:50'],
            'bands.*.letter' => ['nullable', 'string', 'max:2'],
        ];
    }

    /** @return array<int, callable> */
    public function after(): array
    {
        return [function (Validator $validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            // An average must land in exactly one band: no two bands may overlap.
            $bands = $this->collect('bands')->sortByDesc('min')->values();
            foreach ($bands->slice(1) as $i => $band) {
                if ((float) $band['max'] > (float) $bands[$i - 1]['min']) {
                    $validator->errors()->add('bands', __('academic.bands_overlap'));

                    return;
                }
            }
        }];
    }

    /** @return list<array{min: float, max: float, mention: string, mention_km: string, mention_en: string, letter: string}> */
    public function bands(): array
    {
        return $this->safe()->collect('bands')
            ->map(fn (array $b) => [
                'min' => (float) $b['min'],
                'max' => (float) $b['max'],
                'mention' => $b['mention'],
                'mention_km' => $b['mention_km'],
                'mention_en' => $b['mention_en'],
                'letter' => (string) ($b['letter'] ?? ''),
            ])
            ->sortByDesc('min')->values()->all();
    }
}
