<?php

namespace Tests\Telegram\Support;

use App\Enums\Telegram\StateKey;
use App\Models\User;
use App\Telegram\Core\Context;
use App\Telegram\Core\Registry;
use Illuminate\Support\Str;
use Telegram\Bot\Laravel\Facades\Telegram;

class Kit
{
    public FakeTelegram $fake;
    public Context $ctx;
    public User $user;

    public function __construct(?User $user = null)
    {
        $this->fake = new FakeTelegram();
        $this->mockFacade();
        $this->user = $user ?: User::factory()->create([
            'telegram_user_id' => 1001,
            'telegram_chat_id' => 1001,
        ]);
        $this->ctx = new Context($this->user, Registry::map());
    }

    private function mockFacade(): void
    {
        Telegram::swap($this->fake);
    }

    public static function make(?User $user = null): self
    {
        return new self($user);
    }

    public function start(string|StateKey $state): self
    {
        $key = $state instanceof StateKey ? $state->value : $state;
        $this->user->tg_current_state = $key;
        $this->user->save();
        $this->ctx->transitionTo($key);
        return $this;
    }

    public function press(string $label): self
    {
        $labels = [$label];
        if (Str::startsWith($label, ':')) {
            $k = Str::after($label, ':');
            $labels[] = __("telegram.buttons.$k");
            $labels[] = __("telegram.$k");
        }

        if ($this->clickInline($labels)) return $this;
        if ($this->clickReply($labels)) return $this;

        $this->deliverTextUpdate($label);
        return $this;
    }

    public function expectState(string|StateKey $state): self
    {
        $key = $state instanceof StateKey ? $state->value : $state;
        $actual = $this->user->fresh()->tg_current_state;
        if ($actual !== $key) {
            throw new \PHPUnit\Framework\ExpectationFailedException("Expected state [$key], got [$actual]");
        }
        return $this;
    }

    public function expectText(string $expected): self
    {
        $last = $this->fake->lastText() ?? '';
        $exp = __($expected);
        if (!Str::contains($last, $exp) && !Str::contains($last, $expected)) {
            throw new \PHPUnit\Framework\ExpectationFailedException("Expected text to contain [$expected], got [$last]");
        }
        return $this;
    }

    public function expectAnyMessage(): self
    {
        $has = !empty($this->fake->messages) || !empty($this->fake->edits);
        if (!$has) {
            throw new \PHPUnit\Framework\ExpectationFailedException("Expected bot to produce at least one message or edit.");
        }
        return $this;
    }

    private function clickInline(array $labels): bool
    {
        $kb = $this->fake->lastInlineKeyboard ?? [];
        foreach ($kb as $row) {
            foreach ($row as $btn) {
                $text = $btn['text'] ?? '';
                if ($this->labelMatches($text, $labels)) {
                    $data = $btn['callback_data'] ?? null;
                    if ($data) {
                        $this->deliverCallbackUpdate($data);
                        return true;
                    }
                }
            }
        }
        return false;
    }

    private function clickReply(array $labels): bool
    {
        $kb = $this->fake->lastReplyKeyboard ?? [];
        foreach ($kb as $row) {
            foreach ($row as $btnText) {
                if ($this->labelMatches($btnText, $labels)) {
                    $this->deliverTextUpdate($btnText);
                    return true;
                }
            }
        }
        return false;
    }

    private function labelMatches(string $candidate, array $labels): bool
    {
        foreach ($labels as $l) {
            if ($candidate === $l) return true;
        }
        return false;
    }

    private function deliverTextUpdate(string $text): void
    {
        $update = [
            'update_id' => 1,
            'message' => [
                'message_id' => 1,
                'from' => ['id' => $this->user->telegram_user_id],
                'chat' => ['id' => $this->user->telegram_chat_id],
                'text' => $text,
            ],
        ];
        $this->ctx->getState()->handle($update);
    }

    private function deliverCallbackUpdate(string $data): void
    {
        $update = [
            'update_id' => 2,
            'callback_query' => [
                'id' => 'abc',
                'from' => ['id' => $this->user->telegram_user_id],
                'message' => [
                    'message_id' => 1,
                    'chat' => ['id' => $this->user->telegram_chat_id],
                ],
                'data' => $data,
            ],
        ];
        $this->ctx->getState()->handle($update);
    }
}
