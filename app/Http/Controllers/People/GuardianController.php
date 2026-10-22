<?php

namespace App\Http\Controllers\People;

use App\Domain\People\Models\Guardian;
use App\Domain\People\Models\Student;
use App\Http\Controllers\Controller;
use App\Support\PhoneNumber;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/** Every guardian with their children. Guardians are added and edited from a student's profile. */
class GuardianController extends Controller
{
    public function index(Request $request): Response
    {
        $q = trim($request->validate(['q' => ['nullable', 'string', 'max:100']])['q'] ?? '');
        $phone = PhoneNumber::normalize($q);
        $like = '%'.addcslashes($q, '%_\\').'%';

        $guardians = Guardian::query()
            ->when($q !== '', fn (Builder $query) => $query->where(fn (Builder $w) => $w
                ->where('name_km', 'like', $like)
                ->orWhere('name_en', 'like', $like)
                ->when($phone, fn (Builder $p) => $p->orWhere('phone', $phone))))
            ->with('students:id,last_name_km,first_name_km')
            ->orderBy('name_km')
            ->paginate(50)
            ->withQueryString()
            ->through(fn (Guardian $g) => [
                ...$g->only('id', 'name_km', 'name_en', 'phone', 'occupation'),
                'children' => $g->students->map(fn (Student $s) => ['id' => $s->id, 'name' => $s->nameKm()]),
            ]);

        return Inertia::render('People/Guardians', ['guardians' => $guardians, 'q' => $q]);
    }
}
