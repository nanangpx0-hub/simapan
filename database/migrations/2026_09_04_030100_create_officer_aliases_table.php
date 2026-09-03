<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('officer_aliases', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('officer_id')->constrained('officers')->cascadeOnDelete();
            $table->string('alias_name', 150);
            $table->string('normalized_alias', 180);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['officer_id', 'normalized_alias']);
            $table->index('normalized_alias');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('officer_aliases');
    }
};
