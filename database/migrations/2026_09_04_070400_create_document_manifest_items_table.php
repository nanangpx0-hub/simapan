<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_manifest_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('document_manifest_id')->constrained('document_manifests')->cascadeOnDelete();
            $table->foreignId('document_id')->constrained('documents')->restrictOnDelete();
            $table->unsignedInteger('qty_sent');
            $table->string('condition_sent', 30);
            $table->text('sent_note')->nullable();
            $table->timestamps();

            $table->unique(['document_manifest_id', 'document_id']);
            $table->index('document_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_manifest_items');
    }
};
