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
        Schema::create('document_holders', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('document_id')->unique()->constrained('documents')->restrictOnDelete();
            $table->string('holder_type', 20);
            $table->foreignId('work_unit_id')->nullable()->constrained('work_units')->restrictOnDelete();
            $table->foreignId('officer_id')->nullable()->constrained('officers')->restrictOnDelete();
            $table->foreignId('document_location_id')->nullable()->constrained('document_locations')->restrictOnDelete();
            $table->string('condition_code', 30);
            $table->foreignId('assigned_by')->constrained('users')->restrictOnDelete();
            $table->dateTime('assigned_at');
            $table->timestamps();

            $table->index('work_unit_id');
            $table->index('officer_id');
            $table->index('document_location_id');
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE `document_holders` ADD CONSTRAINT `holders_type_xor` CHECK ((`holder_type` = 'WORK_UNIT' AND `work_unit_id` IS NOT NULL AND `officer_id` IS NULL) OR (`holder_type` = 'OFFICER' AND `officer_id` IS NOT NULL AND `work_unit_id` IS NULL))");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('document_holders');
    }
};
