<template>
    <div class="chat-panel flex flex-col p-4" style="width: 100%">
        <div class="messages mb-4 flex-1 overflow-auto">
            <div v-for="m in messages" :key="m.id" class="mb-3">
                <MessageItem :message="m" :current-user-id="currentUserId" :current-user-name="currentUserName" />
            </div>
        </div>

        <form @submit.prevent="send">
            <div class="flex gap-2">
                <input
                    v-model="text"
                    class="flex-1 rounded border p-2"
                    placeholder="Escreve uma mensagem..."
                />
                <button class="btn btn-primary">Enviar</button>
            </div>
        </form>
    </div>
</template>

<script setup lang="ts">
import { onMounted, ref, watch, onUnmounted } from 'vue';
import { usePage } from '@inertiajs/vue3';
import MessageItem from './MessageItem.vue';

const props = defineProps<{ roomId: number }>();
const messages = ref([] as any[]);
const text = ref('');

// Use Inertia shared props to get the authenticated user (falls back to demo id)
const page: any = usePage();
const authUser: any = page.props.value?.auth?.user ?? null;
const currentUserId = ref(String(authUser?.id ?? 999));
const currentUserName = authUser?.name ?? 'You';
const defaultAvatar = 'https://assets.edlin.app/images/rossedlin/03/rossedlin-03-100.jpg';

function isMine(m: any) {
    const senderId = m.user?.id ?? m.user_id ?? m.sender_id ?? null;
    if (senderId === null || senderId === undefined) return false;
    return String(senderId) === currentUserId.value;
}

async function fetchMessages(roomId: number) {
    if (!roomId) return;
    const res = await fetch(`/messages?room_id=${roomId}`, {
        credentials: 'same-origin',
        headers: { Accept: 'application/json' },
    });
    if (res.ok) {
        const data = await res.json();
        messages.value = data.messages;
        // Use backend-provided currentUserId for robust side detection
        if (data.currentUserId) {
            currentUserId.value = String(data.currentUserId);
        }
    }
}

let intervalId: number | undefined;

onMounted(() => {
    if (props.roomId) {
        fetchMessages(props.roomId);
    }
    intervalId = window.setInterval(() => {
        fetchMessages(props.roomId);
    }, 2000);
});

onUnmounted(() => {
    if (intervalId) {
        clearInterval(intervalId);
    }
});

watch(() => props.roomId, (newRoomId) => {
    fetchMessages(newRoomId);
});

async function send() {
    if (!text.value.trim()) return;

    const payload = { content: text.value, room_id: props.roomId };

    // optimistic id in case network fails
    const optimistic = {
        id: Date.now(),
        user: { id: currentUserId.value, name: currentUserName },
        content: text.value,
        _pending: true,
        _failed: false,
    };
    messages.value.push(optimistic);

    try {
        const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
        const res = await fetch('/messages', {
            method: 'POST',
            credentials: 'same-origin', // ensure session cookie is sent
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': token,
                'X-Requested-With': 'XMLHttpRequest',
                Accept: 'application/json',
            },
            body: JSON.stringify(payload),
        });

        if (!res.ok) {
            // keep optimistic but mark failed and log server response for debugging
            optimistic._pending = false;
            optimistic._failed = true;
            try {
                const body = await res.text();
                console.error('Failed to save message', res.status, body);
            } catch (e) {
                console.error('Failed to save message', res.status);
            }
            return;
        }

        const contentType = res.headers.get('content-type') || '';
        if (!contentType.includes('application/json')) {
            const body = await res.text();
            console.error('Unexpected non-JSON response saving message', res.status, body);
            optimistic._pending = false;
            optimistic._failed = true;
            return;
        }

        const saved = await res.json();

        // replace optimistic with saved (match by optimistic id)
        const idx = messages.value.findIndex((m) => m.id === optimistic.id);
        if (idx !== -1) {
            const target = messages.value[idx];
            console.debug('Merging optimistic => saved', { optimistic: target, saved });
            // Preserve the optimistic id (used as the v-for key) to avoid Vue recreating the element.
            // Copy server fields but keep target.id unchanged. Store server id separately.
            const serverId = saved.id;
            const savedCopy = { ...saved, id: target.id };
            Object.assign(target, savedCopy);
            target._serverId = serverId;
            // Keep the message visually on the right (even after server confirms)
            target._forceMine = true;
            // clear helper flags
            delete target._pending;
            delete target._failed;
        } else {
            messages.value.push(saved);
        }

        text.value = '';
    } catch (e) {
        // network error: keep optimistic but notify
        console.error(e);
    }
}
</script>

<style scoped>
.messages {
    max-height: calc(100vh - 200px);
}
.bg-card {
    background: var(--card-bg);
}
</style>
