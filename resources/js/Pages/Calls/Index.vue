<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { onMounted } from 'vue';
import { useCall } from '@/call/useCall';

interface CallRecord {
    id: number;
    counterpart: string | null;
    status: string;
    started_at: string | null;
    duration_seconds: number | null;
    is_caller: boolean;
}

interface Contact {
    id: number;
    name: string;
}

const props = defineProps<{
    calls: CallRecord[];
    contacts: Contact[];
}>();

const form = useForm({
    recipient_id: '',
});

const {
    status,
    activeCall,
    muted,
    error,
    elapsedSeconds,
    acceptCall,
    toggleMute,
    leave,
    startRefreshingActiveCall,
} = useCall();

onMounted(() => {
    startRefreshingActiveCall();
});

function startCall(): void {
    form.post(route('calls.store'));
}

function updateCall(callId: number, action: 'decline' | 'cancel' | 'end'): void {
    router.post(route(`calls.${action}`, callId));
}

function activeCounterpart(): string {
    const call = props.calls.find((candidate) => candidate.id === activeCall.value?.id);

    return call?.counterpart ?? (activeCall.value?.is_caller ? 'the recipient' : 'the caller');
}

function isActiveCall(call: CallRecord): boolean {
    return call.id === activeCall.value?.id;
}

function formattedDate(value: string | null): string {
    return value ? new Date(value).toLocaleString() : '—';
}

function formattedDuration(seconds: number | null): string {
    if (seconds === null) {
        return '—';
    }

    const minutes = Math.floor(seconds / 60);
    const remainingSeconds = seconds % 60;

    return `${minutes}:${remainingSeconds.toString().padStart(2, '0')}`;
}
</script>

<template>
    <Head title="Calls" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Calls
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-4xl space-y-6 sm:px-6 lg:px-8">
                <section
                    v-if="status !== 'idle'"
                    class="overflow-hidden bg-white p-6 shadow-sm sm:rounded-lg"
                >
                    <h3 class="text-lg font-medium text-gray-900">Active call</h3>

                    <p v-if="error" class="mt-1 text-sm text-red-600">
                        {{ error }}
                    </p>

                    <div v-else-if="status === 'joining'" class="mt-2 text-sm text-gray-600">
                        Connecting audio…
                    </div>

                    <div v-else class="mt-2 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <p class="font-medium text-gray-900">
                                On a call with {{ activeCounterpart() }}
                            </p>
                            <p class="mt-1 text-sm text-gray-600">
                                {{ formattedDuration(elapsedSeconds) }}
                            </p>
                        </div>

                        <div class="flex gap-2">
                            <button
                                type="button"
                                class="rounded-md bg-gray-700 px-4 py-2 text-sm font-semibold text-white hover:bg-gray-600"
                                @click="toggleMute"
                            >
                                {{ muted ? 'Unmute' : 'Mute' }}
                            </button>
                            <button
                                type="button"
                                class="rounded-md bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-500"
                                @click="leave"
                            >
                                Leave
                            </button>
                        </div>
                    </div>
                </section>

                <section class="overflow-hidden bg-white p-6 shadow-sm sm:rounded-lg">
                    <h3 class="text-lg font-medium text-gray-900">Start a call</h3>
                    <p class="mt-1 text-sm text-gray-600">
                        Select a registered user to start an app-to-app voice call.
                    </p>

                    <form class="mt-4 flex flex-col gap-3 sm:flex-row" @submit.prevent="startCall">
                        <select
                            v-model="form.recipient_id"
                            class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            required
                        >
                            <option disabled value="">Select a user</option>
                            <option v-for="contact in contacts" :key="contact.id" :value="contact.id">
                                {{ contact.name }}
                            </option>
                        </select>

                        <button
                            class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500 disabled:cursor-not-allowed disabled:opacity-50"
                            :disabled="form.processing || contacts.length === 0 || activeCall !== null"
                            type="submit"
                        >
                            Start call
                        </button>
                    </form>

                    <p v-if="form.errors.recipient_id" class="mt-2 text-sm text-red-600">
                        {{ form.errors.recipient_id }}
                    </p>
                </section>

                <section class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <div class="border-b border-gray-200 px-6 py-4">
                        <h3 class="text-lg font-medium text-gray-900">Call history</h3>
                    </div>

                    <div v-if="calls.length === 0" class="p-6 text-sm text-gray-600">
                        No calls yet. Start your first call above.
                    </div>

                    <ul v-else class="divide-y divide-gray-200">
                        <li v-for="call in calls" :key="call.id" class="flex flex-col gap-3 p-6 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <p class="font-medium text-gray-900">
                                    {{ call.is_caller ? 'Outgoing call to' : 'Incoming call from' }}
                                    {{ call.counterpart }}
                                    <span v-if="isActiveCall(call)" class="ml-2 rounded-full bg-green-100 px-2 py-0.5 text-xs font-semibold text-green-700">
                                        In call
                                    </span>
                                </p>
                                <p class="mt-1 text-sm text-gray-600">
                                    {{ formattedDate(call.started_at) }} · {{ call.status }} · {{ formattedDuration(call.duration_seconds) }}
                                </p>
                            </div>

                            <div class="flex gap-2">
                                <button
                                    v-if="call.status === 'ringing' && !call.is_caller && !isActiveCall(call)"
                                    class="rounded-md bg-green-600 px-3 py-2 text-sm font-semibold text-white hover:bg-green-500 disabled:cursor-not-allowed disabled:opacity-50"
                                    :disabled="status === 'joining'"
                                    @click="acceptCall(call.id)"
                                >
                                    Accept
                                </button>
                                <button v-if="call.status === 'ringing' && !call.is_caller && !isActiveCall(call)" class="rounded-md bg-red-600 px-3 py-2 text-sm font-semibold text-white hover:bg-red-500" @click="updateCall(call.id, 'decline')">
                                    Decline
                                </button>
                                <button v-if="call.status === 'ringing' && call.is_caller && !isActiveCall(call)" class="rounded-md bg-gray-700 px-3 py-2 text-sm font-semibold text-white hover:bg-gray-600" @click="updateCall(call.id, 'cancel')">
                                    Cancel
                                </button>
                                <button v-if="call.status === 'accepted' && !isActiveCall(call)" class="rounded-md bg-red-600 px-3 py-2 text-sm font-semibold text-white hover:bg-red-500" @click="updateCall(call.id, 'end')">
                                    End call
                                </button>
                            </div>
                        </li>
                    </ul>
                </section>
            </div>
        </div>
    </AuthenticatedLayout>
</template>