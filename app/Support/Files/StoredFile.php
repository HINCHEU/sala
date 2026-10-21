<?php

namespace App\Support\Files;

use App\Domain\School\Models\School;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * A file on one of the media disks. Stores disk + relative path only; URLs are built at read time.
 * Not tenant-scoped: it is always reached through its owner, and school_id is nullable for platform files.
 */
class StoredFile extends Model
{
    protected $fillable = ['school_id', 'collection', 'disk', 'path', 'original_name', 'mime', 'size', 'checksum'];

    /** @return MorphTo<Model, $this> */
    public function owner(): MorphTo
    {
        return $this->morphTo();
    }

    /** @return BelongsTo<School, $this> */
    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function url(): string
    {
        return app(FileStore::class)->url($this);
    }
}
