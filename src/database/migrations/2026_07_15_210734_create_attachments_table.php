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
        Schema::create('attachments', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignUlid('parent_attachment_id')->nullable()->constrained('attachments')->cascadeOnUpdate()->cascadeOnDelete();
            $table->ulidMorphs('attachable');
            $table->string('disk', 20)->default('local')->index(); // local, s3
            $table->string('path', 255);
            $table->string('extension', 8);
            $table->unsignedBigInteger('size');
            $table->text('caption')->nullable(); // legenda
            $table->string('taxonomy', 80)->nullable()->index(); // process, etc...
            $table->string('status', 25)->index(); // active, disabled, revision, processing
            $table->unsignedInteger('sort')->nullable()->index();
            $table->timestamps();
            $table->index(['attachable_type', 'attachable_id', 'taxonomy']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attachments');
    }
};
