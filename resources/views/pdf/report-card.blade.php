@extends('pdf.layout')

@section('title', __('documents.kinds.report_cards'))

@section('styles')
    .facts { display: grid; grid-template-columns: 1fr 1fr; gap: 0 6mm; margin-bottom: 3mm; }
    .summary { margin-top: 3mm; }
    @if ($paper === 'A5')
        body { font-size: 8.5pt; line-height: 1.35; }
        .kingdom { font-size: 9pt; }
        .title { font-size: 11pt; }
        .school { font-size: 8pt; }
        .school img { height: 10mm; }
        .signature .role { margin-bottom: 10mm; }
        .signature .date { font-size: 7.5pt; }
    @endif
@endsection

@section('content')
    @foreach ($rows as $row)
        @php($student = $students[$row['enrollment_id']]->student)
        <section class="page">
            @include('pdf.partials.letterhead')
            <h1 class="title moul">{{ __('documents.kinds.report_card') }}</h1>
            <p class="subtitle">{{ $exam->name }} · {{ __('documents.year') }} {{ $year?->name }}</p>

            <div class="facts">
                <div>{{ __('documents.name') }}: <span class="strong">{{ $row['name_km'] }}</span>@if ($student->nameEn()) ({{ $student->nameEn() }})@endif</div>
                <div>{{ __('documents.student_code') }}: {{ $row['student_code'] }}</div>
                <div>{{ __('documents.gender') }}: {{ __("documents.genders.{$row['gender']}") }}</div>
                <div>{{ __('documents.dob') }}: {{ $format->date($student->dob) }}</div>
                <div>{{ __('documents.class') }}: {{ $classroom->name }}</div>
                <div>{{ __('documents.homeroom') }}: {{ $classroom->homeroomTeacher?->name_km ?? '—' }}</div>
            </div>

            <table class="grid">
                <thead>
                    <tr>
                        <th>{{ __('documents.subject') }}</th>
                        <th>{{ __('documents.max') }}</th>
                        <th>{{ __('documents.score') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($subjects as $subject)
                        <tr>
                            <td>{{ app()->getLocale() === 'en' ? ($subject['name_en'] ?: $subject['name_km']) : ($subject['name_km'] ?: $subject['name_en']) }}</td>
                            <td class="num">{{ $format->cell($subject['max']) }}</td>
                            <td class="num">{{ $format->cell($row['cells'][$subject['id']] ?? null) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <table class="grid summary">
                <thead>
                    <tr>
                        <th>{{ __('results.total') }}</th>
                        <th>{{ __('results.average') }}</th>
                        <th>{{ __('results.rank_class') }}</th>
                        @if ($row['rank_grade'] !== null)
                            <th>{{ __('results.rank_grade') }}</th>
                        @endif
                        <th>{{ __('results.mention') }}</th>
                        <th>{{ __('results.result') }}</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="num">{{ $format->decimal($row['total']) }}</td>
                        <td class="num strong">{{ $format->decimal($row['average']) }}</td>
                        <td class="num strong">{{ $format->int($row['rank_class']) }} / {{ $format->int($classSize) }}</td>
                        @if ($row['rank_grade'] !== null)
                            <td class="num">{{ $format->int($row['rank_grade']) }}</td>
                        @endif
                        <td class="num">{{ $row['mention'] ? __("results.mentions.{$row['mention']}") : '—' }}</td>
                        <td class="num">{{ __($row['passed'] ? 'results.passed' : 'results.failed') }}</td>
                    </tr>
                </tbody>
            </table>
            <p class="note">{{ __('documents.legend') }}</p>

            @include('pdf.partials.signatures', ['roles' => ['guardian', 'homeroom', 'director']])
        </section>
    @endforeach
@endsection
