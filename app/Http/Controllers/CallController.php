<?php

namespace App\Http\Controllers;

use App\Contracts\MediaProvider;
use App\Events\CallAccepted;
use App\Http\Requests\StoreCallRequest;
use App\Models\Call;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class CallController extends Controller
{
    public function __construct(private readonly MediaProvider $mediaProvider)
    {
        //
    }

    public function index(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();

        $calls = Call::query()
            ->with(['caller:id,name', 'recipient:id,name'])
            ->where(fn ($query) => $query
                ->where('caller_id', $user->id)
                ->orWhere('recipient_id', $user->id))
            ->latest('started_at')
            ->get()
            ->map(function (Call $call) use ($user): array {
                $counterpart = $call->caller_id === $user->id ? $call->recipient : $call->caller;

                return [
                    'id' => $call->id,
                    'counterpart' => $counterpart?->name,
                    'status' => $call->status,
                    'started_at' => $call->started_at?->toIso8601String(),
                    'duration_seconds' => $call->duration_seconds,
                    'is_caller' => $call->caller_id === $user->id,
                ];
            });

        return Inertia::render('Calls/Index', [
            'calls' => $calls,
            'contacts' => User::query()
                ->whereKeyNot($user->id)
                ->orderBy('name')
                ->get(['id', 'name']),
        ]);
    }

    public function store(StoreCallRequest $request): RedirectResponse
    {
        Gate::authorize('create', Call::class);

        /** @var User $caller */
        $caller = $request->user();
        $recipient = User::query()->findOrFail($request->integer('recipient_id'));

        Call::query()->create([
            'channel_name' => 'call-'.Str::uuid(),
            'caller_id' => $caller->id,
            'recipient_id' => $recipient->id,
            'status' => Call::STATUS_RINGING,
            'started_at' => now(),
        ]);

        return Redirect::route('calls.index')->with('status', 'Call started. Waiting for the recipient.');
    }

    public function accept(Request $request, Call $call): RedirectResponse|JsonResponse
    {
        Gate::authorize('accept', $call);
        $this->ensureStatus($call, Call::STATUS_RINGING);

        $call->update([
            'status' => Call::STATUS_ACCEPTED,
            'answered_at' => now(),
        ]);

        broadcast(new CallAccepted($call));

        if ($request->expectsJson()) {
            return response()->json([
                'call' => $this->presentCall($call, $request->user()),
                'media' => $this->mediaProvider->joinPayload($request->user(), $call)->toArray(),
            ]);
        }

        return Redirect::route('calls.index')->with('status', 'Call accepted.');
    }

    /**
     * Return the current user's active call alongside a media join payload,
     * used to rejoin after a refresh or to learn about an accepted call
     * without a websocket round-trip.
     */
    public function active(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        /** @var Call|null $call */
        $call = Call::query()
            ->where(fn ($query) => $query
                ->where('caller_id', $user->id)
                ->orWhere('recipient_id', $user->id))
            ->whereIn('status', [Call::STATUS_RINGING, Call::STATUS_ACCEPTED])
            ->latest('started_at')
            ->first();

        if ($call === null) {
            return response()->json(['call' => null]);
        }

        return response()->json([
            'call' => $this->presentCall($call, $user),
            'media' => $call->status === Call::STATUS_ACCEPTED
                ? $this->mediaProvider->joinPayload($user, $call)->toArray()
                : null,
        ]);
    }

    public function decline(Request $request, Call $call): RedirectResponse
    {
        Gate::authorize('decline', $call);
        $this->ensureStatus($call, Call::STATUS_RINGING);

        $this->finishCall($call, Call::STATUS_DECLINED, $request->user()->id);

        return Redirect::route('calls.index')->with('status', 'Call declined.');
    }

    public function cancel(Request $request, Call $call): RedirectResponse
    {
        Gate::authorize('cancel', $call);
        $this->ensureStatus($call, Call::STATUS_RINGING);

        $this->finishCall($call, Call::STATUS_CANCELLED, $request->user()->id);

        return Redirect::route('calls.index')->with('status', 'Call cancelled.');
    }

    public function end(Request $request, Call $call): RedirectResponse
    {
        Gate::authorize('end', $call);
        $this->ensureStatus($call, Call::STATUS_ACCEPTED);

        $this->finishCall($call, Call::STATUS_COMPLETED, $request->user()->id);

        return Redirect::route('calls.index')->with('status', 'Call ended.');
    }

    /**
     * @return array{id: int, status: string, channel_name: string, is_caller: bool}
     */
    private function presentCall(Call $call, User $user): array
    {
        return [
            'id' => $call->id,
            'status' => $call->status,
            'channel_name' => $call->channel_name,
            'is_caller' => $call->caller_id === $user->id,
        ];
    }

    private function ensureStatus(Call $call, string $expectedStatus): void
    {
        if ($call->status !== $expectedStatus) {
            throw ValidationException::withMessages([
                'call' => 'This call is no longer available for that action.',
            ]);
        }
    }

    private function finishCall(Call $call, string $status, int $endedByUserId): void
    {
        $endedAt = now();

        $call->update([
            'status' => $status,
            'ended_at' => $endedAt,
            'duration_seconds' => $call->answered_at?->diffInSeconds($endedAt),
            'ended_by_user_id' => $endedByUserId,
        ]);
    }
}
