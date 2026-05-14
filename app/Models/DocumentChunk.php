<?php

namespace App\Models;

use Database\Factories\DocumentChunkFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Pgvector\Laravel\HasNeighbors;
use Pgvector\Laravel\Vector;

/**
 * @property int $id
 * @property int $document_id
 * @property int $chunk_index
 * @property string $content
 * @property array<array-key, mixed>|null $metadata
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property \Pgvector\Laravel\Vector|null $embedding
 * @property-read \App\Models\Document $document
 * @method static \Database\Factories\DocumentChunkFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DocumentChunk nearestNeighbors(string $column, ?mixed $value, \Pgvector\Laravel\Distance $distance)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DocumentChunk newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DocumentChunk newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DocumentChunk query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DocumentChunk whereChunkIndex($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DocumentChunk whereContent($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DocumentChunk whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DocumentChunk whereDocumentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DocumentChunk whereEmbedding($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DocumentChunk whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DocumentChunk whereMetadata($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DocumentChunk whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DocumentChunk whereIn(string $column, mixed $values, string $boolean = 'and', bool $not = false)
 * @mixin \Eloquent
 */
class DocumentChunk extends Model
{
    /** @use HasFactory<DocumentChunkFactory> */
    use HasFactory, HasNeighbors;

    protected $fillable = [
        'document_id',
        'chunk_index',
        'content',
        'embedding',
        'metadata',
    ];

    protected $hidden = ['embedding'];

    protected $casts = [
        'embedding' => Vector::class,
        'metadata' => 'array',
    ];

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }
}
