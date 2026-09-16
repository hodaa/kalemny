import type { CallProvider } from '../types';

type CallProviderFactory = () => Promise<CallProvider>;

const providers: Record<string, CallProviderFactory> = {
    agora: async () => (await import('./agora')).createAgoraCallProvider(),
};

export async function createCallProvider(name: string): Promise<CallProvider> {
    const factory = providers[name];

    if (factory === undefined) {
        throw new Error(`Unsupported media provider [${name}].`);
    }

    return factory();
}