<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\Conversion\Conversion;
use Spatie\MediaLibrary\MediaCollections\MediaCollection;
use Spatie\MediaLibrary\Support\MediaLibraryProxies;

class Media extends \Spatie\MediaLibrary\MediaLibrary\Media
{
    protected $fillable = [
        'model_type',
        'model_id',
        'uuid',
        'collection_name',
        'name',
        'file_name',
        'mime_type',
        'disk',
        'conversions_disk',
        'size',
        'manipulations',
        'custom_properties',
        'generated_conversions',
        'responsive_images',
        'order_column',
    ];

    protected $casts = [
        'id' => 'integer',
        'model_id' => 'integer',
        'size' => 'integer',
        'order_column' => 'integer',
        'manipulations' => 'array',
        'custom_properties' => 'array',
        'generated_conversions' => 'array',
        'responsive_images' => 'array',
    ];

    public function model(): MorphTo
    {
        return $this->morphTo();
    }

    public function getUrl(string $conversionName = ''): string
    {
        return $this->getUrl($conversionName);
    }

    public function getFullUrl(string $conversionName = ''): string
    {
        return $this->getFullUrl($conversionName);
    }

    public function getTemporaryUrl(\DateTimeInterface $expiration, string $conversionName = ''): string
    {
        return $this->getTemporaryUrl($expiration, $conversionName);
    }

    public function getPath(string $conversionName = ''): string
    {
        return $this->getPath($conversionName);
    }

    public function getExtension(): string
    {
        return pathinfo($this->file_name, PATHINFO_EXTENSION);
    }

    public function getMimeType(): string
    {
        return $this->mime_type;
    }

    public function getSize(): int
    {
        return $this->size;
    }

    public function getHumanReadableSize(): string
    {
        $bytes = $this->size;
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }

    public function isImage(): bool
    {
        return str_starts_with($this->mime_type, 'image/');
    }

    public function isPdf(): bool
    {
        return $this->mime_type === 'application/pdf';
    }

    public function isDocument(): bool
    {
        $documentMimes = [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ];

        return in_array($this->mime_type, $documentMimes);
    }

    public function scopeForModel($query, $modelClass, $modelId = null)
    {
        $query->where('model_type', $modelClass);

        if ($modelId) {
            $query->where('model_id', $modelId);
        }

        return $query;
    }

    public function scopeInCollection($query, string $collectionName)
    {
        return $query->where('collection_name', $collectionName);
    }

    public function scopeOrdered($query, string $direction = 'asc')
    {
        return $query->orderBy('order_column', $direction);
    }
}
