<?php

namespace Database\Factories;

use App\Enums\DocumentStatus;
use App\Enums\SyncStatus;
use App\Models\Document;
use App\Models\DocumentType;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Document>
 */
class DocumentFactory extends Factory
{
    protected $model = Document::class;

    public function definition(): array
    {
        return [
            'id'                => Str::uuid()->toString(),
            'documentable_type' => 'App\\Models\\BPM\\Client',
            'documentable_id'   => Str::uuid()->toString(),
            'document_type_id'  => DocumentType::factory(),
            'name'              => fake()->words(3, true),
            'status'            => DocumentStatus::UPLOADED,
            'sync_status'       => SyncStatus::LOCAL,
            'source_app'        => 'local',
            'metadata'          => [],
            'expires_at'        => null,
        ];
    }

    /** Documento appena caricato (stato default). */
    public function uploaded(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => DocumentStatus::UPLOADED,
        ]);
    }

    /** Documento verificato e approvato. */
    public function verified(): static
    {
        return $this->state(fn (array $attributes) => [
            'status'      => DocumentStatus::VERIFIED,
            'verified_at' => now(),
        ]);
    }

    /** Documento scaduto (expires_at nel passato). */
    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'status'     => DocumentStatus::EXPIRED,
            'expires_at' => now()->subDays(10),
        ]);
    }

    /** Associa un DocumentType specifico. */
    public function withType(DocumentType $type): static
    {
        return $this->state(fn (array $attributes) => [
            'document_type_id' => $type->id,
        ]);
    }
}
