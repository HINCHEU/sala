<div class="signatures">
    @foreach ($roles as $role)
        <div class="signature @if ($loop->last) dated @endif">
            @if ($loop->last)
                <div class="date">{{ __('documents.lunar_date') }}</div>
                <div class="date">{{ __('documents.made_on', ['date' => $format->date($printedAt)]) }}</div>
            @endif
            <div class="role">{{ __("documents.signatures.{$role}") }}</div>
        </div>
    @endforeach
</div>
