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
        Schema::create('process_events', function (Blueprint $table) {
            $table->ulid('id')->primary();

            $table->foreignUlid('process_id');
            $table->foreign('process_id')->references('id')->on('processes')->cascadeOnUpdate()->cascadeOnDelete();

            $table->unsignedBigInteger('user_id')->nullable();
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete()->cascadeOnUpdate();

            $table->string('type', 30)->index(); // process-created, signer-added, email-sent, signer-approved, signer-rejected, process-approved, process-canceled
            $table->text('description')->nullable();

            $table->json('metadata')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('process_events');
    }
};
