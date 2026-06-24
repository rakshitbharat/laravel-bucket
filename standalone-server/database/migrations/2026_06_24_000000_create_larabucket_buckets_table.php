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
        Schema::create('larabucket_buckets', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('secret_key')->unique();
            $table->string('owner_email')->nullable();
            $table->unsignedBigInteger('storage_limit_mb')->default(1000);
            $table->unsignedBigInteger('size_used')->default(0); // in bytes
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('larabucket_buckets');
    }
};
