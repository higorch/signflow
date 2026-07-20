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
        Schema::create('processes', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->unsignedBigInteger('owner_id');
            $table->foreign('owner_id')->references('id')->on('users')->cascadeOnUpdate()->cascadeOnDelete();
            $table->char('reference', 12)->unique(); // 100.000.000 (100 milhões) de combinações por ano
            $table->text('title');
            $table->string('title_hash', 64)->index();
            $table->text('description')->nullable();
            $table->string('description_hash', 64)->nullable();
            $table->string('status', 25)->index(); // draft, awaiting-approval, approved, failed, canceled
            $table->json('data')->nullable(); // dados como configuraçoes do processo ou qualquer outro dado não relacional
            $table->timestamp('sign_deadline_at')->nullable()->index(); // Prazo máximo para todas as assinaturas
            $table->timestamp('expires_at')->nullable()->index(); // Validade do processo
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('processes');
    }
};
