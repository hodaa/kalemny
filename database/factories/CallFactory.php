<?php

namespace Database\Factories;

use App\Models\Call;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Call>
 */
class CallFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'channel_name' => 'call-'.Str::uuid(),
            'caller_id' => User::factory(),
            'recipient_id' => User::factory(),
            'status' => Call::STATUS_RINGING,
            'started_at' => now(),
        ];
    }
}
