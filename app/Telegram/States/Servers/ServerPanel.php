<?php

namespace App\Telegram\States\Servers;

use App\DTOs\ServerActionDTO;
use App\Jobs\Telegram\GcoreServerActionJob;
use App\Telegram\UI\Buttons;
use Illuminate\Support\Facades\Http;

class ServerPanel extends \App\Telegram\Core\AbstractState
{
    protected string $gcoreApiKey;
    protected string $gcoreProjectId;
    protected string $gcoreApiBaseV1;

    public function __construct()
    {
        $this->gcoreApiKey    = (string) config('datacenter.gcore.api_key');
        $this->gcoreProjectId = (string) config('datacenter.gcore.project_id', '460993');
        $this->gcoreApiBaseV1 = rtrim((string) config('datacenter.gcore.api_base_v1', 'https://api.gcore.com/cloud/v1'), '/');
    }

    public function onEnter(): void
    {
        $this->goEnum(\App\Enums\Telegram\StateKey::ServersList);
    }

    public function onCallback(string $data, array $u): void
    {
        if (preg_match('~^srv:panel:(\d+)$~', $data, $m)) {
            $this->showPanel((int)$m[1]); return;
        }

        if (preg_match('~^srv:refresh:(\d+)$~', $data, $m)) {
            $this->refreshServer((int)$m[1]); return;
        }

        if (preg_match('~^srv:act:(\d+):(start|stop|delete)$~', $data, $m)) {
            $this->queueAction((int)$m[1], $m[2]); return;
        }

        if ($data === 'nav:list') {
            $this->goEnum(\App\Enums\Telegram\StateKey::ServersList); return;
        }
    }

    protected function showPanel(int $id): void
    {
        $user = $this->process();
        $srv = $user->servers()->whereKey($id)->first();
        if (!$srv) { $this->send(__('telegram.servers.panel.not_found')); return; }

        $txt =
            __('telegram.servers.panel.name', ['name' => $srv->name])."\n".
            __('telegram.servers.panel.provider', ['provider' => strtoupper($srv->provider)])."\n".
            __('telegram.servers.panel.plan', ['plan' => $srv->plan])."\n".
            __('telegram.servers.panel.location', ['location' => $srv->region_id])."\n".
            __('telegram.servers.panel.status', ['status' => $srv->status])."\n".
            __('telegram.servers.panel.ip', ['ip' => ($srv->ip_address ? "<code>{$srv->ip_address}</code>" : '—')]);

        $kb = [
            [
                ['text'=>Buttons::label('servers.panel.refresh'), 'data'=>"srv:refresh:{$srv->id}"],
            ],
            [
                ['text'=>Buttons::label('servers.panel.power_off', '🔌 Power Off'), 'data'=>"srv:act:{$srv->id}:stop"],
                ['text'=>Buttons::label('servers.panel.power_on',  '⚡️ Power On'),  'data'=>"srv:act:{$srv->id}:start"],
            ],
            [
                ['text'=>Buttons::label('servers.panel.delete'), 'data'=>"srv:act:{$srv->id}:delete"],
                ['text'=>Buttons::label('servers.panel.list'), 'data'=>"nav:list"],
            ],
        ];

        $this->send($txt, ['inline_keyboard' => $kb]);
    }

    protected function refreshServer(int $id): void
    {
        $user = $this->process();
        $srv = $user->servers()->whereKey($id)->first();
        if (!$srv || !$srv->external_id) { $this->send(__('telegram.servers.panel.not_enough_info')); return; }

        // خواندن وضعیت از API (سریع؛ بدون صف)
        $listUrl  = "{$this->gcoreApiBaseV1}/instances/{$this->gcoreProjectId}/{$srv->region_id}";

        $r = Http::withHeaders(['Authorization' => "APIKey {$this->gcoreApiKey}"])->get($listUrl);
        if ($r->successful()) {
            $json = $r->json();
            $found = collect($json['results'] ?? [])->firstWhere('id', $srv->external_id);
            if ($found) {
                $srv->status = $found['status'] ?? $srv->status;
                $srv->ip_address = $this->extractIp($found) ?: $srv->ip_address;
                $srv->save();
            }
        }

        $this->showPanel($id);
    }

    protected function queueAction(int $id, string $action): void
    {
        $user = $this->process();
        $srv = $user->servers()->whereKey($id)->first();
        if (!$srv || !$srv->external_id) { $this->send(__('telegram.servers.panel.not_manageable')); return; }


        $dto = new ServerActionDTO(
            user_id: $user->id,
            server_id: $srv->id,
            action: $action
        );
        GcoreServerActionJob::dispatch($dto);

        $human = match($action) {
            'start' => __('telegram.servers.action_name.start'),
            'stop'  => __('telegram.servers.action_name.stop'),
            'delete'=> __('telegram.servers.action_name.delete'),
        };
        $this->send(__('telegram.servers.panel.action_queued', ['action' => $human]));
    }

    protected function extractIp(array $vm): ?string
    {
        $addr = $vm['addresses'] ?? [];
        if (is_array($addr) && isset($addr[0]['address'])) return $addr[0]['address'];
        if (isset($vm['public_ip'])) return $vm['public_ip'];
        return null;
    }
}
