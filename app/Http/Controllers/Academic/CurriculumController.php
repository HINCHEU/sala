<?php

namespace App\Http\Controllers\Academic;

use App\Domain\Academic\Actions\CopyCurriculum;
use App\Domain\Academic\Actions\SaveCurriculum;
use App\Domain\Academic\Models\AcademicYear;
use App\Domain\Academic\Models\CurriculumSubject;
use App\Domain\Academic\Models\GradeDivisor;
use App\Domain\Academic\Models\GradeLevel;
use App\Domain\Academic\Models\Subject;
use App\Domain\Academic\Models\Track;
use App\Http\Controllers\Controller;
use App\Http\Requests\Academic\CurriculumRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/** The grade × subject grid for one year: max score, coefficient, pass score, order, and the divisor. */
class CurriculumController extends Controller
{
    public function edit(Request $request): Response
    {
        $years = AcademicYear::orderByDesc('start_date')->get(['id', 'name', 'is_current']);
        $grades = GradeLevel::orderBy('number')->get(['id', 'number', 'name_km', 'name_en', 'has_tracks']);
        $tracks = Track::orderBy('code')->get(['id', 'name_km', 'name_en']);

        $yearId = $request->integer('year') ?: ($years->firstWhere('is_current', true) ?? $years->first())?->id;
        $grade = $grades->firstWhere('id', $request->integer('grade')) ?? $grades->first();
        $trackId = $grade?->has_tracks ? ($request->integer('track') ?: $tracks->first()?->id) : null;

        $scope = ['academic_year_id' => $yearId, 'grade_level_id' => $grade?->id, 'track_id' => $trackId];

        return Inertia::render('Academic/Curriculum', [
            'years' => $years,
            'grades' => $grades,
            'tracks' => $tracks,
            'subjects' => Subject::orderBy('category')->orderBy('code')->get(['id', 'code', 'name_km', 'name_en']),
            'selection' => ['year' => $yearId, 'grade' => $grade?->id, 'track' => $trackId],
            'rows' => $yearId && $grade ? CurriculumSubject::where($scope)->orderBy('sort')
                ->get(['subject_id', 'max_score', 'coefficient', 'pass_score', 'sort']) : [],
            'divisor' => $yearId && $grade ? GradeDivisor::where($scope)->value('divisor') : null,
        ]);
    }

    public function update(CurriculumRequest $request, SaveCurriculum $save): RedirectResponse
    {
        $divisor = $request->validated('divisor');

        $save->handle(
            $request->integer('academic_year_id'),
            $request->integer('grade_level_id'),
            $request->filled('track_id') ? $request->integer('track_id') : null,
            $request->rows(),
            $divisor === null ? null : (float) $divisor,
        );

        return back()->with('success', __('academic.saved'));
    }

    public function copy(Request $request, CopyCurriculum $copy): RedirectResponse
    {
        $data = $request->validate([
            'from_year_id' => ['required', 'different:to_year_id', Rule::exists('academic_years', 'id')],
            'to_year_id' => ['required', Rule::exists('academic_years', 'id')],
        ]);

        // findOrFail goes through the school scope, so another school's year is a 404.
        $count = $copy->handle(AcademicYear::findOrFail($data['from_year_id']), AcademicYear::findOrFail($data['to_year_id']));

        return back()->with('success', __('academic.curriculum_copied', ['count' => $count]));
    }
}
