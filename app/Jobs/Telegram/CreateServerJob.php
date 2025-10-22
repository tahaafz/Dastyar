<?php

namespace App\Jobs\Telegram;

use App\DTOs\ServerCreateDTO;
use App\Models\Server;
use App\Models\User;
use App\Services\AdminNotifier;
use App\Traits\Telegram\TgApi;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Telegram\Bot\Laravel\Facades\Telegram;

class CreateServerJob implements ShouldQueue
{
    use Dispatchable, Queueable, SerializesModels, TgApi;

    public ServerCreateDTO $dto;
    private string $apiKey;
    private string $projectId;
    private string $apiBaseV1;
    private string $apiBaseV2;

    public function __construct(ServerCreateDTO $dto)
    {
        $this->dto = $dto;
        $this->apiKey     = (string) config('datacenter.gcore.api_key');
        $this->projectId  = (string) config('datacenter.gcore.project_id', '460993');
        $this->apiBaseV1  = rtrim((string) config('datacenter.gcore.api_base_v1', 'https://api.gcore.com/cloud/v1'), '/');
        $this->apiBaseV2  = rtrim((string) config('datacenter.gcore.api_base_v2', 'https://api.gcore.com/cloud/v2'), '/');
    }

    public function handle(): void
    {
        $user = User::find($this->dto->user_id);
        if (!$user || !$user->telegram_chat_id) return;

        $server = Server::create([
            'user_id'     => $user->id,
            'provider'    => $this->dto->provider,
            'plan'        => $this->dto->plan,
            'region_id'   => $this->dto->region_id,
            'os_image_id' => $this->dto->os_image_id,
            'name'        => $this->dto->vm_name,
            'login_user'  => $this->dto->login_user,
            'login_pass'  => $this->dto->login_pass,
            'status'      => 'pending',
        ]);

        $apiUrl = "{$this->apiBaseV2}/instances/{$this->projectId}/{$this->dto->region_id}";

        $payload = [
            'flavor'     => $this->dto->plan,
            'names'      => [$this->dto->vm_name],
            'interfaces' => [['type' => 'external']],
            'user_data'  => base64_encode(
                "#cloud-config\n".
                "password: {$this->dto->login_pass}\n".
                "chpasswd: { expire: False }\n".
                "ssh_pwauth: True\n".
                "power_state:\n  mode: reboot\n  timeout: 30\n  condition: True\n"
            ),
            'volumes'    => [[
                'image_id' => $this->dto->os_image_id,
                'source'   => 'image',
                'metadata' => []
            ]]
        ];

        $resp = Http::withHeaders(['Authorization' => "APIKey {$this->apiKey}"])
            ->post($apiUrl, $payload);

        $server->update(['raw_response' => $resp->json() ?: $resp->body()]);

        if (!$resp->successful()) {
            $server->update(['status' => 'failed']);

            Telegram::sendMessage([
                'chat_id' => $user->telegram_chat_id,
                'parse_mode' => 'HTML',
                'text' => __('telegram.servers.create_unavailable'),
            ]);

            app(AdminNotifier::class)->serverCreationFailed($server, $resp->body(), $payload);

            return;
        }

        [$externalId, $ip] = $this->resolveInstanceInfo(
            regionId:  $this->dto->region_id,
            vmName:    $this->dto->vm_name,
            attempts:  6,
            sleepSec:  5
        );

        $server->update([
            'external_id' => $externalId,
            'ip_address'  => $ip,
            'status'      => $externalId ? 'active' : 'pending',
        ]);

        $this->notifySuccess(
            chatId:   $user->telegram_chat_id,
            server:   $server,
            ip:       $ip
        );
    }

    protected function resolveInstanceInfo(string $regionId, string $vmName, int $attempts = 6, int $sleepSec = 5): array
    {
        $listUrl = "{$this->apiBaseV1}/instances/{$this->projectId}/{$regionId}";

        for ($i = 0; $i < $attempts; $i++) {
            $r = Http::withHeaders(['Authorization' => "APIKey {$this->apiKey}"])->get($listUrl);
            if ($r->successful()) {
                $json = $r->json();
                $results = $json['results'] ?? [];
                foreach ($results as $vm) {
                    if (($vm['name'] ?? '') === $vmName) {
                        $id = $vm['id'] ?? null;
                        $ip = $this->extractIp($vm);
                        if ($id) return [$id, $ip];
                    }
                }
            }
            sleep($sleepSec);
        }
        return [null, null];
    }

    protected function extractIp(array $vm): ?string
    {
        $addr = $vm['addresses'] ?? [];
        if (is_array($addr) && isset($addr[0]['address'])) {
            return $addr[0]['address'];
        }
        if (isset($vm['public_ip'])) return $vm['public_ip'];
        return null;
    }

    protected function notifySuccess(int|string $chatId, Server $server, ?string $ip): void
    {
        $provider = strtoupper($server->provider);
        $location = $this->friendlyRegion($server->region_id);
        $plan     = $this->friendlyPlan($server->plan);
        $ipText   = $ip ? "<code>{$ip}</code>" : __('telegram.servers.ip_pending');

        $this->tgSend(
            $chatId,
            __('telegram.servers.created_message', [
                'provider'    => $provider,
                'name'        => $server->name,
                'plan'        => $plan,
                'location'    => $location,
                'ip'          => $ipText,
                'login_user'  => $server->login_user,
                'login_pass'  => $server->login_pass,
            ]),
            ['inline_keyboard' => [
                [ ['text' => __('telegram.servers.manage_button'), 'callback_data' => "srv:panel:{$server->id}"] ],
            ]]
        );
    }

    protected function notifyFailure(int|string $chatId, string $body, Server $server, array $payload): void
    {
        $this->tgSend($chatId, __('telegram.servers.create_unavailable'));
        app(\App\Services\AdminNotifier::class)->serverCreationFailed($server, $body, $payload);
    }

    protected function friendlyRegion(string $id): string
    {
        $key = "telegram.servers.regions.$id";
        return \Illuminate\Support\Facades\Lang::has($key) ? __($key) : $id;
    }

    protected function friendlyPlan(string $code): string
    {
        $key = "telegram.servers.plans.$code";
        return \Illuminate\Support\Facades\Lang::has($key) ? __($key) : $code;
    }
}
