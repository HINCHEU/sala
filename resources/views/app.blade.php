@php($school = app(\App\Support\Tenancy\CurrentSchool::class)->get())
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="light"
      @if ($school?->brand_color) style="--primary: {{ $school->brand_color }}; --primary-soft: {{ $school->brand_color_soft }};" @endif>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <script>
            (function () {
                try {
                    var theme = localStorage.getItem('sala-theme');
                    if (!theme) {
                        theme = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
                    }
                    document.documentElement.setAttribute('data-theme', theme);
                } catch (e) {
                    document.documentElement.setAttribute('data-theme', 'light');
                }
            })();
        </script>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @inertiaHead
    </head>
    <body>
        @inertia
    </body>
</html>
