<?php

namespace App\Contracts;

use App\Models\Call;
use App\Models\User;
use App\Support\MediaJoinPayload;

interface MediaProvider
{
    /**
     * The provider identifier used by the client to resolve the matching SDK.
     */
    public function name(): string;

    /**
     * The public app identifier handed to the client SDK.
     */
    public function appId(): string;

    /**
     * The channel name a call maps to within this provider.
     */
    public function channelFor(Call $call): string;

    /**
     * Build a short-lived join payload for a single participant.
     */
    public function joinPayload(User $user, Call $call): MediaJoinPayload;
}
