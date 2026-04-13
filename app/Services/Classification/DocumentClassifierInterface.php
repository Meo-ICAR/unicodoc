<?php

namespace App\Services\Classification;

use App\Models\Document;

interface DocumentClassifierInterface
{
    /**
     * Tenta di classificare il documento.
     * Ritorna un ClassificationResult, o null se non riesce a classificarlo.
     */
    public function classify(Document $document): ?ClassificationResult;
}
