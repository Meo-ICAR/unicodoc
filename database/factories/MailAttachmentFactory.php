<?php

namespace Database\Factories;

use App\Models\MailAttachment;
use App\Models\MailMessage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MailAttachment>
 */
class MailAttachmentFactory extends Factory
{
    protected $model = MailAttachment::class;

    public function definition(): array
    {
        return [
            'mail_message_id' => MailMessage::factory(),
            'filename'        => fake()->word() . '.pdf',
            'mime_type'       => 'application/pdf',
            'size'            => fake()->numberBetween(8192, 1_048_576),
            'is_inline'       => false,
            'content'         => null,
        ];
    }

    /**
     * Allegato valido: PDF, >= 8 KB, non inline.
     * shouldSkip() restituirà false.
     */
    public function valid(): static
    {
        return $this->state(fn (array $attributes) => [
            'size'      => 10240,
            'mime_type' => 'application/pdf',
            'is_inline' => false,
        ]);
    }

    /**
     * Allegato troppo piccolo (< 8 KB).
     * shouldSkip() restituirà true.
     */
    public function tooSmall(): static
    {
        return $this->state(fn (array $attributes) => [
            'size' => 1024,
        ]);
    }

    /**
     * Allegato GIF (MIME blacklistato).
     * shouldSkip() restituirà true.
     */
    public function gif(): static
    {
        return $this->state(fn (array $attributes) => [
            'mime_type' => 'image/gif',
            'size'      => 10240,
        ]);
    }

    /**
     * Allegato inline (es. logo nella firma email).
     * shouldSkip() restituirà true.
     */
    public function inline(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_inline' => true,
            'mime_type' => 'image/png',
            'size'      => 10240,
        ]);
    }
}
