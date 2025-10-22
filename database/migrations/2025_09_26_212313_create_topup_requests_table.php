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
        Schema::create('topup_requests', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->string('method', 50);
            $t->unsignedBigInteger('amount');
            $t->string('currency', 8)->default('IRT');
            $t->string('status', 20)->default('pending');
            $t->string('receipt_file_id')->nullable();
            $t->string('receipt_note')->nullable();
            $t->foreignId('receipt_media_id')->nullable();
            $t->foreignId('admin_id')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamp('approved_at')->nullable();
            $t->timestamps();
            $t->index(['status','method']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('topup_requests');
    }
};
