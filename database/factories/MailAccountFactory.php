<?php

namespace Database\Factories;

use App\Models\MailAccount;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MailAccount>
 */
class MailAccountFactory extends Factory
{
    protected $model = MailAccount::class;

    public function definition(): array
    {
        return [
            'name'      => fake()->company(),
            'email'     => fake()->unique()->safeEmail(),
            'protocol'  => 'imap',
            'host'      => 'imap.' . fake()->domainName(),
            'port'      => 993,
            'encryption'=> 'ssl',
            'username'  => fake()->userName(),
            'password'  => fake()->password(),
            'is_active' => true,
        ];
    }
}
