<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration batch 5 and 6 (M4): Teaching assignments, approval assignments, and exams.
 * Teaching assignments define who teaches which subject in each classroom and form the
 * security boundary for score entry. Exams manage the lifecycle from draft to published.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('teaching_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->restrictOnDelete();
            $table->foreignId('academic_year_id')->constrained()->restrictOnDelete();
            $table->foreignId('classroom_id')->constrained()->restrictOnDelete();
            $table->foreignId('subject_id')->constrained()->restrictOnDelete();
            $table->foreignId('teacher_id')->constrained()->restrictOnDelete();
            $table->unsignedTinyInteger('hours_per_week')->default(0);
            $table->timestamps();

            $table->unique(['classroom_id', 'subject_id']);
            $table->index(['school_id', 'teacher_id', 'academic_year_id'], 'teaching_assign_teacher_idx');
            $table->index(['school_id', 'academic_year_id'], 'teaching_assign_school_year_idx');
        });

        Schema::create('approval_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->restrictOnDelete();
            $table->foreignId('academic_year_id')->constrained()->restrictOnDelete();
            $table->foreignId('grade_level_id')->constrained()->restrictOnDelete();
            $table->string('approver_type', 20)->default('homeroom'); // homeroom | role | user
            $table->string('role', 50)->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();

            $table->unique(['school_id', 'academic_year_id', 'grade_level_id'], 'approval_assign_grade_unique');
        });

        Schema::create('exams', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->restrictOnDelete();
            $table->foreignId('academic_year_id')->constrained()->restrictOnDelete();
            $table->foreignId('term_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name', 150);
            $table->string('type', 30); // monthly | semester | final | quiz
            $table->unsignedTinyInteger('month')->nullable(); // 1..12
            $table->date('start_date');
            $table->date('end_date');
            $table->string('status', 20)->default('draft'); // draft | open | approved | locked | published
            $table->text('description')->nullable();
            $table->timestamps();

            $table->index(['school_id', 'academic_year_id', 'status'], 'exams_school_year_status_idx');
            $table->index(['school_id', 'status'], 'exams_school_status_idx');
        });

        Schema::create('exam_classrooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->restrictOnDelete();
            $table->foreignId('exam_id')->constrained()->cascadeOnDelete();
            $table->foreignId('classroom_id')->constrained()->restrictOnDelete();
            $table->timestamps();

            $table->unique(['exam_id', 'classroom_id']);
            $table->index(['school_id', 'classroom_id']);
        });

        Schema::create('exam_subject_status', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->restrictOnDelete();
            $table->foreignId('exam_id')->constrained()->cascadeOnDelete();
            $table->foreignId('classroom_id')->constrained()->restrictOnDelete();
            $table->foreignId('subject_id')->constrained()->restrictOnDelete();
            $table->string('status', 20)->default('draft'); // draft | submitted | approved | returned
            $table->foreignId('submitted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('submitted_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->text('returned_reason')->nullable();
            $table->timestamps();

            $table->unique(['exam_id', 'classroom_id', 'subject_id'], 'exam_subject_status_unique');
            $table->index(['school_id', 'classroom_id', 'status'], 'exam_subj_status_lookup_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_subject_status');
        Schema::dropIfExists('exam_classrooms');
        Schema::dropIfExists('exams');
        Schema::dropIfExists('approval_assignments');
        Schema::dropIfExists('teaching_assignments');
    }
};
