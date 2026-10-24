<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration batch 6 (M5): scores. A row is one filled cell: a score, or an absent / exempt mark.
 * A blank cell has no row, so blank is never confused with 0. Every key restricts deletes: scores
 * hang on an enrollment, and neither an exam nor an enrollment can vanish from under them.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->restrictOnDelete();
            $table->foreignId('exam_id')->constrained()->restrictOnDelete();
            $table->foreignId('enrollment_id')->constrained()->restrictOnDelete();
            $table->foreignId('subject_id')->constrained()->restrictOnDelete();
            $table->decimal('score', 5, 2)->nullable(); // null when absent or exempt
            $table->string('mark', 10)->default('present'); // present | absent | exempt
            $table->foreignId('entered_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['exam_id', 'enrollment_id', 'subject_id']);
            $table->index(['exam_id', 'subject_id']);
            $table->index(['school_id', 'exam_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scores');
    }
};
