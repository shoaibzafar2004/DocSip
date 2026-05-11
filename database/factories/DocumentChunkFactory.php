<?php

namespace Database\Factories;

use App\Models\Document;
use App\Models\DocumentChunk;
use Illuminate\Database\Eloquent\Factories\Factory;
use Pgvector\Laravel\Vector;

/**
 * @extends Factory<DocumentChunk>
 */
class DocumentChunkFactory extends Factory
{
    public function definition(): array
    {
        return [
            'document_id' => Document::factory(),
            'chunk_index' => fake()->numberBetween(0, 20),
            'content' => fake()->paragraph(),
            'embedding' => new Vector(array_fill(0, 768, 0.1)),
            'metadata' => null,
        ];
    }
}
