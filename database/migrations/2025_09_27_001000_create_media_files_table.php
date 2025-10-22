<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('media_files', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();

            $t->nullableMorphs('mediable');

            $t->string('source', 20)->default('telegram');
            $t->string('driver', 20)->default('media');

            $t->string('dir')->nullable();
            $t->string('filename')->nullable();
            $t->string('path')->nullable();
            $t->string('mime')->nullable();
            $t->unsignedBigInteger('size')->nullable();
            $t->unsignedInteger('width')->nullable();
            $t->unsignedInteger('height')->nullable();
            $t->string('hash_sha1', 64)->nullable()->index();

            $t->string('tg_file_id')->nullable()->index();
            $t->string('tg_unique_id')->nullable();
            $t->string('tg_file_path')->nullable();

            $t->string('purpose', 50)->nullable();

            $t->timestamps();
        });

    }

    public function down(): void
    {
        Schema::dropIfExists('media_files');
    }
};
