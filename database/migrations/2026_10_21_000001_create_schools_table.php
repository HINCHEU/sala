<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('schools', function (Blueprint $table) {
            $table->id();
            $table->string('code', 40)->unique(); // also the subdomain
            $table->string('name_km');
            $table->string('name_en');
            $table->string('type', 20);
            $table->string('stage', 20);
            $table->string('province');
            $table->string('district')->nullable();
            $table->string('commune')->nullable();
            $table->string('moeys_code', 40)->nullable();
            $table->string('brand_color', 7)->nullable();
            $table->string('brand_color_soft', 7)->nullable();
            $table->text('report_header')->nullable();
            $table->json('settings')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('school_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->string('status', 20)->default('active');
            $table->timestamps();
            $table->unique(['school_id', 'user_id']);
        });

        Schema::create('stored_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->nullable()->constrained()->restrictOnDelete();
            $table->nullableMorphs('owner');
            $table->string('collection', 40);
            $table->string('disk', 40);
            $table->string('path');
            $table->string('original_name')->nullable();
            $table->string('mime', 100);
            $table->unsignedBigInteger('size');
            $table->string('checksum', 64);
            $table->timestamps();
            $table->index(['school_id', 'collection']);
        });

        Schema::create('school_invitations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->restrictOnDelete();
            $table->string('email')->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('role', 40);
            $table->string('token_hash', 64)->unique();
            $table->foreignId('invited_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('accepted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('expires_at');
            $table->timestamp('accepted_at')->nullable();
            $table->timestamps();
            $table->index(['school_id', 'accepted_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('school_invitations');
        Schema::dropIfExists('stored_files');
        Schema::dropIfExists('school_user');
        Schema::dropIfExists('schools');
    }
};
