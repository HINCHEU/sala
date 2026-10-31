<header class="letterhead">
    <div class="school">
        @if ($logo)
            <img src="{{ $logo }}" alt="">
        @endif
        <div class="moul">{{ $school->name() }}</div>
        @if ($school->report_header)
            <div class="school-header">{{ $school->report_header }}</div>
        @endif
    </div>
    <div class="kingdom moul">
        <div>ព្រះរាជាណាចក្រកម្ពុជា</div>
        <div>ជាតិ សាសនា ព្រះមហាក្សត្រ</div>
    </div>
    <div></div>
</header>
