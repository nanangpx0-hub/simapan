<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dsrt_samples', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('allocation_id')->constrained('allocations')->restrictOnDelete();
            $table->string('nus', 32);
            $table->string('nurt', 32);
            $table->string('family_number', 32)->nullable();
            $table->string('building_number', 32)->nullable();
            $table->string('household_number', 32)->nullable();
            $table->string('krt_name', 255);
            $table->text('address')->nullable();
            $table->string('krt_education_code', 32)->nullable();
            $table->string('enumeration_status', 30)->default('PENDING');
            $table->string('contact_person', 150)->nullable();
            $table->string('contact_phone', 30)->nullable();
            $table->text('notes')->nullable();
            $table->string('record_status', 20)->default('DRAFT');
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('verified_at')->nullable();
            $table->foreignId('archived_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('archived_at')->nullable();
            $table->timestamps();

            $table->unique(['allocation_id', 'nus']);
            $table->unique(['allocation_id', 'nurt']);
            $table->index(['allocation_id', 'record_status']);
            $table->index('enumeration_status');
            $table->index('krt_name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dsrt_samples');
    }
};
