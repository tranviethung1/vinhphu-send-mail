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
        Schema::create('salary_files', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Tên file/quản lý
            $table->text('description')->nullable(); // Mô tả
            $table->string('file_name'); // Tên file thực tế trên server
            $table->string('file_path'); // Đường dẫn file
            $table->string('file_size')->nullable(); // Kích thước file
            $table->string('month')->nullable(); // Tháng (ví dụ: 2026-01)
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('salary_files');
    }
};
