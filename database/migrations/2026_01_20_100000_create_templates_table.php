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
        Schema::create('templates', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Tên template
            $table->text('description')->nullable(); // Mô tả
            $table->string('logo_path')->nullable(); // Đường dẫn logo
            $table->string('company_name')->nullable(); // Tên công ty
            $table->string('document_code')->nullable(); // Mã số (BM05_QT03)
            $table->string('issue_number')->nullable(); // Lần ban hành
            $table->string('title')->default('PHIẾU LƯƠNG'); // Tiêu đề
            $table->boolean('is_active')->default(true); // Template đang sử dụng
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('templates');
    }
};
