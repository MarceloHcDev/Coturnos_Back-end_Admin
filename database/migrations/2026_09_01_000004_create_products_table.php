<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Regra de e-commerce: produtos NUNCA são excluídos, apenas inativados (coluna `active`).
     * Por isso não há softDeletes() nesta tabela.
     */
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->string('slug', 191)->unique();
            $table->string('product_type', 60)->index();
            $table->string('brand', 80)->nullable()->index();
            $table->text('description')->nullable();
            $table->decimal('base_price', 10, 2);
            $table->decimal('promotional_price', 10, 2)->nullable();
            $table->boolean('active')->default(true)->index();

            // Dados de embalagem para cálculo de frete (peso em kg, dimensões em cm)
            $table->decimal('weight', 8, 2)->nullable();
            $table->decimal('height', 8, 2)->nullable();
            $table->decimal('width', 8, 2)->nullable();
            $table->decimal('length', 8, 2)->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
