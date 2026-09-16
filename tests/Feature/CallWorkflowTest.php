<?php

namespace Tests\Feature;

use App\Models\Call;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CallWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_start_a_call_with_another_user(): void
    {
        $caller = User::factory()->create();
        $recipient = User::factory()->create();

        $response = $this
            ->actingAs($caller)
            ->post(route('calls.store'), ['recipient_id' => $recipient->id]);

        $response->assertRedirect(route('calls.index'));

        $this->assertDatabaseHas('calls', [
            'caller_id' => $caller->id,
            'recipient_id' => $recipient->id,
            'status' => Call::STATUS_RINGING,
        ]);
    }

    public function test_user_cannot_start_a_call_with_themselves(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from(route('calls.index'))
            ->post(route('calls.store'), ['recipient_id' => $user->id]);

        $response
            ->assertSessionHasErrors('recipient_id')
            ->assertRedirect(route('calls.index'));
    }

    public function test_only_the_recipient_can_accept_a_ringing_call(): void
    {
        $caller = User::factory()->create();
        $recipient = User::factory()->create();
        $call = Call::factory()->create([
            'caller_id' => $caller->id,
            'recipient_id' => $recipient->id,
        ]);

        $this
            ->actingAs($caller)
            ->post(route('calls.accept', $call))
            ->assertForbidden();

        $this
            ->actingAs($recipient)
            ->post(route('calls.accept', $call))
            ->assertRedirect(route('calls.index'));

        $this->assertSame(Call::STATUS_ACCEPTED, $call->fresh()->status);
    }

    public function test_only_participants_can_view_call_history(): void
    {
        config(['app.asset_url' => 'https://assets.example.test']);

        $participant = User::factory()->create();
        $otherParticipant = User::factory()->create();
        $outsider = User::factory()->create();

        Call::factory()->create([
            'caller_id' => $participant->id,
            'recipient_id' => $otherParticipant->id,
        ]);

        $this
            ->actingAs($participant)
            ->get(route('calls.index'), $this->inertiaHeaders())
            ->assertOk()
            ->assertJsonPath('component', 'Calls/Index')
            ->assertJsonPath('props.calls.0.counterpart', $otherParticipant->name);

        $this
            ->actingAs($outsider)
            ->get(route('calls.index'), $this->inertiaHeaders())
            ->assertOk()
            ->assertJsonPath('props.calls', []);
    }

    /**
     * @return array<string, string>
     */
    private function inertiaHeaders(): array
    {
        return [
            'X-Inertia' => 'true',
            'X-Inertia-Version' => hash('xxh128', 'https://assets.example.test'),
        ];
    }
}
