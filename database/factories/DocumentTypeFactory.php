<?php

namespace Database\Factories;

use App\Models\DocumentType;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<DocumentType>
 */
class DocumentTypeFactory extends Factory
{
    protected $model = DocumentType::class;

    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'name'                   => ucwords($name),
            'code'                   => strtoupper(Str::slug($name, '_')),
            'slug'                   => Str::slug($name),
            'description'            => fake()->sentence(),
            'min_confidence'         => 70,
            'allow_auto_verification'=> false,
            'regex_pattern'          => null,
            'priority'               => 1,
            'AiPattern'              => null,
            'is_AiAbstract'          => false,
            'is_AiCheck'             => false,
            'duration'               => null,
        ];
    }

    /** Imposta un regex_pattern specifico. */
    public function withRegex(string $pattern): static
    {
        return $this->state(fn (array $attributes) => [
            'regex_pattern' => $pattern,
            'regex'         => $pattern,
        ]);
    }

    /** Abilita la classificazione AI (is_AiCheck + AiPattern). */
    public function withAi(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_AiAbstract' => true,
            'is_AiCheck'    => true,
            'AiPattern'     => 'Detect if this document is a ' . ($attributes['name'] ?? 'document'),
        ]);
    }

    /** Abilita la verifica automatica quando confidence > 95. */
    public function autoVerifiable(): static
    {
        return $this->state(fn (array $attributes) => [
            'allow_auto_verification' => true,
            'min_confidence'          => 70,
        ]);
    }
}
