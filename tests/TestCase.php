<?php

namespace Tests;

use App\Models\Document;
use App\Models\DocumentType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Http;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    /**
     * Configure Http::fake() with the given responses map.
     * Accepts the same format as Http::fake().
     *
     * @param  array<string, mixed>  $responses
     */
    protected function fakeHttp(array $responses = []): void
    {
        Http::fake($responses);
    }

    /**
     * Create a Document model via factory with optional overrides.
     *
     * @param  array<string, mixed>  $overrides
     */
    protected function makeDocument(array $overrides = []): Document
    {
        return Document::factory()->create($overrides);
    }

    /**
     * Create a DocumentType model via factory with optional overrides.
     *
     * @param  array<string, mixed>  $overrides
     */
    protected function makeDocumentType(array $overrides = []): DocumentType
    {
        return DocumentType::factory()->create($overrides);
    }
}
