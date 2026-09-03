<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('allocations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('survey_period_id')->constrained('survey_periods')->restrictOnDelete();
            $table->foreignId('village_region_id')->constrained('regions')->restrictOnDelete();
            $table->string('nks', 32);
            $table->string('sls_code', 32)->nullable();
            $table->string('sub_sls_code', 32)->nullable();
            $table->string('sls_name', 255);
            $table->string('status', 20)->default('DRAFT');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();

            $table->unique(['survey_period_id', 'nks']);
            $table->index(['survey_period_id', 'status']);
            $table->index('village_region_id');
            $table->index('nks');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('allocations');
    }
};
