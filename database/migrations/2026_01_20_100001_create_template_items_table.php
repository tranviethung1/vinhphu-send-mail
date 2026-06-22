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
        Schema::create('template_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('template_id')->constrained('templates')->onDelete('cascade');
            $table->string('section'); // 'income' hoặc 'deduction'
            $table->string('code'); // Mã số như (1), (2), (3)...
            $table->string('label'); // Text hiển thị như "Lương cơ bản", "Chuyên cần"
            $table->integer('order')->default(0); // Thứ tự hiển thị
            $table->boolean('is_active')->default(true); // Có hiển thị hay không
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('template_items');
    }
};
