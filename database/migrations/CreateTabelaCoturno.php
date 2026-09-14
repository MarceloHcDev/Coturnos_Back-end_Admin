<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('coturnos_embalados', function (Blueprint $table) {
            $table->id();
            $table->decimal('peso', 8, 2)->default(1.50);
            $table->decimal('altura', 8, 2)->default(14.00);
            $table->decimal('largura', 8, 2)->default(22.00);
            $table->decimal('comprimento', 8, 2)->default(35.00);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coturnos_embalados');
    }
};