<?php

namespace App\Support\Files;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * The only class that touches Storage. Paths follow schools/{school_id}/{collection}/{owner_id}/{uuid}.{ext}.
 */
class FileStore
{
    public const PUBLIC_DISK = 'media_public';

    public const PRIVATE_DISK = 'media_private';

    public function put(UploadedFile $file, Model $owner, string $collection, ?int $schoolId, bool $public = false): StoredFile
    {
        return $this->putContents(
            $file->getContent(), $file->extension(), (string) $file->getMimeType(),
            $owner, $collection, $schoolId, $public, $file->getClientOriginalName(),
        );
    }

    public function putContents(
        string $contents,
        string $extension,
        string $mime,
        Model $owner,
        string $collection,
        ?int $schoolId,
        bool $public = false,
        ?string $originalName = null,
    ): StoredFile {
        $disk = $public ? self::PUBLIC_DISK : self::PRIVATE_DISK;
        $path = sprintf(
            '%s/%s/%s/%s.%s',
            $schoolId ? "schools/{$schoolId}" : 'platform',
            $collection,
            $owner->getKey(),
            Str::uuid(),
            $extension,
        );

        Storage::disk($disk)->put($path, $contents, $public ? 'public' : 'private');

        $stored = new StoredFile([
            'school_id' => $schoolId,
            'collection' => $collection,
            'disk' => $disk,
            'path' => $path,
            'original_name' => $originalName,
            'mime' => $mime,
            'size' => strlen($contents),
            'checksum' => hash('sha256', $contents),
        ]);
        $stored->owner()->associate($owner)->save();

        return $stored;
    }

    public function url(StoredFile $file): string
    {
        return $file->disk === self::PUBLIC_DISK
            ? Storage::disk($file->disk)->url($file->path)
            : $this->temporaryUrl($file);
    }

    public function temporaryUrl(StoredFile $file, int $minutes = 10): string
    {
        return Storage::disk($file->disk)->temporaryUrl($file->path, now()->addMinutes($minutes));
    }

    public function delete(StoredFile $file): void
    {
        Storage::disk($file->disk)->delete($file->path);
        $file->delete();
    }
}
