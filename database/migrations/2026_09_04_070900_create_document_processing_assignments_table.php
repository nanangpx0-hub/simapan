<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_processing_assignments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('document_id')->constrained('documents')->restrictOnDelete();
            $table->foreignId('officer_id')->constrained('officers')->restrictOnDelete();
            $table->foreignId('assigned_by')->constrained('users')->restrictOnDelete();
            $table->dateTime('assigned_at');
            $table->dateTime('returned_at')->nullable();
            $table->string('status', 20)->default('ACTIVE');
            $table->text('note')->nullable();
            $table->timestamps();

            $table->index(['document_id', 'status']);
            $table->index('officer_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_processing_assignments');
    }
};
