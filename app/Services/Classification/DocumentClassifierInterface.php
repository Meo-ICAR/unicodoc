<?php

namespace App\Services\Classification;

use App\Models\Document;

readonly class ClassificationResult
{
    public function __construct(
        public ?int $documentTypeId,
        public int $confidenceScore,
        public string $classifierUsed,
        public ?array $evidence = null
    ) {}
}

interface DocumentClassifierInterface
{
    /**
     * Tenta di classificare il documento.
     * Ritorna un ClassificationResult, o null se non riesce a classificarlo.
     */
    public function classify(Document $document): ?ClassificationResult;
}
