<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mail_lists', function (Blueprint $table) {
            $table->string('google_sheet_url', 512)->nullable()->after('original_filename');
        });
    }

    public function down(): void
    {
        Schema::table('mail_lists', function (Blueprint $table) {
            $table->dropColumn('google_sheet_url');
        });
    }
};
