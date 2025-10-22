<?php

namespace App\Telegram\States\Support;

use App\Enums\Telegram\StateKey;
use App\Models\{Category, CategoryState, User};
use App\Services\Cart\UserCart;
use App\Telegram\Callback\Action;
use App\Telegram\Core\DeclarativeState;
use App\Telegram\UI\InlineMenu;
use App\Telegram\UI\RowBuilder;
use Illuminate\Support\Facades\DB;

abstract class CategoryDrivenState extends DeclarativeState
{
    protected StateKey $stateKey;

    protected string $textKey;

    protected bool $resetCartOnEnter = false;

    protected function screen(): array
    {
        return [];
    }

    protected function routes(): array
    {
        return [];
    }

    protected function enterEffects(User $user): array
    {
        return [];
    }

    protected function sayKey(string $key, ?InlineMenu $menu = null, array $vars = []): void
    {
        $markup = $menu
            ? $menu->toTelegram(fn(string $raw) => $this->pack($raw))
            : null;

        $user = $this->process();

        if ($user->tg_last_message_id) {
            $this->editT($key, $vars, $markup);
            return;
        }

        $this->sendT($key, $vars, $markup);
    }

    public function onEnter(): void
    {
        $user = $this->process();

        if ($this->resetCartOnEnter) {
            UserCart::reset($user);
        }

        $this->renderState($user);
    }

    public function onCallback(string $callbackData, array $u): void
    {
        $parsed = $this->cbParse($callbackData, $u);
        if (!$parsed) {
            return;
        }

        $action = $parsed['action'];

        if ($action === Action::CatalogPick) {
            $id = (int) ($parsed['params']['id'] ?? 0);
            if ($id <= 0) {
                $this->silent();
                return;
            }

            $btn = CategoryState::query()->with('category')->find($id);
            if (!$btn) {
                $this->silent();
                return;
            }

            $expectedSlug = $this->stateKey->categorySlug();
            $actualSlug   = $btn->category?->slug;

            if ($expectedSlug && $actualSlug !== $expectedSlug) {
                $this->renderState($this->process());
                return;
            }

            $this->selectCategoryState($btn);
            return;
        }

        if ($action === Action::NavBack) {
            $target = (string) ($parsed['params']['to'] ?? '');
            $prev   = $this->stateKey->back();
            if ($prev !== null && $target === $prev->value) {
                $this->handleBack($prev);
                return;
            }
        }

        parent::onCallback($callbackData, $u);
    }

    public function onText(string $text, array $u): void
    {
        if ($this->interceptShortcuts($text)) {
            return;
        }

        $payload = trim($text);

        if ($payload === Action::Back->value && ($prev = $this->stateKey->back())) {
            $this->handleBack($prev);
            return;
        }

        $prefix = Action::CatalogPick->value . ':';
        if (str_starts_with($payload, $prefix)) {
            $id  = (int) substr($payload, strlen($prefix));
            $btn = CategoryState::query()->with('category')->find($id);
            if (!$btn) {
                $this->silent();
                return;
            }

            $expectedSlug = $this->stateKey->categorySlug();
            $actualSlug   = $btn->category?->slug;

            if ($expectedSlug && $actualSlug !== $expectedSlug) {
                $this->renderState($this->process());
                return;
            }

            $this->selectCategoryState($btn);
            return;
        }

        $this->silent();
    }

    protected function handleBack(StateKey $prev): void
    {
        $user = $this->process();
        $prevSlug = $prev->categorySlug();

        $data = (array) ($user->tg_data ?? []);
        $pickedId = (int) ($data['choices_ids'][$prevSlug] ?? 0);

        if ($pickedId > 0) {
            if ($btn = CategoryState::query()->find($pickedId)) {
                if ($btn->price > 0) {
                    UserCart::sub($user, (int) $btn->price);
                }
            }
        }

        unset($data['choices'][$prevSlug], $data['choices_ids'][$prevSlug], $data['choices_codes'][$prevSlug]);
        $user->forceFill(['tg_data' => $data])->save();

        $this->goKey($prev->value);
    }

    protected function selectCategoryState(CategoryState $btn): void
    {
        $btn->loadMissing('category');

        DB::transaction(function () use ($btn) {
            $user = $this->process();

            $data = (array) ($user->tg_data ?? []);
            $slug = $btn->category->slug;
            $previousId = (int) ($data['choices_ids'][$slug] ?? 0);

            if ($previousId > 0 && $previousId !== $btn->id) {
                if ($previous = CategoryState::query()->find($previousId)) {
                    if ($previous->price > 0) {
                        UserCart::sub($user, (int) $previous->price);
                    }
                }
            }

            if ($previousId !== $btn->id && $btn->price > 0) {
                UserCart::add($user, (int) $btn->price);
            }

            $data['choices'][$slug]     = (string) $btn->title;
            if (!empty($btn->code)) {
                $data['choices_codes'][$slug] = $btn->code;
            } else {
                unset($data['choices_codes'][$slug]);
            }
            $data['choices_ids'][$slug] = $btn->id;
            $user->forceFill(['tg_data' => $data])->save();
        });

        if ($next = $this->stateKey->next()) {
            $this->goKey($next->value);
        } else {
            $this->goKey(StateKey::BuyConfirm->value);
        }
    }

    protected function renderState(User $user): void
    {
        $slug     = $this->stateKey->categorySlug();
        $category = Category::query()->where('slug', $slug)->firstOrFail();
        $buttons  = $category->states()->get();

        $rows = RowBuilder::build($buttons);
        $menu = InlineMenu::make(...$rows);

        if ($prev = $this->stateKey->back()) {
            $menu->backTo($prev->value, 'telegram.buttons.back');
        }

        $vars = (array) $this->enterEffects($user);

        $this->sayKey($this->textKey, menu: $menu, vars: $vars);
    }
}
