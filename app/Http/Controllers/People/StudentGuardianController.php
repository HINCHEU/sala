<?php

namespace App\Http\Controllers\People;

use App\Domain\People\Actions\LinkGuardian;
use App\Domain\People\Models\Guardian;
use App\Domain\People\Models\Student;
use App\Http\Controllers\Controller;
use App\Http\Requests\People\GuardianRequest;
use App\Support\PhoneNumber;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StudentGuardianController extends Controller
{
    /** The guardian a phone belongs to, so the form can say "this parent is already here" before saving. */
    public function lookup(Request $request): JsonResponse
    {
        $phone = PhoneNumber::normalize($request->string('phone')->toString());
        $guardian = $phone ? Guardian::where('phone', $phone)->with('students:id,last_name_km,first_name_km')->first() : null;

        return response()->json(['guardian' => $guardian ? [
            'id' => $guardian->id,
            'name_km' => $guardian->name_km,
            'children' => $guardian->students->map->nameKm(),
        ] : null]);
    }

    public function store(GuardianRequest $request, Student $student, LinkGuardian $link): RedirectResponse
    {
        $existing = $request->phone() ? Guardian::where('phone', $request->phone())->exists() : false;
        $link->handle($student, $request->guardian(), $request->link());

        return back()->with('success', __($existing ? 'people.guardian_linked_existing' : 'people.saved'));
    }

    public function update(GuardianRequest $request, Student $student, Guardian $guardian): RedirectResponse
    {
        $this->linked($student, $guardian);

        DB::transaction(function () use ($request, $student, $guardian) {
            $guardian->update($request->guardian());
            $student->guardians()->updateExistingPivot($guardian->id, $request->link());
            if ($request->link()['is_primary_contact']) {
                LinkGuardian::makePrimary($student, $guardian);
            }
            activity()->performedOn($student)->causedBy(auth()->user())
                ->withProperties(['guardian_id' => $guardian->id])->log('guardian.updated');
        });

        return back()->with('success', __('people.saved'));
    }

    /** Unlinks only: the guardian stays, since a sibling may still need them. */
    public function destroy(Student $student, Guardian $guardian): RedirectResponse
    {
        $this->linked($student, $guardian);
        $student->guardians()->detach($guardian->id);
        activity()->performedOn($student)->causedBy(auth()->user())
            ->withProperties(['guardian_id' => $guardian->id])->log('guardian.unlinked');

        return back()->with('success', __('people.guardian_unlinked'));
    }

    private function linked(Student $student, Guardian $guardian): void
    {
        abort_unless($student->guardians()->whereKey($guardian->id)->exists(), 404);
    }
}
