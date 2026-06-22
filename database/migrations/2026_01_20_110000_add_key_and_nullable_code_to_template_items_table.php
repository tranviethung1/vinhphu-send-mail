<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('template_items', function (Blueprint $table) {
            $table->string('key')->nullable()->after('template_id');
            $table->string('code')->nullable()->change();
            $table->index(['template_id', 'section', 'key']);
        });
    }

    public function down(): void
    {
        Schema::table('template_items', function (Blueprint $table) {
            $table->dropIndex(['template_id', 'section', 'key']);
            $table->dropColumn('key');
            $table->string('code')->nullable(false)->change();
        });
    }
};

