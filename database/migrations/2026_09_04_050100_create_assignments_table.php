<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assignments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('allocation_id')->constrained('allocations')->cascadeOnDelete();
            $table->foreignId('officer_id')->constrained('officers')->restrictOnDelete();
            $table->string('assignment_role', 30);
            $table->string('employment_category', 20)->nullable();
            $table->boolean('is_active')->default(true);
            $table->dateTime('started_at');
            $table->dateTime('ended_at')->nullable();
            $table->foreignId('assigned_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();

            $table->index('allocation_id');
            $table->index('officer_id');
            $table->index('assignment_role');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assignments');
    }
};
