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
        Schema::table('template_selections', function (Blueprint $table) {
            $table->string('type')->default('salary')->after('name')->comment('salary, bonus');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('template_selections', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }
};
