<?php

namespace App\Http\Controllers\Academic;

use App\Domain\Academic\Models\Track;
use App\Http\Controllers\Controller;
use App\Http\Requests\Academic\TrackRequest;
use Illuminate\Http\RedirectResponse;

class TrackController extends Controller
{
    public function store(TrackRequest $request): RedirectResponse
    {
        Track::create($request->validated());

        return back()->with('success', __('academic.saved'));
    }

    public function update(TrackRequest $request, Track $track): RedirectResponse
    {
        $track->update($request->validated());

        return back()->with('success', __('academic.saved'));
    }

    public function destroy(Track $track): RedirectResponse
    {
        return $this->destroyUnlessUsed($track);
    }
}
