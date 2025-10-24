<?php

namespace App\Telegram\States\Buy;

use App\Enums\Telegram\StateKey;
use App\Models\CategoryState;
use App\Models\User;
use App\Services\Calculator;
use App\Telegram\States\Support\CategoryDrivenState;

class Review extends CategoryDrivenState
{
    protected StateKey $stateKey = StateKey::BuyReview;
    protected string   $textKey  = 'telegram.buy.review';

    protected function enterEffects(User $user): array
    {
        $sum = (new Calculator())->summarize($user);
        $data = (array) ($user->tg_data ?? []);
        $choiceIds  = (array) ($data['choices_ids'] ?? []);
        $savedTexts = (array) ($data['choices'] ?? []);

        $labels = $this->resolveSelections($choiceIds, [
            'buy.duration' => 'duration',
        ], $savedTexts);

        $labels['link'] = htmlspecialchars(
            (string) ($savedTexts['buy.link'] ?? __('telegram.buy.review_missing')),
            ENT_QUOTES,
            'UTF-8'
        );

        return array_merge($labels, [
            'cart'    => number_format($sum['cart']),
            'balance' => number_format($sum['balance']),
        ]);
    }

    private function resolveSelections(array $choiceIds, array $map, array $savedTexts): array
    {
        $ids = array_values(array_intersect_key($choiceIds, $map));
        $states = empty($ids)
            ? collect()
            : CategoryState::query()->whereIn('id', $ids)->get()->keyBy('id');

        $result = [];
        foreach ($map as $slug => $alias) {
            $state = $states->get($choiceIds[$slug] ?? null);
            if ($state) {
                $result[$alias] = htmlspecialchars((string) $state->title, ENT_QUOTES, 'UTF-8');
                continue;
            }

            $fallback = $savedTexts[$slug] ?? __('telegram.buy.review_missing');
            $result[$alias] = htmlspecialchars((string) $fallback, ENT_QUOTES, 'UTF-8');
        }

        return $result;
    }
}
