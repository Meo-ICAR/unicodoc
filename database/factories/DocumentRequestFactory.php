<?php

namespace Database\Factories;

use App\Models\DocumentRequest;
use App\Models\DocumentRequestItem;
use App\Models\DocumentType;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<DocumentRequest>
 */
class DocumentRequestFactory extends Factory
{
    protected $model = DocumentRequest::class;

    public function definition(): array
    {
        return [
            'id'                  => Str::uuid()->toString(),
            'documentable_type'   => 'App\\Models\\BPM\\Client',
            'documentable_id'     => Str::uuid()->toString(),
            'sender_email'        => fake()->safeEmail(),
            'status'              => 'PENDING',
            'expires_at'          => now()->addDays(7),
            'bpm_task_id'         => null,
            'has_unread_messages' => false,
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status'     => 'PENDING',
            'expires_at' => now()->addDays(7),
        ]);
    }

    public function partial(): static
    {
        return $this->state(fn (array $attributes) => [
            'status'     => 'PARTIAL',
            'expires_at' => now()->addDays(7),
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status'     => 'COMPLETED',
            'expires_at' => now()->addDays(7),
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'status'     => 'EXPIRED',
            'expires_at' => now()->subDays(1),
        ]);
    }

    /** Crea N DocumentRequestItem pendenti associati a questa richiesta. */
    public function withItems(int $count = 1): static
    {
        return $this->afterCreating(function (DocumentRequest $request) use ($count) {
            DocumentRequestItem::factory()
                ->count($count)
                ->create(['document_request_id' => $request->id]);
        });
    }
}
