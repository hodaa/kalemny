<?php

namespace App\Support;

use Illuminate\Contracts\Support\Arrayable;

final readonly class MediaJoinPayload implements Arrayable
{
    public function __construct(
        public string $provider,
        public string $appId,
        public string $channel,
        public string $token,
        public int $uid,
    ) {}

    /**
     * @return array{provider: string, app_id: string, channel: string, token: string, uid: int}
     */
    public function toArray(): array
    {
        return [
            'provider' => $this->provider,
            'app_id' => $this->appId,
            'channel' => $this->channel,
            'token' => $this->token,
            'uid' => $this->uid,
        ];
    }
}
