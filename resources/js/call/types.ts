export interface MediaJoinPayload {
    provider: string;
    app_id: string;
    channel: string;
    token: string;
    uid: number;
}

export interface ActiveCallPayload {
    id: number;
    status: 'ringing' | 'accepted';
    channel_name: string;
    is_caller: boolean;
}

export interface ActiveCallResponse {
    call: ActiveCallPayload | null;
    media: MediaJoinPayload | null;
}

export interface AcceptCallResponse {
    call: ActiveCallPayload;
    media: MediaJoinPayload;
}

/**
 * Contract every realtime-media adapter implements. The registry maps a
 * provider name (coming from the server media payload) to an implementation,
 * so swapping providers later only means adding another adapter.
 */
export interface CallProvider {
    readonly name: string;

    join(payload: MediaJoinPayload): Promise<void>;

    leave(): Promise<void>;

    setMuted(muted: boolean): Promise<void>;

    onRemoteUserJoined(callback: () => void): void;

    onRemoteUserLeft(callback: () => void): void;

    destroy(): void;
}