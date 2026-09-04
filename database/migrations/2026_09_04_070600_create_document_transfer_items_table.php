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
        Schema::create('document_transfer_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('document_transfer_id')->constrained('document_transfers')->cascadeOnDelete();
            $table->foreignId('document_manifest_item_id')->constrained('document_manifest_items')->restrictOnDelete();
            $table->unsignedInteger('qty_sent');
            $table->unsignedInteger('qty_received')->default(0);
            $table->string('condition_received', 30)->nullable();
            $table->string('receipt_status', 30);
            $table->text('receipt_note')->nullable();
            $table->timestamps();

            $table->index('document_transfer_id');
            $table->index('document_manifest_item_id');
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE `document_transfer_items` ADD CONSTRAINT `transfer_items_qty_range` CHECK (`qty_received` >= 0 AND `qty_received` <= `qty_sent`)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('document_transfer_items');
    }
};
