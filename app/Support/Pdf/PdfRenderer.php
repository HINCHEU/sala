<?php

namespace App\Support\Pdf;

use Gotenberg\Gotenberg;
use Gotenberg\Stream;
use Illuminate\Support\Facades\Http;

/**
 * Blade HTML → Gotenberg (headless Chromium) → PDF bytes. Chromium is what shapes Khmer subscripts
 * correctly. Everything the page needs travels with the request: fonts as assets, images as data URIs.
 */
class PdfRenderer
{
    private const FONTS = [
        'battambang-khmer-400-normal.woff2',
        'battambang-khmer-700-normal.woff2',
        'battambang-latin-400-normal.woff2',
        'battambang-latin-700-normal.woff2',
        'moul-khmer-400-normal.woff2',
        'moul-latin-400-normal.woff2',
    ];

    private const PAPER_MM = ['A4' => [210, 297], 'A5' => [148, 210]];

    /** @param  array<string, mixed>  $data */
    public function render(string $view, array $data = [], string $paper = 'A4', bool $landscape = false): string
    {
        [$width, $height] = self::PAPER_MM[$paper];

        $chromium = Gotenberg::chromium((string) config('services.gotenberg.url'))->pdf()
            ->paperSize("{$width}mm", "{$height}mm")
            ->margins('10mm', '10mm', '10mm', '10mm')
            ->printBackground()
            ->waitForExpression('window.fontsReady === true')
            ->assets(...array_map(fn (string $font) => Stream::path(resource_path("fonts/pdf/{$font}")), self::FONTS));

        if ($landscape) {
            $chromium->landscape();
        }

        $request = $chromium->html(Stream::string('index.html', view($view, $data)->render()));

        return Http::timeout(60)
            ->withBody((string) $request->getBody(), $request->getHeaderLine('Content-Type'))
            ->post((string) $request->getUri())
            ->throw()
            ->body();
    }
}
