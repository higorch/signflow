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
        Schema::create('customer_users', function (Blueprint $table) {
            $table->ulid('id')->primary();

            $table->unsignedBigInteger('customer_id'); // o Customer (dono da conta)
            $table->foreign('customer_id')->references('id')->on('users')->cascadeOnUpdate()->cascadeOnDelete();

            $table->unsignedBigInteger('user_id'); // o usuário interno vinculado
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnUpdate()->cascadeOnDelete();

            $table->timestamps();

            $table->unique(['customer_id', 'user_id'], 'customer_users_customer_user_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_users');
    }
};
