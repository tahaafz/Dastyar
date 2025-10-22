<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            if (Schema::hasColumn('categories', 'next_slug')) {
                $table->dropColumn('next_slug');
            }

            if (Schema::hasColumn('categories', 'is_final')) {
                $table->dropColumn('is_final');
            }
        });

        if (! Schema::hasColumn('category_states', 'title')) {
            Schema::table('category_states', function (Blueprint $table) {
                $table->string('title')->nullable()->after('title_key');
            });

            DB::table('category_states')
                ->select('id', 'title_key')
                ->orderBy('id')
                ->chunkById(200, function ($rows) {
                    foreach ($rows as $row) {
                        $titleKey = $row->title_key;
                        $resolved = $titleKey && Lang::has($titleKey)
                            ? __($titleKey)
                            : (string) $titleKey;

                        DB::table('category_states')->where('id', $row->id)->update(['title' => $resolved]);
                    }
                });
        }

        Schema::table('category_states', function (Blueprint $table) {
            if (Schema::hasColumn('category_states', 'title_key')) {
                $table->dropColumn('title_key');
            }
        });

        Schema::table('category_states', function (Blueprint $table) {
            if (Schema::hasColumn('category_states', 'sort')) {
                $table->dropColumn('sort');
            }
        });

        Schema::table('category_states', function (Blueprint $table) {
            if (! Schema::hasColumn('category_states', 'sort')) {
                $table->string('sort', 12)->default('beside');
            }
        });
    }

    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            if (! Schema::hasColumn('categories', 'next_slug')) {
                $table->string('next_slug')->nullable();
            }

            if (! Schema::hasColumn('categories', 'is_final')) {
                $table->boolean('is_final')->default(false);
            }
        });

        if (! Schema::hasColumn('category_states', 'title_key')) {
            Schema::table('category_states', function (Blueprint $table) {
                $table->string('title_key')->nullable()->after('title');
            });

            DB::table('category_states')
                ->select('id', 'title')
                ->orderBy('id')
                ->chunkById(200, function ($rows) {
                    foreach ($rows as $row) {
                        DB::table('category_states')->where('id', $row->id)->update(['title_key' => $row->title]);
                    }
                });
        }

        Schema::table('category_states', function (Blueprint $table) {
            if (Schema::hasColumn('category_states', 'title')) {
                $table->dropColumn('title');
            }
        });

        Schema::table('category_states', function (Blueprint $table) {
            if (Schema::hasColumn('category_states', 'sort')) {
                $table->dropColumn('sort');
            }
        });

        Schema::table('category_states', function (Blueprint $table) {
            if (! Schema::hasColumn('category_states', 'sort')) {
                $table->unsignedInteger('sort')->default(0);
            }
        });
    }
};
