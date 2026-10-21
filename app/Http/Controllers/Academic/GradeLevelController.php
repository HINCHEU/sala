<?php

namespace App\Http\Controllers\Academic;

use App\Domain\Academic\Models\GradeLevel;
use App\Http\Controllers\Controller;
use App\Http\Requests\Academic\GradeLevelRequest;
use Illuminate\Http\RedirectResponse;

class GradeLevelController extends Controller
{
    public function store(GradeLevelRequest $request): RedirectResponse
    {
        GradeLevel::create($request->validated());

        return back()->with('success', __('academic.saved'));
    }

    public function update(GradeLevelRequest $request, GradeLevel $grade): RedirectResponse
    {
        $grade->update($request->validated());

        return back()->with('success', __('academic.saved'));
    }

    public function destroy(GradeLevel $grade): RedirectResponse
    {
        return $this->destroyUnlessUsed($grade);
    }
}
