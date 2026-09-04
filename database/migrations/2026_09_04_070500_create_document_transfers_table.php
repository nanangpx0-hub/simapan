<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_transfers', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('document_manifest_id')->unique()->constrained('document_manifests')->restrictOnDelete();
            $table->string('transfer_status', 30)->default('PENDING');
            $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('received_at')->nullable();
            $table->foreignId('checked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('checked_at')->nullable();
            $table->string('receipt_result', 30)->nullable();
            $table->text('receipt_note')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();

            $table->index('transfer_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_transfers');
    }
};
