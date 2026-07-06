<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('findings', function (Blueprint $table) {
            // Set when a previously-open finding is no longer detected on rescan
            // (i.e. the underlying issue was fixed). Status becomes 'resolved'.
            $table->timestamp('resolved_at')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('findings', function (Blueprint $table) {
            $table->dropColumn('resolved_at');
        });
    }
};
