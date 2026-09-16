import AgoraRTC, { IAgoraRTCClient, IMicrophoneAudioTrack } from 'agora-rtc-sdk-ng';
import type { CallProvider, MediaJoinPayload } from '../types';

let remoteUserJoinedCallback: (() => void) | null = null;
let remoteUserLeftCallback: (() => void) | null = null;

export function createAgoraCallProvider(): CallProvider {
    let client: IAgoraRTCClient | null = null;
    let localAudioTrack: IMicrophoneAudioTrack | null = null;

    function notifyRemoteJoined(): void {
        remoteUserJoinedCallback?.();
    }

    function notifyRemoteLeft(): void {
        remoteUserLeftCallback?.();
    }

    return {
        name: 'agora',

        async join(payload: MediaJoinPayload): Promise<void> {
            if (client !== null) {
                return;
            }

            client = AgoraRTC.createClient({ mode: 'rtc', codec: 'vp8' });

            client.on('user-published', async (user, mediaType) => {
                if (mediaType !== 'audio') {
                    return;
                }

                await client?.subscribe(user, mediaType);
                user.audioTrack?.play();

                notifyRemoteJoined();
            });

            client.on('user-unpublished', (_user, mediaType) => {
                if (mediaType === 'audio') {
                    notifyRemoteLeft();
                }
            });

            client.on('user-left', () => {
                notifyRemoteLeft();
            });

            localAudioTrack = await AgoraRTC.createMicrophoneAudioTrack();

            await client.join(payload.app_id, payload.channel, payload.token, payload.uid);
            await client.publish([localAudioTrack]);
        },

        async leave(): Promise<void> {
            localAudioTrack?.close();
            localAudioTrack = null;

            if (client !== null) {
                await client.leave();
                client = null;
            }
        },

        async setMuted(muted: boolean): Promise<void> {
            await localAudioTrack?.setMuted(muted);
        },

        onRemoteUserJoined(callback: () => void): void {
            remoteUserJoinedCallback = callback;
        },

        onRemoteUserLeft(callback: () => void): void {
            remoteUserLeftCallback = callback;
        },

        destroy(): void {
            remoteUserJoinedCallback = null;
            remoteUserLeftCallback = null;
        },
    };
}