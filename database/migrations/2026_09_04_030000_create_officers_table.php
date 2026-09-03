<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('officers', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 32)->unique();
            $table->string('name', 150);
            $table->string('normalized_name', 180)->index();
            $table->foreignId('work_unit_id')->constrained('work_units')->restrictOnDelete();
            $table->foreignId('user_id')->nullable()->unique()->constrained('users')->nullOnDelete();
            $table->string('phone', 30)->nullable();
            $table->string('email', 150)->nullable();
            $table->string('status', 20)->default('ACTIVE');
            $table->date('active_from')->nullable();
            $table->date('active_until')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('work_unit_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('officers');
    }
};
