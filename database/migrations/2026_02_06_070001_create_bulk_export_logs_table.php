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
        Schema::create('bulk_export_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('salary_bulk_export_id')->constrained('salary_bulk_exports')->onDelete('cascade');
            $table->string('status')->index(); // info, success, warning, error
            $table->string('email')->nullable();
            $table->string('name')->nullable();
            $table->text('message');
            $table->json('details')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bulk_export_logs');
    }
};
