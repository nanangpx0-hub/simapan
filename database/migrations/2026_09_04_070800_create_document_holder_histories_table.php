<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_holder_histories', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('document_id')->constrained('documents')->restrictOnDelete();
            $table->string('from_holder_type', 20)->nullable();
            $table->foreignId('from_work_unit_id')->nullable()->constrained('work_units')->restrictOnDelete();
            $table->foreignId('from_officer_id')->nullable()->constrained('officers')->restrictOnDelete();
            $table->foreignId('from_document_location_id')->nullable()->constrained('document_locations')->restrictOnDelete();
            $table->string('to_holder_type', 20);
            $table->foreignId('to_work_unit_id')->nullable()->constrained('work_units')->restrictOnDelete();
            $table->foreignId('to_officer_id')->nullable()->constrained('officers')->restrictOnDelete();
            $table->foreignId('to_document_location_id')->nullable()->constrained('document_locations')->restrictOnDelete();
            $table->string('condition_before', 30)->nullable();
            $table->string('condition_after', 30);
            $table->string('movement_type', 40);
            $table->string('reference_type', 255)->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->foreignId('moved_by')->constrained('users')->restrictOnDelete();
            $table->dateTime('moved_at');
            $table->text('note')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index(['document_id', 'created_at']);
            $table->index('movement_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_holder_histories');
    }
};
