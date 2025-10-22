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
        Schema::create('wallet_transactions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->string('type', 20);
            $t->unsignedBigInteger('amount');
            $t->string('currency', 8)->default('IRT');
            $t->string('source', 50)->nullable();
            $t->unsignedBigInteger('balance_before')->nullable();
            $t->unsignedBigInteger('balance_after')->nullable();
            $t->json('meta')->nullable();
            $t->timestamps();
            $t->index(['user_id','type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wallet_transactions');
    }
};
