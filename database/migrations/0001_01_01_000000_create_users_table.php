<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('telegram_user_id')->nullable()->unique();
            $table->unsignedBigInteger('telegram_chat_id')->nullable()->index();
            $table->string('tg_current_state')->nullable()->index();
            $table->json('tg_data')->nullable();
            $table->unsignedBigInteger('tg_last_message_id')->nullable();
            $table->string('name')->nullable();
            $table->string('username')->nullable();
            $table->string('email')->nullable()->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password')->nullable();
            $table->boolean('is_admin')->default(false)->index();
            $table->boolean('is_blocked')->default(false)->index();
            $table->string('blocked_reason')->nullable();
            $table->unsignedInteger('message_count')->default(0);
            $table->timestamp('last_message_at')->nullable();
            $table->unsignedBigInteger('balance')->default(0);
            $table->unsignedBigInteger('cart_total')->default(0);
            $table->rememberToken();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
