<?php

namespace App\Services;

use App\Models\RequestRegistryAttachment;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SharePointService
{
    public function uploadToArchive(RequestRegistryAttachment $attachment): string
    {
        $registry = $attachment->registry;
        $year = $registry->created_at->format('Y');
        $client = Str::slug($registry->requester_name);

        // Definiamo il path definitivo su SharePoint
        $destinationPath = "Archivio/{$year}/{$client}/{$attachment->file_type}/{$attachment->file_name}";

        // Spostiamo dal disco 'local_temp' al disco 'sharepoint'
        Storage::disk('sharepoint')->writeStream(
            $destinationPath,
            Storage::disk('local_temp')->readStream($attachment->file_path)
        );

        return $destinationPath;
    }

    public function getTemporaryUrl(string $path): string
    {
        // Genera un link pre-firmato di 15 min tramite Graph API
        return Storage::disk('sharepoint')->temporaryUrl($path, now()->addMinutes(15));
    }
}
