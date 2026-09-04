<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documents', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('document_type_id')->constrained('document_types')->restrictOnDelete();
            $table->foreignId('allocation_id')->nullable()->constrained('allocations')->restrictOnDelete();
            $table->foreignId('dsrt_sample_id')->nullable()->constrained('dsrt_samples')->restrictOnDelete();
            $table->string('document_number', 64)->nullable();
            $table->string('title', 255);
            $table->string('format', 20);
            $table->unsignedInteger('quantity')->default(1);
            $table->string('status', 30)->default('REGISTERED');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();

            $table->index(['allocation_id', 'status']);
            $table->index(['dsrt_sample_id', 'status']);
            $table->index('document_type_id');
            $table->index('status');
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE `documents` ADD CONSTRAINT `documents_context_xor` CHECK ((`allocation_id` IS NOT NULL AND `dsrt_sample_id` IS NULL) OR (`allocation_id` IS NULL AND `dsrt_sample_id` IS NOT NULL))');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
