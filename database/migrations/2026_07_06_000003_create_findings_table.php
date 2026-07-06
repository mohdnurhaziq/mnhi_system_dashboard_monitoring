<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('findings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('rule_key');
            $table->string('severity'); // info | warning | critical
            $table->string('message');
            $table->json('details')->nullable();
            $table->string('status')->default('open'); // open | dismissed
            $table->timestamp('first_detected_at');
            $table->timestamp('last_seen_at');
            $table->timestamps();

            $table->unique(['project_id', 'rule_key']);
            $table->index(['status', 'severity']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('findings');
    }
};
