<?php

namespace App\MediaProviders;

use App\Contracts\MediaProvider;
use App\Models\Call;
use App\Models\User;
use App\Support\MediaJoinPayload;
use BoogieFromZk\AgoraToken\RtcTokenBuilder2;

final readonly class AgoraMediaProvider implements MediaProvider
{
    public function __construct(
        private string $appId,
        private string $appCertificate,
        private int $tokenExpiresAfter,
    ) {}

    public function name(): string
    {
        return 'agora';
    }

    public function appId(): string
    {
        return $this->appId;
    }

    public function channelFor(Call $call): string
    {
        return $call->channel_name;
    }

    public function joinPayload(User $user, Call $call): MediaJoinPayload
    {
        return new MediaJoinPayload(
            provider: $this->name(),
            appId: $this->appId,
            channel: $this->channelFor($call),
            token: $this->buildToken($user, $call),
            uid: $user->id,
        );
    }

    private function buildToken(User $user, Call $call): string
    {
        return RtcTokenBuilder2::buildTokenWithUid(
            $this->appId,
            $this->appCertificate,
            $call->channel_name,
            $user->id,
            RtcTokenBuilder2::ROLE_PUBLISHER,
            $this->tokenExpiresAfter,
        );
    }
}
