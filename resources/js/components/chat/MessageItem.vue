<template>
    <div :class="['mb-3', isRight ? 'flex justify-end' : 'flex justify-start']">
        <div v-if="isRight" class="flex flex-col items-end gap-2">
            <div class="text-color-white text-sm">
                {{
                    isRight
                        ? currentUserName || 'You'
                        : message.user?.name || message.user_name
                }}
            </div>
            <div class="flex items-end gap-2">
                <div class="relative">
                    <MessageBubble :message="message" />
                    <button
                        type="button"
                        :class="[
                            'important-toggle absolute -top-2 -right-2 rounded-full border p-1 transition-colors duration-150',
                            message._important || message.important
                                ? 'border-yellow-400 bg-yellow-400 text-white'
                                : 'border-gray-200 bg-white text-gray-600 hover:bg-gray-50',
                        ]"
                        :title="
                            message._important || message.important
                                ? 'Remover importante'
                                : 'Marcar como importante'
                        "
                        :aria-pressed="message._important || message.important"
                        @click.stop.prevent="toggleImportant"
                    >
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            viewBox="0 0 24 24"
                            class="h-4 w-4"
                            :aria-hidden="true"
                        >
                            <path
                                v-if="message._important || message.important"
                                d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"
                                fill="currentColor"
                            />
                            <path
                                v-else
                                d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"
                                stroke="currentColor"
                                stroke-width="1"
                                fill="none"
                                opacity="0.9"
                            />
                        </svg>
                    </button>
                </div>
                <Avatar
                    :src="message.user?.avatar"
                    :alt="message.user?.name || 'Avatar'"
                    :size="32"
                />
            </div>
        </div>
        <div v-else class="flex items-start gap-2">
            <Avatar
                :src="message.user?.avatar"
                :alt="message.user?.name || 'Avatar'"
                :size="32"
            />
            <div>
                <div class="text-color-white text-sm">
                    {{ message.user?.name || message.user_name }}
                </div>
                <div class="relative">
                    <MessageBubble :message="message" />
                    <button
                        type="button"
                        :class="[
                            'important-toggle absolute -top-2 -right-2 rounded-full border p-1 transition-colors duration-150',
                            message._important || message.important
                                ? 'border-yellow-400 bg-yellow-400 text-white'
                                : 'border-gray-200 bg-white text-gray-600 hover:bg-gray-50',
                        ]"
                        :title="
                            message._important || message.important
                                ? 'Remover importante'
                                : 'Marcar como importante'
                        "
                        :aria-pressed="message._important || message.important"
                        @click.stop.prevent="toggleImportant"
                    >
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            viewBox="0 0 24 24"
                            class="h-4 w-4"
                            :aria-hidden="true"
                        >
                            <path
                                v-if="message._important || message.important"
                                d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"
                                fill="currentColor"
                            />
                            <path
                                v-else
                                d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"
                                stroke="currentColor"
                                stroke-width="1"
                                fill="none"
                                opacity="0.9"
                            />
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import Avatar from './Avatar.vue';
import MessageBubble from './MessageBubble.vue';

const props = defineProps<{
    message: any;
    currentUserId: string;
    currentUserName?: string;
}>();
const { message, currentUserId, currentUserName } = props as any;

function toggleImportant() {
    try {
        // optimistic toggle
        message._important = !message._important;
        // emit a window event so other parts (or a backend bridge) can listen and persist
        window.dispatchEvent(
            new CustomEvent('message:toggle-important', {
                detail: { id: message.id, important: message._important },
            }),
        );

        // try to persist to backend if an endpoint exists (graceful failure)
        const token =
            document
                .querySelector('meta[name="csrf-token"]')
                ?.getAttribute('content') || '';
        fetch(`/messages/${message.id}/important`, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': token,
                'X-Requested-With': 'XMLHttpRequest',
                Accept: 'application/json',
            },
            body: JSON.stringify({ important: !!message._important }),
        })
            .then((res) => {
                if (!res.ok) {
                    // silently ignore: backend may not implement endpoint yet
                    console.debug(
                        'Persist important failed or not implemented',
                        res.status,
                    );
                }
            })
            .catch((e) => {
                console.debug('Persist important network error', e);
            });
    } catch (e) {
        console.warn('toggleImportant error', e);
    }
}

// Decide side: always compare senderId and currentUserId as strings
const senderId =
    message?.user?.id ?? message?.user_id ?? message?.sender_id ?? null;
const isRight =
    String(senderId) === currentUserId ||
    message?._pending === true ||
    message?._forceMine === true;
</script>

<style scoped>
.text-color-white {
    color: var(--color-muted);
}
</style>
