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
        Schema::create('download_progress', function (Blueprint $table) {
            $table->string('job_id')->primary();
            $table->integer('percent')->default(0);
            $table->string('speed')->nullable();
            $table->string('eta')->nullable();
            $table->string('status')->nullable();
            $table->timestamp('updated_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('download_progress');
    }
};
