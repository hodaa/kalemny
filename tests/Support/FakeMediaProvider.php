<?php

namespace Tests\Support;

use App\Contracts\MediaProvider;
use App\Models\Call;
use App\Models\User;
use App\Support\MediaJoinPayload;

final class FakeMediaProvider implements MediaProvider
{
    public function name(): string
    {
        return 'fake';
    }

    public function appId(): string
    {
        return 'fake-app-id';
    }

    public function channelFor(Call $call): string
    {
        return $call->channel_name;
    }

    public function joinPayload(User $user, Call $call): MediaJoinPayload
    {
        return new MediaJoinPayload(
            provider: $this->name(),
            appId: $this->appId(),
            channel: $this->channelFor($call),
            token: 'shareable-token-'.$user->id,
            uid: $user->id,
        );
    }
}
