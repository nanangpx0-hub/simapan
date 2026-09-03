<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('survey_periods', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 32)->unique();
            $table->foreignId('survey_type_id')->constrained('survey_types')->restrictOnDelete();
            $table->string('name', 150);
            $table->string('period_type', 20);
            $table->unsignedTinyInteger('period_number')->nullable();
            $table->unsignedSmallInteger('year');
            $table->date('start_date');
            $table->date('end_date');
            $table->string('status', 20)->default('DRAFT');
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('closed_at')->nullable();
            $table->timestamps();

            $table->index('survey_type_id');
            $table->index('year');
            $table->index('period_type');
            $table->index('period_number');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('survey_periods');
    }
};
