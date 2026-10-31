<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration batch 8 (M8): whose school each logged event belongs to, so a director reads only their
 * own. Earlier rows stay null: no real school has used the system yet, and they remain in the table.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('activity_log', function (Blueprint $table) {
            $table->foreignId('school_id')->nullable()->after('id')->constrained()->restrictOnDelete();
            $table->index(['school_id', 'id']);
        });
    }

    public function down(): void
    {
        Schema::table('activity_log', function (Blueprint $table) {
            $table->dropForeign(['school_id']);
            $table->dropIndex(['school_id', 'id']);
            $table->dropColumn('school_id');
        });
    }
};
