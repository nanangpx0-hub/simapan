<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('survey_periods', function (Blueprint $table): void {
            // SLA pelaporan 5 dokumen SUSENAS-SERUTI (append-only).
            $table->date('pemutakhiran_submission_deadline')->nullable()->after('end_date');
            $table->date('pemutakhiran_entry_deadline')->nullable()->after('pemutakhiran_submission_deadline');
            $table->date('sampel_submission_deadline')->nullable()->after('pemutakhiran_entry_deadline');
            $table->date('sampel_entry_deadline')->nullable()->after('sampel_submission_deadline');
        });
    }

    public function down(): void
    {
        Schema::table('survey_periods', function (Blueprint $table): void {
            $table->dropColumn([
                'pemutakhiran_submission_deadline',
                'pemutakhiran_entry_deadline',
                'sampel_submission_deadline',
                'sampel_entry_deadline',
            ]);
        });
    }
};
