<?php

namespace App\Http\Controllers\Academic;

use App\Domain\Academic\Models\Subject;
use App\Http\Controllers\Controller;
use App\Http\Requests\Academic\SubjectRequest;
use Illuminate\Http\RedirectResponse;

class SubjectController extends Controller
{
    public function store(SubjectRequest $request): RedirectResponse
    {
        Subject::create($request->validated());

        return back()->with('success', __('academic.saved'));
    }

    public function update(SubjectRequest $request, Subject $subject): RedirectResponse
    {
        $subject->update($request->validated());

        return back()->with('success', __('academic.saved'));
    }

    public function destroy(Subject $subject): RedirectResponse
    {
        return $this->destroyUnlessUsed($subject);
    }
}
