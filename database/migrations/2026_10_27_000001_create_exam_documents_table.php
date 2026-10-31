<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration batch 7 (M7): the PDFs generated for a class in one exam, one row per kind and results
 * version. The file itself is a StoredFile owned by the row.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exam_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->restrictOnDelete();
            $table->foreignId('exam_id')->constrained()->restrictOnDelete();
            $table->foreignId('classroom_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('version');
            $table->string('kind', 20);
            $table->string('paper', 2)->default('A4');
            $table->string('status', 10);
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['exam_id', 'classroom_id', 'version', 'kind']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_documents');
    }
};
