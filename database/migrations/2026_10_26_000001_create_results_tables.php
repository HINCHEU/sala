<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration batch 6 (M6): results and corrections after a lock. Locking an exam writes its results as
 * a new version; exams.results_version says which one is current, so a recalculation never leaves a
 * half-written sheet visible. A correction after the lock is a request the director decides on.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exams', function (Blueprint $table) {
            $table->unsignedInteger('results_version')->default(0)->after('status'); // 0 = not calculated yet
            $table->timestamp('results_computed_at')->nullable()->after('results_version');
        });

        Schema::create('exam_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->restrictOnDelete();
            $table->foreignId('exam_id')->constrained()->restrictOnDelete();
            $table->foreignId('enrollment_id')->constrained()->restrictOnDelete();
            $table->foreignId('classroom_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('version');
            $table->decimal('total', 7, 2);
            $table->decimal('average', 6, 2);
            $table->unsignedSmallInteger('rank_class')->nullable(); // null: not ranked (absent, by the school's rule)
            $table->unsignedSmallInteger('rank_grade')->nullable();
            $table->string('mention', 20)->nullable();
            $table->boolean('passed');
            $table->unsignedTinyInteger('absent_count')->default(0);
            $table->unsignedTinyInteger('exempt_count')->default(0);
            $table->timestamp('computed_at');

            $table->unique(['exam_id', 'enrollment_id', 'version']);
            $table->index(['exam_id', 'version', 'classroom_id'], 'exam_results_sheet_idx');
            $table->index(['school_id', 'enrollment_id'], 'exam_results_student_idx');
        });

        Schema::create('score_change_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->restrictOnDelete();
            $table->foreignId('exam_id')->constrained()->restrictOnDelete();
            $table->foreignId('enrollment_id')->constrained()->restrictOnDelete();
            $table->foreignId('subject_id')->constrained()->restrictOnDelete();
            $table->string('old_value', 10)->nullable(); // as the sheet showed it: "12.5", "A", "E"
            $table->string('new_value', 10);
            $table->text('reason');
            $table->string('status', 20)->default('pending'); // pending | approved | rejected
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('decided_at')->nullable();
            $table->text('decision_note')->nullable();
            $table->timestamps();

            $table->index(['school_id', 'exam_id', 'status'], 'score_changes_exam_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('score_change_requests');
        Schema::dropIfExists('exam_results');
        Schema::table('exams', fn (Blueprint $table) => $table->dropColumn(['results_version', 'results_computed_at']));
    }
};
