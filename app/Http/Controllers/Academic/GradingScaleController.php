<?php

namespace App\Http\Controllers\Academic;

use App\Domain\Academic\Enums\GradeStage;
use App\Domain\Academic\Models\GradingScale;
use App\Domain\Academic\MoeysTemplate;
use App\Http\Controllers\Controller;
use App\Http\Requests\Academic\GradingScaleRequest;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class GradingScaleController extends Controller
{
    private const STAGES = [GradeStage::Primary, GradeStage::LowerSecondary, GradeStage::UpperSecondary];

    public function index(): Response
    {
        $saved = GradingScale::all()->keyBy(fn (GradingScale $scale) => $scale->stage->value);

        return Inertia::render('Academic/Grading', [
            // A stage without a saved scale starts from the MoEYS default, shown as unsaved.
            'scales' => collect(self::STAGES)->map(fn (GradeStage $stage) => [
                'stage' => $stage->value,
                'saved' => $saved->has($stage->value),
                'bands' => $saved->has($stage->value) ? $saved[$stage->value]->bands : MoeysTemplate::bands($stage),
            ]),
        ]);
    }

    public function update(GradingScaleRequest $request, GradeStage $stage): RedirectResponse
    {
        abort_unless(in_array($stage, self::STAGES, true), 404);

        GradingScale::updateOrCreate(['stage' => $stage], ['bands' => $request->bands()]);

        return back()->with('success', __('academic.saved'));
    }
}
