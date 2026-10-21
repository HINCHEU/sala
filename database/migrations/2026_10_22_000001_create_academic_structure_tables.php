<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration batch 2 (M2): academic structure. Every foreign key restricts deletes, so a year, grade,
 * subject or room that is in use cannot vanish under curriculum, classes or (later) scores.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('academic_years', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->restrictOnDelete();
            $table->string('name', 40); // 2026–2027
            $table->date('start_date');
            $table->date('end_date');
            $table->boolean('is_current')->default(false);
            $table->timestamps();
            $table->unique(['school_id', 'name']);
            $table->index(['school_id', 'is_current']);
        });

        Schema::create('terms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->restrictOnDelete();
            $table->foreignId('academic_year_id')->constrained()->restrictOnDelete();
            $table->string('name_km');
            $table->string('name_en');
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->unsignedSmallInteger('sort')->default(0);
            $table->timestamps();
            $table->index(['school_id', 'academic_year_id']);
        });

        Schema::create('grade_levels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->restrictOnDelete();
            $table->unsignedTinyInteger('number'); // 0 = kindergarten
            $table->string('name_km');
            $table->string('name_en');
            $table->string('stage', 20);
            $table->boolean('has_tracks')->default(false);
            $table->timestamps();
            $table->unique(['school_id', 'number']);
        });

        Schema::create('tracks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->restrictOnDelete();
            $table->string('code', 20);
            $table->string('name_km');
            $table->string('name_en');
            $table->timestamps();
            $table->unique(['school_id', 'code']);
        });

        Schema::create('subjects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->restrictOnDelete();
            $table->string('code', 20);
            $table->string('name_km');
            $table->string('name_en');
            $table->string('category', 20);
            $table->timestamps();
            $table->unique(['school_id', 'code']);
        });

        Schema::create('rooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->restrictOnDelete();
            $table->string('name', 60);
            $table->string('building', 60)->nullable();
            $table->unsignedSmallInteger('capacity')->nullable();
            $table->timestamps();
            $table->unique(['school_id', 'name']);
        });

        // Curriculum rows are per year, so changing a max score next year never touches old results.
        Schema::create('curriculum_subjects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->restrictOnDelete();
            $table->foreignId('academic_year_id')->constrained()->restrictOnDelete();
            $table->foreignId('grade_level_id')->constrained()->restrictOnDelete();
            $table->foreignId('track_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('subject_id')->constrained()->restrictOnDelete();
            $table->decimal('max_score', 5, 2);
            $table->decimal('coefficient', 4, 2)->default(1);
            $table->decimal('pass_score', 5, 2);
            $table->unsignedSmallInteger('sort')->default(0);
            $table->timestamps();
            // Named by hand: the generated name passes MySQL's 64-character limit.
            $table->index(['school_id', 'academic_year_id', 'grade_level_id'], 'curriculum_subjects_scope_index');
        });

        Schema::create('grade_divisors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->restrictOnDelete();
            $table->foreignId('academic_year_id')->constrained()->restrictOnDelete();
            $table->foreignId('grade_level_id')->constrained()->restrictOnDelete();
            $table->foreignId('track_id')->nullable()->constrained()->restrictOnDelete();
            $table->decimal('divisor', 6, 2);
            $table->timestamps();
            $table->index(['school_id', 'academic_year_id', 'grade_level_id'], 'grade_divisors_scope_index');
        });

        Schema::create('grading_scales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->restrictOnDelete();
            $table->string('stage', 20);
            $table->json('bands'); // [{min, max, mention, mention_km, mention_en, letter}]
            $table->timestamps();
            $table->unique(['school_id', 'stage']);
        });

        // homeroom_teacher_id is added in M3 with the teachers table.
        Schema::create('classrooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->restrictOnDelete();
            $table->foreignId('academic_year_id')->constrained()->restrictOnDelete();
            $table->foreignId('grade_level_id')->constrained()->restrictOnDelete();
            $table->foreignId('track_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('room_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('name', 20); // 7A
            $table->string('shift', 20);
            $table->unsignedSmallInteger('capacity')->nullable();
            $table->timestamps();
            $table->unique(['school_id', 'academic_year_id', 'name']);
        });
    }

    public function down(): void
    {
        foreach (['classrooms', 'grading_scales', 'grade_divisors', 'curriculum_subjects', 'rooms', 'subjects', 'tracks', 'grade_levels', 'terms', 'academic_years'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
