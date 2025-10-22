<?php

namespace App\Telegram\UI;

use Illuminate\Support\Facades\Lang;

final class Buttons
{
    private static array $defaults = [
        'back'                   => '⬅️ Back',
        'buy'                    => 'Buy VPS',
        'support'                => 'Support',
        'manage'                 => 'Manage servers',
        'management'             => 'Management',
        'topup'                  => 'Add balance',
        'approve'                => '✅ Approve',
        'reject'                 => '❌ Reject',
        'cancel'                 => 'Cancel',
        'reply'                  => '✍️ Reply',
        'channel.join'          => 'Join Channel',
        'channel.check'         => '✅ Joined, Check',
        'servers.manage_button' => '📋 Manage Server',
        'servers.panel.refresh' => '🔄 Refresh',
        'servers.panel.delete'  => '🗑 Delete',
        'servers.panel.list'    => '⬅️ List',
        'servers.panel.power_off' => '🔌 Power Off',
        'servers.panel.power_on'  => '⚡️ Power On',
        'buy.confirm_and_send'  => '✅ Confirm & Submit',
        'buy.back'              => '⬅️ Back',
        'buy.plan1'             => 'Plan 1',
        'buy.plan2'             => 'Plan 2',
        'os.ubuntu20'           => 'Ubuntu 20',
        'os.ubuntu22'           => 'Ubuntu 22',
        'provider.gcore'        => 'GCore',
        'locations.dubai'       => '🇦🇪 Dubai',
        'locations.london'      => '🇬🇧 London',
        'locations.frankfurt'   => '🇩🇪 Frankfurt',
    ];

    public static function label(string $key, ?string $fallback = null): string
    {
        $locale = app()->getLocale();

        $k1 = "telegram.buttons.$key";
        if (Lang::has($k1, $locale)) return __($k1);

        $k2 = "telegram.$key";
        if (Lang::has($k2, $locale)) return __($k2);

        if ($fallback !== null) return $fallback;
        return self::$defaults[$key] ?? $key;
    }
}
