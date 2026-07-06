<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scan_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->json('metrics');
            $table->unsignedInteger('duration_ms')->nullable();
            $table->timestamp('scanned_at');
            $table->timestamps();

            $table->index(['project_id', 'scanned_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scan_snapshots');
    }
};
