<!doctype html>
<html lang="{{ app()->getLocale() }}">
<head>
<meta charset="utf-8">
<title>@yield('title')</title>
<style>
    @font-face { font-family: Battambang; font-weight: 400; src: url(battambang-latin-400-normal.woff2) format('woff2'); }
    @font-face { font-family: Battambang; font-weight: 700; src: url(battambang-latin-700-normal.woff2) format('woff2'); }
    @font-face { font-family: Battambang; font-weight: 400; src: url(battambang-khmer-400-normal.woff2) format('woff2'); unicode-range: U+1780-17FF, U+19E0-19FF, U+200C-200D, U+25CC; }
    @font-face { font-family: Battambang; font-weight: 700; src: url(battambang-khmer-700-normal.woff2) format('woff2'); unicode-range: U+1780-17FF, U+19E0-19FF, U+200C-200D, U+25CC; }
    @font-face { font-family: Moul; src: url(moul-latin-400-normal.woff2) format('woff2'); }
    @font-face { font-family: Moul; src: url(moul-khmer-400-normal.woff2) format('woff2'); unicode-range: U+1780-17FF, U+19E0-19FF, U+200C-200D, U+25CC; }

    * { box-sizing: border-box; }
    body { margin: 0; font-family: Battambang, sans-serif; font-size: 10.5pt; line-height: 1.5; color: #000; }
    /* Moul's vowels rise far above its line box: the room keeps them from painting on the page before. */
    .moul { font-family: Moul, Battambang, serif; font-weight: 400; line-height: 2; }
    .page { break-after: page; padding-top: 1px; }
    .page:last-child { break-after: auto; }

    .letterhead { display: grid; grid-template-columns: 1fr auto 1fr; align-items: start; margin-bottom: 3mm; }
    .school { text-align: center; justify-self: start; max-width: 75mm; font-size: 9.5pt; }
    .school img { display: block; height: 15mm; max-width: 30mm; object-fit: contain; margin: 0 auto 1mm; }
    .school-header { white-space: pre-line; font-size: 8.5pt; }
    .kingdom { text-align: center; font-size: 11pt; }
    .title { text-align: center; font-size: 13pt; margin: 2mm 0 0; }
    .subtitle { text-align: center; margin: 0 0 3mm; }

    table.grid { width: 100%; border-collapse: collapse; }
    table.grid th, table.grid td { border: 0.6pt solid #000; padding: 0.6mm 1.2mm; }
    table.grid th { font-weight: 700; background: #eee; text-align: center; }
    table.grid tr { break-inside: avoid; }
    table.grid thead { display: table-header-group; }
    .num { text-align: center; white-space: nowrap; }
    .name { white-space: nowrap; }
    .strong { font-weight: 700; }

    .signatures { display: flex; justify-content: space-between; align-items: flex-end; gap: 4mm; margin-top: 4mm; text-align: center; break-inside: avoid; }
    .signature { flex: 1; }
    .signature.dated { flex: 0 0 auto; }
    .signature .date { font-size: 8.5pt; white-space: nowrap; }
    .signature .role { font-weight: 700; margin-bottom: 15mm; }
    .note { font-size: 8.5pt; margin: 1.5mm 0 0; }

    @yield('styles')
</style>
</head>
<body>
@yield('content')
<script>document.fonts.ready.then(function () { window.fontsReady = true; });</script>
</body>
</html>
