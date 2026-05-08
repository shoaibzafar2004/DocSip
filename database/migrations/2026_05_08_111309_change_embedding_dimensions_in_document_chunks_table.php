<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement('ALTER TABLE document_chunks DROP COLUMN embedding');
        DB::statement('ALTER TABLE document_chunks ADD COLUMN embedding vector(768)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('ALTER TABLE document_chunks DROP COLUMN embedding');
        DB::statement('ALTER TABLE document_chunks ADD COLUMN embedding vector(1536)');
    }
};
