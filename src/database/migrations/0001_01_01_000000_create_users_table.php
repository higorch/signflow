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
            $table->ulid()->unique();

            // publicos protegidos
            $table->string('name')->nullable();
            $table->string('name_hash', 64)->nullable()->index();
            $table->text('email')->nullable();
            $table->string('email_hash', 64)->nullable()->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');

            // privados sensiveis
            $table->string('status', 15)->index(); // active, disabled
            $table->text('cpf_cnpj')->nullable();
            $table->string('cpf_cnpj_hash', 64)->unique()->index()->nullable();
            $table->text('role')->nullable(); // root, admin, customer, signer
            $table->string('role_hash', 64)->index();

            // sistema
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email_hash', 64)->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};