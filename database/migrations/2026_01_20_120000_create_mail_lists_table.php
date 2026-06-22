<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mail_lists', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('original_filename')->nullable();
            $table->json('headers')->nullable();
            $table->json('rows')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mail_lists');
    }
};

