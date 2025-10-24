<?php

namespace App\Telegram\States\Links;

use App\Enums\Telegram\StateKey;
use App\Models\UserLink;
use App\Telegram\Callback\Action;
use App\Telegram\Core\AbstractState;
use App\Telegram\UI\Btn;
use App\Telegram\UI\InlineMenu;
use App\Telegram\UI\KeyboardFactory;
use App\Telegram\UI\Row;
use Illuminate\Support\Str;

final class ActiveLinks extends AbstractState
{
    public function onEnter(): void
    {
        $this->renderList(resetAnchor: true);
    }

    public function onCallback(string $callbackData, array $update): void
    {
        $parsed = $this->cbParse($callbackData, $update);
        if (!$parsed) {
            return;
        }

        $action = $parsed['action'];
        $params = $parsed['params'] ?? [];

        match ($action) {
            Action::ActiveLinkShow  => $this->showLink((int) ($params['id'] ?? 0)),
            Action::ActiveLinkDelete=> $this->deleteLink((int) ($params['id'] ?? 0)),
            Action::ActiveLinkList  => $this->renderList(),
            Action::NavBack         => $this->handleBack($params),
            default                 => null,
        };
    }

    private function handleBack(array $params): void
    {
        $target = (string) ($params['to'] ?? '');
        if ($target === StateKey::Welcome->value) {
            $this->goKey(StateKey::Welcome->value);
            return;
        }

        $this->renderList();
    }

    private function renderList(bool $resetAnchor = false): void
    {
        $user = $this->process();
        $links = UserLink::query()
            ->where('user_id', $user->id)
            ->active()
            ->orderByDesc('id')
            ->get();

        if ($links->isEmpty()) {
            $this->expireInlineScreen();
            $this->sendT('telegram.links.active.empty', [], KeyboardFactory::replyMain());
            return;
        }

        $rows = [];
        foreach ($links as $link) {
            $label = $this->formatLinkLabel($link->url);
            $rows[] = Row::make(
                Btn::make($label, Action::ActiveLinkShow, ['id' => $link->id])
            );
        }

        $menu = InlineMenu::make(...$rows);
        $menu->backTo(StateKey::Welcome->value, 'telegram.buttons.back_main');

        $this->ensureInlineScreen(
            'telegram.links.active.title',
            $menu->toTelegram(fn(string $raw) => $this->pack($raw)),
            resetAnchor: $resetAnchor
        );
    }

    private function showLink(int $id): void
    {
        if ($id <= 0) {
            $this->renderList();
            return;
        }

        $user = $this->process();
        $link = UserLink::query()
            ->where('id', $id)
            ->where('user_id', $user->id)
            ->active()
            ->first();

        if (!$link) {
            $this->renderList();
            return;
        }

        $menu = InlineMenu::make(
            Row::make(Btn::key('telegram.links.active.delete', Action::ActiveLinkDelete, ['id' => $link->id])),
            Row::make(Btn::key('telegram.links.active.back_to_list', Action::ActiveLinkList))
        );
        $menu->backTo(StateKey::Welcome->value, 'telegram.buttons.back_main');

        $vars = [
            'url'       => htmlspecialchars($link->url, ENT_QUOTES, 'UTF-8'),
            'duration'  => $link->duration ?? __('telegram.links.active.duration_unknown'),
            'last_sync' => $link->last_synced_at
                ? $link->last_synced_at->diffForHumans()
                : __('telegram.links.active.never_synced'),
        ];

        $this->ensureInlineScreen(
            'telegram.links.active.view',
            $menu->toTelegram(fn(string $raw) => $this->pack($raw)),
            vars: $vars
        );
    }

    private function deleteLink(int $id): void
    {
        if ($id <= 0) {
            $this->renderList();
            return;
        }

        $user = $this->process();
        $link = UserLink::query()
            ->where('id', $id)
            ->where('user_id', $user->id)
            ->active()
            ->first();

        if (!$link) {
            $this->renderList();
            return;
        }

        $link->linkResults()->delete();

        $link->forceFill([
            'status'     => UserLink::STATUS_INACTIVE,
            'duration'   => null,
            'active_at'  => null,
            'expires_at' => null,
            'last_synced_at' => null,
        ])->save();

        $this->renderList();
    }

    private function formatLinkLabel(string $url): string
    {
        $clean = Str::of($url)->replace(['https://', 'http://'], '')->trim('/');
        return Str::limit($clean, 48);
    }
}
