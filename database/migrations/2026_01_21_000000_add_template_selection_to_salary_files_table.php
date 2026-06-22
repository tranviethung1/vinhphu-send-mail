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
        Schema::table('salary_files', function (Blueprint $table) {
            $table->foreignId('template_selection_id')
                ->nullable()
                ->after('month')
                ->constrained('template_selections')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('salary_files', function (Blueprint $table) {
            $table->dropForeign(['template_selection_id']);
            $table->dropColumn('template_selection_id');
        });
    }
};
