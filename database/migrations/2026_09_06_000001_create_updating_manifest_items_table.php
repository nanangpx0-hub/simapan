<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('updating_manifest_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('document_manifest_id')->constrained('document_manifests')->restrictOnDelete();
            $table->foreignId('allocation_id')->constrained('allocations')->restrictOnDelete();
            $table->string('nks', 32);
            $table->string('kecamatan_code', 64);
            $table->string('desa_code', 64);
            $table->unsignedInteger('household_count_listing')->default(0);
            $table->boolean('has_vsen_p')->default(true);
            $table->boolean('has_peta_ws')->default(true);
            $table->string('physical_condition', 20)->default('GOOD');
            $table->foreignId('processing_officer_id')->nullable()->constrained('officers')->nullOnDelete();
            $table->string('delivery_status', 30)->default('DRAFT');
            $table->text('receive_note')->nullable();
            $table->timestamps();

            $table->unique(['document_manifest_id', 'allocation_id'], 'umi_manifest_allocation_unique');
            $table->index('delivery_status');
            $table->index('processing_officer_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('updating_manifest_items');
    }
};
