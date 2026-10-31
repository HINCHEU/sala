@extends('pdf.layout')

@section('title', __('documents.kinds.score_sheet'))

@section('styles')
    body { font-size: 9pt; }
    table.grid th { font-size: 8pt; line-height: 1.25; }
@endsection

@section('content')
    @include('pdf.partials.letterhead')
    <h1 class="title moul">{{ __('documents.kinds.score_sheet') }}</h1>
    <p class="subtitle">{{ $exam->name }} · {{ __('documents.class') }} {{ $classroom->name }} · {{ __('documents.year') }} {{ $year?->name }}</p>

    <table class="grid">
        <thead>
            <tr>
                <th>{{ __('documents.roll') }}</th>
                <th>{{ __('documents.name') }}</th>
                <th>{{ __('documents.gender') }}</th>
                @foreach ($subjects as $subject)
                    <th>{{ app()->getLocale() === 'en' ? ($subject['name_en'] ?: $subject['name_km']) : ($subject['name_km'] ?: $subject['name_en']) }}<br>/{{ $format->cell($subject['max']) }}</th>
                @endforeach
                <th>{{ __('results.total') }}</th>
                <th>{{ __('results.average') }}</th>
                <th>{{ __('results.rank') }}</th>
                <th>{{ __('results.mention') }}</th>
                <th>{{ __('results.result') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($rows as $row)
                <tr>
                    <td class="num">{{ $format->int($row['roll_no']) }}</td>
                    <td class="name">{{ $row['name_km'] }}</td>
                    <td class="num">{{ __("documents.genders.{$row['gender']}") }}</td>
                    @foreach ($subjects as $subject)
                        <td class="num">{{ $format->cell($row['cells'][$subject['id']] ?? null) }}</td>
                    @endforeach
                    <td class="num">{{ $format->decimal($row['total']) }}</td>
                    <td class="num strong">{{ $format->decimal($row['average']) }}</td>
                    <td class="num strong">{{ $format->int($row['rank_class']) }}</td>
                    <td class="num">{{ $row['mention'] ? __("results.mentions.{$row['mention']}") : '—' }}</td>
                    <td class="num">{{ __($row['passed'] ? 'results.passed' : 'results.failed') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
    <p class="note">
        {{ __('documents.counts', ['total' => $format->int($classSize), 'girls' => $format->int($girls), 'passed' => $format->int($passed)]) }}
        · {{ __('documents.legend') }}
    </p>

    @include('pdf.partials.signatures', ['roles' => ['homeroom', 'office', 'director']])
@endsection
