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
        Schema::create('processing_entry_reports', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('survey_period_id')->constrained('survey_periods')->restrictOnDelete();
            $table->foreignId('allocation_id')->nullable()->constrained('allocations')->restrictOnDelete();
            $table->foreignId('document_id')->nullable()->constrained('documents')->restrictOnDelete();
            $table->foreignId('document_type_id')->constrained('document_types')->restrictOnDelete();
            $table->string('report_type', 30);
            $table->string('batch_number', 64)->nullable();
            $table->foreignId('officer_id')->nullable()->constrained('officers')->restrictOnDelete();
            $table->timestamp('assigned_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->unsignedInteger('target_qty')->default(0);
            $table->unsignedInteger('processed_qty')->default(0);
            $table->unsignedInteger('clean_qty')->default(0);
            $table->unsignedInteger('error_qty')->default(0);
            $table->string('entry_status', 20)->default('PENDING');
            $table->text('reconciliation_note')->nullable();
            $table->foreignId('parent_entry_report_id')->nullable()->constrained('processing_entry_reports')->restrictOnDelete();
            $table->timestamps();

            $table->unique(['allocation_id', 'report_type']);
            $table->index(['survey_period_id', 'report_type']);
            $table->index('entry_status');
            $table->index('officer_id');
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE `processing_entry_reports` ADD CONSTRAINT `processing_entry_reports_report_type_check` CHECK (`report_type` IN ('PEMUTAKHIRAN_SUSENAS', 'SAMPEL_SUSENAS', 'SAMPEL_SERUTI'))");
            DB::statement("ALTER TABLE `processing_entry_reports` ADD CONSTRAINT `processing_entry_reports_entry_status_check` CHECK (`entry_status` IN ('PENDING', 'IN_PROGRESS', 'COMPLETED', 'RECONCILED', 'DISCREPANCY'))");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('processing_entry_reports');
    }
};
