@php($school = app(\App\Support\Tenancy\CurrentSchool::class)->get())
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="light"
      @if ($school?->brand_color) style="--primary: {{ $school->brand_color }}; --primary-soft: {{ $school->brand_color_soft }};" @endif>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @inertiaHead
    </head>
    <body>
        @inertia
    </body>
</html>
