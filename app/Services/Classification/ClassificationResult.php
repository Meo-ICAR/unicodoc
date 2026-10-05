<?php

namespace App\Services\Classification;

readonly class ClassificationResult
{
    public function __construct(
        public ?int $documentTypeId,
        public int $confidenceScore,
        public string $classifierUsed,
        public ?array $evidence = null
    ) {}
}
