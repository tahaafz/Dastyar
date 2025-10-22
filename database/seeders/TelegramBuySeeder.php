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
            $categories = [
                ['slug' => 'buy.provider', 'title_key' => 'telegram.buy.choose_provider'],
                ['slug' => 'buy.plan',     'title_key' => 'telegram.buy.choose_plan'],
                ['slug' => 'buy.location', 'title_key' => 'telegram.buy.choose_location'],
                ['slug' => 'buy.os',       'title_key' => 'telegram.buy.choose_os'],
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
                'buy.provider' => [
                    ['title' => 'Alpha', 'code' => 'provider-alpha', 'price' => 0, 'sort' => 'beside'],
                    ['title' => 'Beta',  'code' => 'provider-beta',  'price' => 0, 'sort' => 'beside'],
                    ['title' => 'Gamma', 'code' => 'provider-gamma', 'price' => 0, 'sort' => 'below'],
                ],
                'buy.plan' => [
                    ['title' => 'پلن پایه',   'code' => 'plan-basic', 'price' => 100000, 'sort' => 'beside'],
                    ['title' => 'پلن حرفه‌ای', 'code' => 'plan-pro',   'price' => 200000, 'sort' => 'beside'],
                    ['title' => 'پلن ویژه',    'code' => 'plan-ultra', 'price' => 350000, 'sort' => 'below'],
                ],
                'buy.location' => [
                    ['title' => '🇩🇪 آلمان', 'code' => 'loc-de', 'price' => 0, 'sort' => 'beside'],
                    ['title' => '🇫🇷 فرانسه', 'code' => 'loc-fr', 'price' => 0, 'sort' => 'beside'],
                    ['title' => '🇺🇸 آمریکا', 'code' => 'loc-us', 'price' => 0, 'sort' => 'below'],
                ],
                'buy.os' => [
                    ['title' => 'Android', 'code' => 'os-android', 'price' => 0, 'sort' => 'beside'],
                    ['title' => 'iOS',     'code' => 'os-ios',     'price' => 0, 'sort' => 'beside'],
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
