<?php

use Illuminate\Support\Facades\Route;

beforeEach(function () {
    Route::get('/test-scheme', fn () => request()->getScheme().' '.request()->ip());
});

it('believes X-Forwarded-* from a proxy on a private network', function () {
    $this->withServerVariables(['REMOTE_ADDR' => '172.18.0.5'])
        ->get('/test-scheme', ['X-Forwarded-Proto' => 'https', 'X-Forwarded-For' => '203.0.113.9'])
        ->assertContent('https 203.0.113.9');
});

it('ignores X-Forwarded-* from a public address', function () {
    $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.7'])
        ->get('/test-scheme', ['X-Forwarded-Proto' => 'https', 'X-Forwarded-For' => '10.0.0.1'])
        ->assertContent('http 198.51.100.7');
});
