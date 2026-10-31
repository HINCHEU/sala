@extends('pdf.layout')

@section('title', __('documents.kinds.honor_roll'))

@section('content')
    @include('pdf.partials.letterhead')
    <h1 class="title moul">{{ __('documents.kinds.honor_roll') }}</h1>
    <p class="subtitle">{{ $exam->name }} · {{ __('documents.class') }} {{ $classroom->name }} · {{ __('documents.year') }} {{ $year?->name }}</p>

    <table class="grid">
        <thead>
            <tr>
                <th>{{ __('results.rank') }}</th>
                <th>{{ __('documents.name') }}</th>
                <th>{{ __('documents.gender') }}</th>
                <th>{{ __('results.average') }}</th>
                <th>{{ __('results.mention') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($honorRoll as $row)
                <tr>
                    <td class="num strong">{{ $format->int($row['rank_class']) }}</td>
                    <td class="name">{{ $row['name_km'] }}</td>
                    <td class="num">{{ __("documents.genders.{$row['gender']}") }}</td>
                    <td class="num">{{ $format->decimal($row['average']) }}</td>
                    <td class="num">{{ $row['mention'] ? __("results.mentions.{$row['mention']}") : '—' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    @include('pdf.partials.signatures', ['roles' => ['homeroom', 'director']])
@endsection
