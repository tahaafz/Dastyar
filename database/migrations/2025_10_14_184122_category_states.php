<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('category_states', function (Blueprint $t) {
            $t->id();
            $t->foreignId('category_id')->constrained('categories')->cascadeOnDelete();
            $t->string('title');
            $t->string('code')->nullable();
            $t->unsignedBigInteger('price')->default(0);
            $t->unsignedInteger('sort')->default(0);
            $t->boolean('active')->default(true);
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::table('', function (Blueprint $table) {
            //
        });
    }
};
