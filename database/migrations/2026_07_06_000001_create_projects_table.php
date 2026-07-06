<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('root_path')->unique();
            $table->string('resolved_path')->nullable();
            $table->string('status')->default('included'); // included | excluded | archived
            $table->string('stack')->nullable();
            $table->string('stack_version')->nullable();
            $table->timestamp('last_scanned_at')->nullable();
            $table->timestamp('last_commit_at')->nullable();
            $table->boolean('has_commits')->default(false);
            $table->json('metrics')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('stack');
            $table->index('last_commit_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
