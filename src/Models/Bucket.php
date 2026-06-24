<?php

namespace LaraBucket\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Bucket extends Model
{
    protected $table = 'larabucket_buckets';

    protected $fillable = [
        'name',
        'slug',
        'secret_key',
        'owner_email',
        'storage_limit_mb',
        'size_used',
        'is_active',
    ];

    protected $casts = [
        'storage_limit_mb' => 'integer',
        'size_used' => 'integer',
        'is_active' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($bucket) {
            if (empty($bucket->secret_key)) {
                $bucket->secret_key = 'sk_live_' . Str::random(32);
            }
            if (empty($bucket->slug)) {
                $bucket->slug = Str::slug($bucket->name);
            }
        });
    }

    /**
     * Determine if the bucket has reached its storage limit.
     */
    public function storageLimitReached(): bool
    {
        $limitInBytes = $this->storage_limit_mb * 1024 * 1024;
        return $this->size_used >= $limitInBytes;
    }
}
