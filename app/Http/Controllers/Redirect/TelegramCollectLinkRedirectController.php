<?php

namespace App\Http\Controllers\Redirect;

use App\Services\Telegram\DeepLinkService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class TelegramCollectLinkRedirectController
{
    public function __construct(private readonly DeepLinkService $deepLinks)
    {
    }

    public function __invoke(Request $request, string $slug): RedirectResponse
    {
        if ($slug === '') {
            abort(404);
        }

        $link = $this->buildDivarUrl($slug, (string) $request->getQueryString());

        $token = $this->deepLinks->createCollectLinkToken($link);

        $botUsername = ltrim((string) config('telegram.bot_username', 'dastyarwebbot'), '@');

        $target = sprintf('https://t.me/%s?start=%s', $botUsername, $token);

        return redirect()->away($target);
    }

    private function buildDivarUrl(string $slug, ?string $query): string
    {
        $url = 'https://divar.ir/s/' . ltrim($slug, '/');

        if ($query) {
            $url .= '?' . $query;
        }

        return $url;
    }
}
