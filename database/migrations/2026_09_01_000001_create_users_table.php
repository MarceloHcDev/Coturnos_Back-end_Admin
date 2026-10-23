<?php

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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('first_name', 60);
            $table->string('last_name', 60);
            $table->string('cpf', 255); // Encrypted using AES-256-CBC via APP_KEY (LGPD compliant)
            $table->string('email', 191)->unique();
            $table->string('phone', 255); // Encrypted using AES-256-CBC via APP_KEY (LGPD compliant)
            $table->date('birth_date')->nullable();
            $table->string('password', 255)->nullable(); // Argon2id/Bcrypt hash; nullable for pure social OAuth login
            $table->enum('role', ['customer', 'admin'])->default('customer');
            $table->boolean('active')->default(true);
            $table->rememberToken();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
