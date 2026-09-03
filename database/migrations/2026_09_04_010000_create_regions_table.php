<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('regions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('regions')->restrictOnDelete();
            $table->string('level', 40);
            $table->string('code', 32);
            $table->string('full_code', 128)->unique();
            $table->string('name', 150);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('parent_id');
            $table->index('level');
            $table->index('is_active');
            $table->unique(['parent_id', 'level', 'code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('regions');
    }
};
