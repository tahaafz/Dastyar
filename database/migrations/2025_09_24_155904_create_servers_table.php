<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('servers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->string('provider')->index();          // gcore
            $table->string('external_id')->nullable();    // id از gcore

            $table->string('plan');                       // g2s-shared-1-1-25
            $table->string('region_id');                  // 116/104/38
            $table->string('os_image_id');                // image id
            $table->string('name');                       // vm name

            $table->string('login_user')->default('ubuntu');
            $table->string('login_pass');

            $table->string('ip_address')->nullable();
            $table->string('status')->default('pending'); // pending|active|failed
            $table->json('raw_response')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('servers');
    }
};
