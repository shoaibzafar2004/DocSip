<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE documents DROP CONSTRAINT IF EXISTS documents_status_check');
        DB::statement("ALTER TABLE documents ADD CONSTRAINT documents_status_check CHECK (status IN ('uploaded', 'processing', 'ready', 'failed', 'pending_approval'))");

        Schema::table('documents', function (Blueprint $table) {
            $table->float('ocr_confidence')->nullable()->after('status');
            $table->string('extraction_method')->nullable()->after('ocr_confidence');
        });

    }

    public function down(): void
    {
        DB::statement('ALTER TABLE documents DROP CONSTRAINT IF EXISTS documents_status_check');
        DB::statement("ALTER TABLE documents ADD CONSTRAINT documents_status_check CHECK (status IN ('uploaded', 'processing', 'ready', 'failed'))");
        
        Schema::table('documents', function (Blueprint $table) {
            $table->dropColumn('ocr_confidence');
            $table->dropColumn('extraction_method');
        });
    }
};
