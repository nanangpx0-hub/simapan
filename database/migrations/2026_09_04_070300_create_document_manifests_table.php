<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_manifests', function (Blueprint $table): void {
            $table->id();
            $table->string('manifest_number', 64)->unique();
            $table->foreignId('from_work_unit_id')->constrained('work_units')->restrictOnDelete();
            $table->foreignId('to_work_unit_id')->constrained('work_units')->restrictOnDelete();
            $table->string('status', 30)->default('DRAFT');
            $table->foreignId('submitted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('submitted_at')->nullable();
            $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('received_at')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();

            $table->index('status');
            $table->index('from_work_unit_id');
            $table->index('to_work_unit_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_manifests');
    }
};
