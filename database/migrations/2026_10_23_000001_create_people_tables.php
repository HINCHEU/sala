<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration batches 3 and 4 (M3): people, then enrollment. People are soft-deleted and never lose their
 * history; an enrollment (one per student per year) is what later scores and attendance hang on.
 * birth_cert_no, id_poor_level and disability are encrypted, so they are text columns.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('teachers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('staff_code', 30)->nullable();
            $table->string('name_km', 100);
            $table->string('name_en', 100)->nullable();
            $table->string('gender', 10);
            $table->date('dob')->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('email')->nullable();
            $table->string('qualification')->nullable();
            $table->string('employment_type', 20);
            $table->date('hired_at')->nullable();
            $table->string('status', 20)->default('active');
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['school_id', 'staff_code']);
            $table->index(['school_id', 'status']);
        });

        // What a teacher can teach; M4's teaching assignments say what they do teach.
        Schema::create('teacher_subject', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->restrictOnDelete();
            $table->foreignId('teacher_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained()->cascadeOnDelete();
            $table->unique(['teacher_id', 'subject_id']);
            $table->index('school_id');
        });

        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->restrictOnDelete();
            $table->string('student_code', 30);
            $table->text('birth_cert_no')->nullable();
            $table->string('last_name_km', 100);
            $table->string('first_name_km', 100);
            $table->string('last_name_en', 100)->nullable();
            $table->string('first_name_en', 100)->nullable();
            $table->string('gender', 10);
            $table->date('dob')->nullable();
            $table->string('birth_village', 100)->nullable();
            $table->string('birth_commune', 100)->nullable();
            $table->string('birth_district', 100)->nullable();
            $table->string('birth_province', 100)->nullable();
            $table->text('address')->nullable();
            $table->text('id_poor_level')->nullable();
            $table->text('disability')->nullable();
            $table->date('admitted_at')->nullable();
            $table->string('status', 20)->default('active');
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['school_id', 'student_code']);
            $table->index(['school_id', 'status']);
            $table->index(['school_id', 'last_name_km', 'first_name_km']);
        });

        Schema::create('guardians', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name_km', 100);
            $table->string('name_en', 100)->nullable();
            $table->string('gender', 10)->nullable();
            $table->string('occupation', 100)->nullable();
            $table->string('phone', 20)->nullable(); // +855 form; one guardian per phone, so siblings share it
            $table->text('address')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['school_id', 'phone']);
        });

        Schema::create('guardian_student', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->restrictOnDelete();
            $table->foreignId('guardian_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->string('relationship', 20);
            $table->boolean('is_primary_contact')->default(false);
            $table->boolean('can_pickup')->default(true);
            $table->timestamps();
            $table->unique(['guardian_id', 'student_id']);
            $table->index(['school_id', 'student_id']);
        });

        Schema::table('classrooms', function (Blueprint $table) {
            $table->foreignId('homeroom_teacher_id')->nullable()->after('room_id')->constrained('teachers')->nullOnDelete();
        });

        Schema::create('enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->restrictOnDelete();
            $table->foreignId('academic_year_id')->constrained()->restrictOnDelete();
            $table->foreignId('classroom_id')->constrained()->restrictOnDelete();
            $table->foreignId('student_id')->constrained()->restrictOnDelete();
            $table->unsignedSmallInteger('roll_no')->nullable();
            $table->string('status', 20)->default('active');
            $table->date('joined_at')->nullable();
            $table->date('left_at')->nullable();
            $table->timestamps();
            $table->unique(['student_id', 'academic_year_id']);
            $table->index(['school_id', 'classroom_id']);
        });

        // A teacher's login invitation: accepting it links the new user to the teacher record.
        Schema::table('school_invitations', function (Blueprint $table) {
            $table->foreignId('teacher_id')->nullable()->after('role')->constrained()->nullOnDelete();
        });

        Schema::create('student_imports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('academic_year_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('original_name')->nullable();
            $table->string('status', 20);
            $table->json('rows'); // [{line, data, errors}] as checked at preview
            $table->unsignedInteger('row_count');
            $table->unsignedInteger('error_count');
            $table->unsignedInteger('imported_count')->default(0);
            $table->text('failure')->nullable();
            $table->timestamps();
            $table->index(['school_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_imports');
        Schema::table('school_invitations', fn (Blueprint $table) => $table->dropConstrainedForeignId('teacher_id'));
        Schema::dropIfExists('enrollments');
        Schema::table('classrooms', fn (Blueprint $table) => $table->dropConstrainedForeignId('homeroom_teacher_id'));
        foreach (['guardian_student', 'guardians', 'students', 'teacher_subject', 'teachers'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
