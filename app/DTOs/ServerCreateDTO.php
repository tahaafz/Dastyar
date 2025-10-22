<?php

namespace App\DTOs;

class ServerCreateDTO
{
    public function __construct(
        public int    $user_id,
        public string $provider,
        public string $plan,
        public string $region_id,
        public string $os_image_id,
        public string $vm_name,
        public string $login_user,
        public string $login_pass
    ) {}

    public static function fromArray(array $a): self
    {
        return new self(
            user_id:      $a['user_id'],
            provider:     $a['provider'],
            plan:         $a['plan'],
            region_id:    $a['region_id'],
            os_image_id:  $a['os_image_id'],
            vm_name:      $a['vm_name'],
            login_user:   $a['login_user'],
            login_pass:   $a['login_pass'],
        );
    }

    public function toArray(): array
    {
        return [
            'user_id'      => $this->user_id,
            'provider'     => $this->provider,
            'plan'         => $this->plan,
            'region_id'    => $this->region_id,
            'os_image_id'  => $this->os_image_id,
            'vm_name'      => $this->vm_name,
            'login_user'   => $this->login_user,
            'login_pass'   => $this->login_pass,
        ];
    }
}
