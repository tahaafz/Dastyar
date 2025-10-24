<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('user_links', function (Blueprint $table) {
            $table->timestamp('last_synced_at')->nullable()->after('expires_at')->index();
        });
    }

    public function down(): void
    {
        Schema::table('user_links', function (Blueprint $table) {
            $table->dropColumn('last_synced_at');
        });
    }
};
