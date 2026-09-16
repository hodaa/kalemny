import { onBeforeUnmount, ref, type Ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { createCallProvider } from './providers/registry';
import type { AcceptCallResponse, ActiveCallPayload, ActiveCallResponse, CallProvider, MediaJoinPayload } from './types';

export type CallStatus = 'idle' | 'joining' | 'connected' | 'error';

function csrfToken(): string {
    const meta = document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]');

    if (meta?.content !== '') {
        return meta?.content ?? '';
    }

    const match = document.cookie.match(/(?:^|;\s*)XSRF-TOKEN=([^;]*)/);

    return match ? decodeURIComponent(match[1] ?? '') : '';
}

function errorMessage(caught: unknown): string {
    return caught instanceof Error ? caught.message : 'Something went wrong.';
}

export function useCall() {
    const status = ref<CallStatus>('idle');
    const activeCall = ref<ActiveCallPayload | null>(null);
    const joinedMedia = ref<MediaJoinPayload | null>(null);
    const muted = ref<boolean>(false);
    const error = ref<string | null>(null);
    const elapsedSeconds = ref<number>(0);

    let provider: CallProvider | null = null;
    let elapsedTimer: number | null = null;
    let pollTimer: number | null = null;

    function startElapsedTimer(): void {
        stopElapsedTimer();
        elapsedSeconds.value = 0;
        elapsedTimer = window.setInterval(() => {
            elapsedSeconds.value += 1;
        }, 1000);
    }

    function stopElapsedTimer(): void {
        if (elapsedTimer !== null) {
            window.clearInterval(elapsedTimer);
            elapsedTimer = null;
        }
    }

    async function join(payload: MediaJoinPayload): Promise<void> {
        const nextProvider = await createCallProvider(payload.provider);

        nextProvider.onRemoteUserJoined(() => {
            // Remote audio subscription succeeded; nothing else to drive here.
        });

        nextProvider.onRemoteUserLeft(() => {
            // A peer leaving is surfaced through the server side of the flow.
        });

        status.value = 'joining';
        error.value = null;

        try {
            await nextProvider.join(payload);
            provider = nextProvider;
            joinedMedia.value = payload;
            muted.value = false;
            status.value = 'connected';
            startElapsedTimer();
        } catch (caught) {
            await nextProvider.leave().catch(() => undefined);
            nextProvider.destroy();
            status.value = 'error';
            error.value = errorMessage(caught);
        }
    }

    async function acceptCall(callId: number): Promise<void> {
        status.value = 'joining';
        error.value = null;

        try {
            const response = await fetch(window.route('calls.accept', callId), {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-XSRF-TOKEN': csrfToken(),
                },
            });

            if (!response.ok || !response.body) {
                throw new Error(`Accept failed with status ${response.status}.`);
            }

            const result = (await response.json()) as AcceptCallResponse;
            activeCall.value = result.call;

            await join(result.media);
        } catch (caught) {
            status.value = 'error';
            error.value = errorMessage(caught);
        }
    }

    async function toggleMute(): Promise<void> {
        muted.value = !muted.value;
        await provider?.setMuted(muted.value);
    }

    async function leave(): Promise<void> {
        stopElapsedTimer();
        await provider?.leave().catch(() => undefined);
        provider?.destroy();
        provider = null;
        joinedMedia.value = null;
        status.value = 'idle';
        muted.value = false;

        router.visit(window.route('calls.index'));
    }

    async function refreshActive(): Promise<void> {
        try {
            const response = await fetch(window.route('calls.active'), {
                credentials: 'same-origin',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            if (!response.ok) {
                return;
            }

            const { call, media } = (await response.json()) as ActiveCallResponse;
            activeCall.value = call?.status === 'accepted' ? call : null;

            const shouldJoin =
                call?.status === 'accepted' &&
                media !== null &&
                status.value !== 'connected' &&
                status.value !== 'joining';

            if (shouldJoin && media !== null) {
                await join(media);
            }
        } catch {
            // Transient network hiccup; the next poll retries.
        }
    }

    function startRefreshingActiveCall(intervalMs = 3000): void {
        stopRefreshingActiveCall();
        void refreshActive();
        pollTimer = window.setInterval(() => {
            void refreshActive();
        }, intervalMs);
    }

    function stopRefreshingActiveCall(): void {
        if (pollTimer !== null) {
            window.clearInterval(pollTimer);
            pollTimer = null;
        }
    }

    onBeforeUnmount(() => {
        stopElapsedTimer();
        stopRefreshingActiveCall();
        void provider?.leave().catch(() => undefined);
        provider?.destroy();
        provider = null;
    });

    return {
        status: status as Ref<CallStatus>,
        activeCall: activeCall as Ref<ActiveCallPayload | null>,
        joinedMedia: joinedMedia as Ref<MediaJoinPayload | null>,
        muted: muted as Ref<boolean>,
        error: error as Ref<string | null>,
        elapsedSeconds: elapsedSeconds as Ref<number>,
        acceptCall,
        toggleMute,
        leave,
        startRefreshingActiveCall,
    };
}