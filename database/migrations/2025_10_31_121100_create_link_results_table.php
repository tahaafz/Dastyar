<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('link_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_link_id')
                ->constrained('user_links')
                ->cascadeOnDelete();
            $table->string('title');
            $table->string('city')->nullable();
            $table->string('price')->nullable();
            $table->string('link', 512);
            $table->json('payload')->nullable();
            $table->timestamps();

            $table->unique(['user_link_id', 'link']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('link_results');
    }
};
