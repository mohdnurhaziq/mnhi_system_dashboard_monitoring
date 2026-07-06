<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('findings', function (Blueprint $table) {
            // gap | error | idea | ui
            $table->string('category')->default('gap')->after('rule_key');
            // heuristic | llm
            $table->string('source')->default('heuristic')->after('category');

            $table->index(['category', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('findings', function (Blueprint $table) {
            $table->dropIndex(['category', 'status']);
            $table->dropColumn(['category', 'source']);
        });
    }
};
