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
        Schema::create('process_signers', function (Blueprint $table) {
            $table->ulid('id')->primary();

            $table->unsignedBigInteger('user_id');
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnUpdate()->cascadeOnDelete();

            $table->foreignUlid('process_id');
            $table->foreign('process_id')->references('id')->on('processes')->cascadeOnUpdate()->cascadeOnDelete();

            $table->string('status', 20)->index(); // awaiting-signature, signed, rejected

            $table->timestamp('action_at')->nullable();
            $table->string('action_ip', 45)->nullable();
            $table->text('action_agent')->nullable();

            $table->text('rejection_reason')->nullable();

            $table->unsignedInteger('sort')->nullable()->index();

            $table->timestamps();

            $table->unique(['process_id', 'user_id'], 'process_signers_process_user_unique');
        });

        Schema::create('process_signer_tokens', function (Blueprint $table) {
            $table->ulid('id')->primary();

            $table->foreignUlid('process_signer_id');
            $table->foreign('process_signer_id')->references('id')->on('process_signers')->cascadeOnUpdate()->cascadeOnDelete();

            $table->string('token', 64)->unique();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('used_at')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('process_signer_tokens');
        Schema::dropIfExists('process_signers');
    }
};
