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
        Schema::table('mail_lists', function (Blueprint $table) {
            $table->foreignId('facility_id')
                ->nullable()
                ->after('name')
                ->constrained('facilities')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mail_lists', function (Blueprint $table) {
            $table->dropForeign(['facility_id']);
            $table->dropColumn('facility_id');
        });
    }
};

