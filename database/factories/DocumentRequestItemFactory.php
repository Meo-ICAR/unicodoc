<?php

namespace Database\Factories;

use App\Models\Document;
use App\Models\DocumentRequest;
use App\Models\DocumentRequestItem;
use App\Models\DocumentType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DocumentRequestItem>
 */
class DocumentRequestItemFactory extends Factory
{
    protected $model = DocumentRequestItem::class;

    public function definition(): array
    {
        return [
            'document_request_id'      => DocumentRequest::factory(),
            'document_type_id'         => DocumentType::factory(),
            'fulfilled_by_document_id' => null,
        ];
    }

    /** Item soddisfatto da un documento specifico. */
    public function fulfilled(Document $doc): static
    {
        return $this->state(fn (array $attributes) => [
            'fulfilled_by_document_id' => $doc->id,
        ]);
    }

    /** Item ancora in attesa (non soddisfatto). */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'fulfilled_by_document_id' => null,
        ]);
    }
}
