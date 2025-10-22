<?php

namespace App\DTOs;

class ServerActionDTO
{
    public function __construct(
        public int $user_id,
        public int $server_id,
        public string $action
    ) {}
}
