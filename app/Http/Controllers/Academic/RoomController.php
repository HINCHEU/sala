<?php

namespace App\Http\Controllers\Academic;

use App\Domain\Academic\Models\Room;
use App\Http\Controllers\Controller;
use App\Http\Requests\Academic\RoomRequest;
use Illuminate\Http\RedirectResponse;

class RoomController extends Controller
{
    public function store(RoomRequest $request): RedirectResponse
    {
        Room::create($request->validated());

        return back()->with('success', __('academic.saved'));
    }

    public function update(RoomRequest $request, Room $room): RedirectResponse
    {
        $room->update($request->validated());

        return back()->with('success', __('academic.saved'));
    }

    public function destroy(Room $room): RedirectResponse
    {
        return $this->destroyUnlessUsed($room);
    }
}
