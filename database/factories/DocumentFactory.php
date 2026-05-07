<?php

namespace Database\Factories;

use App\Models\Document;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Document>
 */
class DocumentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => fake()->words(3, true).'.pdf',
            'path' => 'documents/'.fake()->uuid().'.pdf',
            'size' => fake()->numberBetween(1024, 10_485_760),
            'mime_type' => 'application/pdf',
            'status' => fake()->randomElement(['uploaded', 'processing', 'ready']),
        ];
    }

    public function uploaded(): static
    {
        return $this->state(['status' => 'uploaded']);
    }

    public function processing(): static
    {
        return $this->state(['status' => 'processing']);
    }

    public function ready(): static
    {
        return $this->state(['status' => 'ready']);
    }
}
