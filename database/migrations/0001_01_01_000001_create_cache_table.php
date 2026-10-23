<?php

/**
 * ============================================================================
 * INFRAESTRUTURA LARAVEL (TEMPORÁRIO - BANCO DE DADOS LOCAL)
 * ============================================================================
 * Esta migration atende ao driver local de Cache (CACHE_STORE=database) para
 * desenvolvimento e testes locais enquanto o Redis não está ativo.
 * Na Semana 3 da Sprint, o Ryan Rangel ativará o container Redis no Docker Sail,
 * e o cache passará a rodar em memória (CACHE_STORE=redis).
 * ============================================================================
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('cache', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->mediumText('value');
            $table->integer('expiration');
        });

        Schema::create('cache_locks', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->string('owner');
            $table->integer('expiration');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cache');
        Schema::dropIfExists('cache_locks');
    }
};
