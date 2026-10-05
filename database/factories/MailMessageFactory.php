<?php

namespace Database\Factories;

use App\Models\MailAccount;
use App\Models\MailAttachment;
use App\Models\MailMessage;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<MailMessage>
 */
class MailMessageFactory extends Factory
{
    protected $model = MailMessage::class;

    public function definition(): array
    {
        return [
            'mail_account_id' => MailAccount::factory(),
            'message_id'      => '<' . Str::uuid() . '@mail.example.com>',
            'from_address'    => fake()->safeEmail(),
            'from_name'       => fake()->name(),
            'to_address'      => fake()->safeEmail(),
            'subject'         => fake()->sentence(6),
            'body_text'       => fake()->paragraph(),
            'body_html'       => null,
            'received_at'     => now(),
            'is_processed'    => false,
            'metadata'        => null,
        ];
    }

    /** Imposta un body_text specifico. */
    public function withBody(string $body): static
    {
        return $this->state(fn (array $attributes) => [
            'body_text' => $body,
            'body_html' => '<p>' . e($body) . '</p>',
        ]);
    }

    /** Segna il messaggio come già processato. */
    public function processed(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_processed' => true,
        ]);
    }

    /** Crea N MailAttachment associati a questo messaggio. */
    public function withAttachments(int $count = 1): static
    {
        return $this->afterCreating(function (MailMessage $message) use ($count) {
            MailAttachment::factory()
                ->count($count)
                ->create(['mail_message_id' => $message->id]);
        });
    }
}
