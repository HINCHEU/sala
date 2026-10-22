<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Filesystem Disk
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default filesystem disk that should be used
    | by the framework. The "local" disk, as well as a variety of cloud
    | based disks are available to your application for file storage.
    |
    */

    'default' => env('FILESYSTEM_DISK', 'local'),

    /*
    |--------------------------------------------------------------------------
    | Filesystem Disks
    |--------------------------------------------------------------------------
    |
    | Below you may configure as many filesystem disks as necessary, and you
    | may even configure multiple disks for the same driver. Examples for
    | most supported storage drivers are configured here for reference.
    |
    | Supported drivers: "local", "ftp", "sftp", "s3"
    |
    */

    'disks' => [

        'local' => [
            'driver' => 'local',
            'root' => storage_path('app/private'),
            'serve' => true,
            'throw' => false,
            'report' => false,
        ],

        'public' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => rtrim(env('APP_URL', 'http://localhost'), '/').'/storage',
            'visibility' => 'public',
            'throw' => false,
            'report' => false,
        ],

        // School files. Code only ever names these disks; MEDIA_DRIVER switches local <-> S3-compatible
        // (DO Spaces, R2, MinIO). The local driver ignores the s3 keys. root only applies to local: the s3 driver
        // would prefix every key with it, and its leading slash made R2 store keys the public URL never matched.
        'media_private' => [
            'driver' => env('MEDIA_DRIVER', 'local'),
            'root' => env('MEDIA_DRIVER', 'local') === 'local' ? storage_path('app/private/media') : '',
            'serve' => true,
            'key' => env('MEDIA_KEY'),
            'secret' => env('MEDIA_SECRET'),
            'region' => env('MEDIA_REGION', 'auto'),
            'bucket' => env('MEDIA_BUCKET'),
            'endpoint' => env('MEDIA_ENDPOINT'),
            'use_path_style_endpoint' => env('MEDIA_PATH_STYLE', true),
            'visibility' => 'private',
            'throw' => true,
            'report' => false,
        ],

        'media_public' => [
            'driver' => env('MEDIA_DRIVER', 'local'),
            'root' => env('MEDIA_DRIVER', 'local') === 'local' ? storage_path('app/public/media') : '',
            'url' => env('MEDIA_PUBLIC_URL', '/storage/media'),
            'key' => env('MEDIA_KEY'),
            'secret' => env('MEDIA_SECRET'),
            'region' => env('MEDIA_REGION', 'auto'),
            'bucket' => env('MEDIA_PUBLIC_BUCKET', env('MEDIA_BUCKET')),
            'endpoint' => env('MEDIA_ENDPOINT'),
            'use_path_style_endpoint' => env('MEDIA_PATH_STYLE', true),
            'visibility' => 'public',
            'throw' => true,
            'report' => false,
        ],

        // Off-server bucket for nightly database dumps; never the same bucket as media.
        'backups' => [
            'driver' => 's3',
            'key' => env('BACKUP_KEY'),
            'secret' => env('BACKUP_SECRET'),
            'region' => env('BACKUP_REGION', 'auto'),
            'bucket' => env('BACKUP_BUCKET'),
            'endpoint' => env('BACKUP_ENDPOINT'),
            'use_path_style_endpoint' => env('BACKUP_PATH_STYLE', true),
            'throw' => true,
            'report' => true,
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Symbolic Links
    |--------------------------------------------------------------------------
    |
    | Here you may configure the symbolic links that will be created when the
    | `storage:link` Artisan command is executed. The array keys should be
    | the locations of the links and the values should be their targets.
    |
    */

    'links' => [
        public_path('storage') => storage_path('app/public'),
    ],

];
