<?php

namespace App\Http\Controllers\Academic;

use App\Domain\Academic\Actions\ApplyMoeysTemplate;
use App\Domain\Academic\Enums\GradeStage;
use App\Domain\Academic\Enums\SubjectCategory;
use App\Domain\Academic\Models\GradeLevel;
use App\Domain\Academic\Models\Room;
use App\Domain\Academic\Models\Subject;
use App\Domain\Academic\Models\Track;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/** Grades, tracks, subjects and rooms: the school-level lists every year reuses. */
class StructureController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Academic/Structure', [
            'grades' => GradeLevel::orderBy('number')->get(),
            'tracks' => Track::orderBy('code')->get(),
            'subjects' => Subject::orderBy('category')->orderBy('code')->get(),
            'rooms' => Room::orderBy('name')->get(),
            'stages' => array_column(GradeStage::cases(), 'value'),
            'categories' => array_column(SubjectCategory::cases(), 'value'),
        ]);
    }

    public function applyTemplate(ApplyMoeysTemplate $apply): RedirectResponse
    {
        $apply->handle();

        return back()->with('success', __('academic.template_applied'));
    }
}
