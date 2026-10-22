<?php

use Illuminate\Support\Facades\Storage;

/** The media disks as config/filesystems.php builds them for a server on R2. */
function r2Disks(): array
{
    $env = ['MEDIA_DRIVER' => 's3', 'MEDIA_PUBLIC_URL' => 'https://pub-x.r2.dev', 'MEDIA_BUCKET' => 'sala-private',
        'MEDIA_PUBLIC_BUCKET' => 'sala-public', 'MEDIA_KEY' => 'k', 'MEDIA_SECRET' => 's', 'MEDIA_ENDPOINT' => 'https://acct.r2.cloudflarestorage.com'];
    foreach ($env as $key => $value) {
        $_ENV[$key] = $_SERVER[$key] = $value;
    }

    try {
        return (require config_path('filesystems.php'))['disks'];
    } finally {
        foreach ($env as $key => $value) {
            unset($_ENV[$key], $_SERVER[$key]);
        }
    }
}

it('stores files on R2 at the key the public URL points to', function () {
    $public = Storage::build(r2Disks()['media_public']);

    expect($public->path('schools/1/logo.png'))->toBe('schools/1/logo.png')
        ->and($public->url('schools/1/logo.png'))->toBe('https://pub-x.r2.dev/schools/1/logo.png');
});

it('keeps private R2 keys free of the local storage path', function () {
    expect(Storage::build(r2Disks()['media_private'])->path('students/1/photo.jpg'))->toBe('students/1/photo.jpg');
});
