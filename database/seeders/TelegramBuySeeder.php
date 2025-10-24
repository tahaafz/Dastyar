<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Category;
use App\Models\CategoryState;

class TelegramBuySeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $obsoleteSlugs = ['buy.provider', 'buy.plan', 'buy.location', 'buy.os'];
            Category::query()
                ->whereIn('slug', $obsoleteSlugs)
                ->get()
                ->each(function (Category $category) {
                    $category->states()->delete();
                    $category->delete();
                });

            $categories = [
                ['slug' => 'buy.duration', 'title_key' => 'telegram.buy.choose_duration'],
                ['slug' => 'buy.review',   'title_key' => 'telegram.buy.review'],
            ];

            $catId = [];
            foreach ($categories as $c) {
                $cat = Category::updateOrCreate(
                    ['slug' => $c['slug']],
                    ['title_key' => $c['title_key']]
                );
                $catId[$c['slug']] = $cat->id;
            }


            $buttons = [
                'buy.duration' => [
                    ['title' => '۲۴ ساعت', 'code' => 'duration-24h', 'price' => 0, 'sort' => 'beside'],
                    ['title' => '۱۲ ساعت', 'code' => 'duration-12h', 'price' => 0, 'sort' => 'beside'],
                    ['title' => '۶ ساعت',  'code' => 'duration-6h',  'price' => 0, 'sort' => 'below'],
                    ['title' => '۳ ساعت - ۱۵۰۰۰ تومان',  'code' => 'duration-3h',  'price' => 15000, 'sort' => 'below'],
                    ['title' => '۱ ساعت - ۲۵۰۰۰ تومان',  'code' => 'duration-1h',  'price' => 25000, 'sort' => 'below'],
                ],
                'buy.review' => [
                    ['title' => 'تایید سفارش', 'code' => 'review-confirm', 'price' => 0, 'sort' => 'beside'],
                ],
            ];

            foreach ($buttons as $slug => $rows) {
                $categoryId = $catId[$slug] ?? null;
                if (!$categoryId) {
                    continue;
                }
                foreach ($rows as $i => $b) {
                    $match = ['category_id' => $categoryId];
                    if (!empty($b['code'])) {
                        $match['code'] = $b['code'];
                    }

                    CategoryState::updateOrCreate(
                        $match,
                        [
                            'title'     => $b['title'],
                            'price'     => (int) ($b['price'] ?? 0),
                            'sort'      => $b['sort'] ?? ($i === 0 ? 'beside' : 'below'),
                            'active'    => true,
                        ]
                    );
                }
            }
        });
    }
}
