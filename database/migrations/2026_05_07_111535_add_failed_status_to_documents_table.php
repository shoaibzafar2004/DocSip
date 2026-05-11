<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE documents DROP CONSTRAINT IF EXISTS documents_status_check');
            DB::statement("ALTER TABLE documents ADD CONSTRAINT documents_status_check CHECK (status IN ('uploaded', 'processing', 'ready', 'failed'))");
            DB::statement("ALTER TABLE documents ALTER COLUMN status SET DEFAULT 'uploaded'");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE documents DROP CONSTRAINT IF EXISTS documents_status_check');
            DB::statement("ALTER TABLE documents ADD CONSTRAINT documents_status_check CHECK (status IN ('uploaded', 'processing', 'ready'))");
            DB::statement("ALTER TABLE documents ALTER COLUMN status SET DEFAULT 'uploaded'");
        }
    }
};
