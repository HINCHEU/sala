<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Academic\Models\Subject;
use App\Domain\Exams\Models\Exam;
use App\Domain\People\Models\Enrollment;
use App\Domain\People\Models\Student;
use App\Domain\People\Models\Teacher;
use App\Domain\Results\Models\ScoreChangeRequest;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Activity\SchoolActivity;
use App\Support\Tenancy\CurrentSchool;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Lang;
use Inertia\Inertia;
use Inertia\Response;

/** The director's view of who did what in the school. */
class ActivityController extends Controller
{
    /** Event name prefixes of each filter. */
    private const GROUPS = [
        'scores' => ['score.', 'score_change.'],
        'exams' => ['exam.'],
        'people' => ['student.', 'students.', 'teacher.', 'guardian.', 'enrollment.', 'document.'],
        'academic' => ['academic.', 'teaching.', 'approval.'],
        'members' => ['member.'],
    ];

    public function index(Request $request, CurrentSchool $current): Response
    {
        $filters = $request->validate([
            'group' => ['nullable', 'in:'.implode(',', array_keys(self::GROUPS))],
            'user' => ['nullable', 'integer'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
        ]);
        $school = $current->get();

        $page = SchoolActivity::where('school_id', $school->id)
            ->when($filters['group'] ?? null, fn (Builder $q, string $group) => $q->where(function (Builder $q) use ($group) {
                foreach (self::GROUPS[$group] as $prefix) {
                    $q->orWhere('description', 'like', $prefix.'%');
                }
            }))
            ->when($filters['user'] ?? null, fn (Builder $q, $user) => $q->where('causer_type', (new User)->getMorphClass())->where('causer_id', $user))
            ->when($filters['from'] ?? null, fn (Builder $q, string $from) => $q->where('created_at', '>=', $from.' 00:00:00'))
            ->when($filters['to'] ?? null, fn (Builder $q, string $to) => $q->where('created_at', '<=', $to.' 23:59:59'))
            ->with('causer', 'subject')
            ->latest('id')
            ->paginate(50)
            ->withQueryString();

        $names = $this->names(collect($page->items()));

        return Inertia::render('School/Activity', [
            'events' => $page->through(fn (SchoolActivity $a) => $this->row($a, $names)),
            'filters' => [
                'group' => $filters['group'] ?? '',
                'user' => (int) ($filters['user'] ?? 0) ?: '',
                'from' => $filters['from'] ?? '',
                'to' => $filters['to'] ?? '',
            ],
            'groups' => array_keys(self::GROUPS),
            'users' => $school->users()->orderBy('name')->get(['users.id', 'users.name'])->map(fn (User $u) => $u->only('id', 'name')),
        ]);
    }

    /**
     * @param  array<string, array<int|string, mixed>>  $names
     * @return array<string, mixed>
     */
    private function row(SchoolActivity $activity, array $names): array
    {
        $key = 'activity.events.'.$activity->description;
        $subject = $activity->subject;
        $properties = $activity->properties;

        $student = $names['students'][$this->enrollmentId($activity)] ?? match (true) {
            $subject instanceof Student => $subject->nameKm(),
            $subject instanceof Teacher => $subject->name_km,
            default => null,
        };

        return [
            'id' => $activity->id,
            'at' => $activity->created_at?->timezone('Asia/Phnom_Penh')->format('Y-m-d H:i'),
            'who' => $activity->causer instanceof User ? $activity->causer->name : null,
            // A logged-model event ("updated") or one added later still shows, under its raw name.
            'event' => Lang::has($key) ? __($key) : $activity->description,
            'exam' => $names['exams'][$this->examId($activity)] ?? null,
            'person' => $student,
            'subject' => $names['subjects'][$properties->get('subject_id') ?? ($subject instanceof ScoreChangeRequest ? $subject->subject_id : null)] ?? null,
            // Only plain values: model events keep whole attribute arrays under the same key.
            'old' => is_scalar($properties->get('old')) ? $properties->get('old') : null,
            'new' => is_scalar($properties->get('new')) ? $properties->get('new') : null,
        ];
    }

    /**
     * The names this page needs, loaded at once rather than row by row.
     *
     * @param  Collection<int, SchoolActivity>  $activities
     * @return array<string, array<int|string, mixed>>
     */
    private function names(Collection $activities): array
    {
        $enrollments = $activities->map(fn (SchoolActivity $a) => $this->enrollmentId($a))->filter()->unique();
        $subjects = $activities->map(fn (SchoolActivity $a) => $a->properties->get('subject_id') ?? ($a->subject instanceof ScoreChangeRequest ? $a->subject->subject_id : null))
            ->filter()->unique();
        $exams = $activities->map(fn (SchoolActivity $a) => $this->examId($a))->filter()->unique();

        return [
            'students' => Enrollment::whereKey($enrollments)->with('student')->get()
                ->mapWithKeys(fn (Enrollment $e) => [$e->id => $e->student?->nameKm()])->all(),
            'subjects' => Subject::whereKey($subjects)->get()
                ->mapWithKeys(fn (Subject $s) => [$s->id => $s->name_km ?: $s->name_en])->all(),
            'exams' => Exam::whereKey($exams)->pluck('name', 'id')->all(),
        ];
    }

    private function enrollmentId(SchoolActivity $activity): ?int
    {
        $id = $activity->properties->get('enrollment_id') ?? ($activity->subject instanceof ScoreChangeRequest ? $activity->subject->enrollment_id : null);

        return is_numeric($id) ? (int) $id : null;
    }

    private function examId(SchoolActivity $activity): ?int
    {
        return $activity->subject instanceof Exam ? $activity->subject->id : ($activity->subject?->getAttribute('exam_id') ?: null);
    }
}
