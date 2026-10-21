<?php

namespace App\Http\Controllers\Academic;

use App\Domain\Academic\Actions\CreateAcademicYear;
use App\Domain\Academic\Actions\SetCurrentYear;
use App\Domain\Academic\Models\AcademicYear;
use App\Domain\Academic\Models\Term;
use App\Http\Controllers\Controller;
use App\Http\Requests\Academic\AcademicYearRequest;
use App\Http\Requests\Academic\TermRequest;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class AcademicYearController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Academic/Years', [
            'years' => AcademicYear::with('terms')->orderByDesc('start_date')->get(),
        ]);
    }

    public function store(AcademicYearRequest $request, CreateAcademicYear $create): RedirectResponse
    {
        $create->handle($request->validated());

        return back()->with('success', __('academic.saved'));
    }

    public function update(AcademicYearRequest $request, AcademicYear $year): RedirectResponse
    {
        $year->update($request->validated());

        return back()->with('success', __('academic.saved'));
    }

    public function destroy(AcademicYear $year): RedirectResponse
    {
        if ($year->is_current) {
            return back()->with('error', __('academic.cannot_delete_current'));
        }

        return $this->destroyUnlessUsed($year);
    }

    public function setCurrent(AcademicYear $year, SetCurrentYear $setCurrent): RedirectResponse
    {
        $setCurrent->handle($year);

        return back()->with('success', __('academic.saved'));
    }

    public function storeTerm(TermRequest $request, AcademicYear $year): RedirectResponse
    {
        $year->terms()->create($request->validated());

        return back()->with('success', __('academic.saved'));
    }

    public function updateTerm(TermRequest $request, Term $term): RedirectResponse
    {
        $term->update($request->validated());

        return back()->with('success', __('academic.saved'));
    }

    public function destroyTerm(Term $term): RedirectResponse
    {
        return $this->destroyUnlessUsed($term);
    }
}
