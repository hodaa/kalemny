<?php

namespace Tests\Feature;

use App\Contracts\MediaProvider;
use App\Models\Call;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\FakeMediaProvider;
use Tests\TestCase;

class CallMediaFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app->singleton(MediaProvider::class, fn (): FakeMediaProvider => new FakeMediaProvider);
    }

    public function test_accept_returns_a_media_join_payload_for_api_clients(): void
    {
        $caller = User::factory()->create();
        $recipient = User::factory()->create();
        $call = Call::factory()->create([
            'caller_id' => $caller->id,
            'recipient_id' => $recipient->id,
        ]);

        $this
            ->actingAs($recipient)
            ->postJson(route('calls.accept', $call))
            ->assertOk()
            ->assertJsonPath('call.id', $call->id)
            ->assertJsonPath('call.status', Call::STATUS_ACCEPTED)
            ->assertJsonPath('media.provider', 'fake')
            ->assertJsonPath('media.app_id', 'fake-app-id')
            ->assertJsonPath('media.channel', $call->channel_name)
            ->assertJsonPath('media.uid', $recipient->id);

        $this->assertDatabaseHas('calls', [
            'id' => $call->id,
            'status' => Call::STATUS_ACCEPTED,
        ]);
    }

    public function test_accept_keeps_the_redirect_response_for_browser_clients(): void
    {
        $caller = User::factory()->create();
        $recipient = User::factory()->create();
        $call = Call::factory()->create([
            'caller_id' => $caller->id,
            'recipient_id' => $recipient->id,
        ]);

        $this
            ->actingAs($recipient)
            ->post(route('calls.accept', $call))
            ->assertRedirect(route('calls.index'));

        $this->assertSame(Call::STATUS_ACCEPTED, $call->fresh()->status);
    }

    public function test_active_returns_null_when_the_user_has_no_active_call(): void
    {
        $user = User::factory()->create();

        $this
            ->actingAs($user)
            ->getJson(route('calls.active'))
            ->assertOk()
            ->assertJsonPath('call', null);
    }

    public function test_active_returns_the_media_payload_once_a_call_is_accepted(): void
    {
        $caller = User::factory()->create();
        $recipient = User::factory()->create();
        $call = Call::factory()->create([
            'caller_id' => $caller->id,
            'recipient_id' => $recipient->id,
            'status' => Call::STATUS_ACCEPTED,
            'answered_at' => now(),
        ]);

        $this
            ->actingAs($caller)
            ->getJson(route('calls.active'))
            ->assertOk()
            ->assertJsonPath('call.id', $call->id)
            ->assertJsonPath('media.provider', 'fake')
            ->assertJsonPath('media.uid', $caller->id);

        $this
            ->actingAs($recipient)
            ->getJson(route('calls.active'))
            ->assertOk()
            ->assertJsonPath('call.id', $call->id)
            ->assertJsonPath('media.uid', $recipient->id);
    }

    public function test_active_returns_no_media_while_a_call_is_still_ringing(): void
    {
        $caller = User::factory()->create();
        $recipient = User::factory()->create();
        $call = Call::factory()->create([
            'caller_id' => $caller->id,
            'recipient_id' => $recipient->id,
        ]);

        $this
            ->actingAs($caller)
            ->getJson(route('calls.active'))
            ->assertOk()
            ->assertJsonPath('call.id', $call->id)
            ->assertJsonPath('media', null);
    }

    public function test_active_only_returns_calls_for_the_requesting_user(): void
    {
        $caller = User::factory()->create();
        $outsider = User::factory()->create();

        Call::factory()->create([
            'caller_id' => $caller->id,
            'status' => Call::STATUS_ACCEPTED,
        ]);

        $this
            ->actingAs($outsider)
            ->getJson(route('calls.active'))
            ->assertOk()
            ->assertJsonPath('call', null);
    }
}
