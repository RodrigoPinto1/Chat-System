<template>
    <div class="chat-panel flex flex-col p-4" style="width: 100%">
        <!-- Navbar for members and invite -->
        <div class="flex items-center justify-between mb-2">
            <div class="font-semibold">Chat</div>
            <div class="flex items-center gap-2">
                <RoomMembers :room-id="props.roomId" />
                <!-- Invite form -->
                <form @submit.prevent="submitInvite" class="flex items-center gap-2">
                    <UserSearch @select="onUserSelect" @error="inviteError = $event" />
                    <button class="btn btn-xs btn-primary" type="submit" :disabled="!inviteUser">Convidar</button>
                    <span v-if="inviteUser" class="text-xs text-gray-400 ml-2">Selecionado: {{ inviteUser.name }} (id: {{ inviteUser.id }})</span>
                </form>
                <span v-if="inviteError" class="text-red-500 ml-2">{{ inviteError }}</span>
            </div>
        </div>
        <div ref="messagesContainer" class="messages mb-4 flex-1 overflow-auto">
            <div v-for="m in messages" :key="m.id" class="mb-3">
                <MessageItem :message="m" :current-user-id="currentUserId" :current-user-name="currentUserName" />
            </div>
        </div>
        <!-- Floating button to jump to bottom when user is scrolled up -->
        <button v-if="showScrollToBottom" @click="scrollToBottom(true)" class="scroll-bottom-btn" title="Ir para o final">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M3.293 9.293a1 1 0 011.414 0L10 14.586l5.293-5.293a1 1 0 011.414 1.414l-6 6a1 1 0 01-1.414 0l-6-6a1 1 0 010-1.414z" clip-rule="evenodd" />
            </svg>
        </button>
        <form @submit.prevent="send">
            <div class="mb-2 w-full">
                <FileUploader ref="fileUploader" :room-id="props.roomId" />
            </div>
            <div class="flex gap-2 items-center">
                <input
                    v-model="text"
                    class="flex-1 rounded border p-2"
                    placeholder="Escreve uma mensagem..."
                />
                <button type="button" class="btn btn-ghost p-1" @click="triggerUploader" title="Enviar arquivo ou imagem">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-gray-500 hover:text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l7.071-7.071a4 4 0 00-5.657-5.657l-7.071 7.07a6 6 0 108.485 8.486l6.364-6.364" />
                    </svg>
                </button>
                <button class="btn btn-primary">Enviar</button>
            </div>
        </form>
    </div>
</template>

<script setup lang="ts">
import { onMounted, ref, watch, onUnmounted, nextTick } from 'vue';
import { usePage } from '@inertiajs/vue3';
import MessageItem from './MessageItem.vue';
import UserSearch from './UserSearch.vue';
import RoomMembers from './RoomMembers.vue';
import FileUploader from './FileUploader.vue';

const props = defineProps<{ roomId: number }>();
const messages = ref([] as any[]);
const text = ref('');
const inviteUser = ref<any|null>(null);
const inviteError = ref('');
const members = ref<any[]>([]);
const defaultAvatar = 'https://ui-avatars.com/api/?name=User&background=eee&color=555';

// Use Inertia shared props to get the authenticated user (falls back to demo id)
const page: any = usePage();
const authUser: any = page.props.value?.auth?.user ?? null;
const currentUserId = ref(String(authUser?.id ?? 999));
const currentUserName = authUser?.name ?? 'You';

function roleClass(role: string) {
    if (role === 'owner') return 'bg-yellow-100 text-yellow-800';
    if (role === 'admin') return 'bg-blue-100 text-blue-800';
    return 'bg-gray-100 text-gray-700';
}

function onUserSelect(user: any) {
    console.log('[ChatPanel] User selected for invite:', user);
    inviteUser.value = user;
    inviteError.value = '';
}

async function submitInvite() {
    inviteError.value = '';
    if (!props.roomId || !inviteUser.value) {
        console.log('[ChatPanel] Invite blocked: missing roomId or inviteUser', { roomId: props.roomId, inviteUser: inviteUser.value });
        return;
    }
    try {
        const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
        console.log('[ChatPanel] Sending invite POST', { roomId: props.roomId, userId: inviteUser.value.id });
        const res = await fetch(`/rooms/${props.roomId}/invite`, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': token,
                Accept: 'application/json',
            },
            body: JSON.stringify({ user_id: inviteUser.value.id }),
        });
        if (!res.ok) {
            const body = await res.json();
            inviteError.value = body.error || 'Erro ao convidar.';
            console.log('[ChatPanel] Invite failed', body);
            return;
        }
        const result = await res.json();
        console.log('[ChatPanel] Invite success', result);
        inviteUser.value = null;
    } catch (e) {
        inviteError.value = 'Erro de rede.';
        console.log('[ChatPanel] Invite network error', e);
    }
}
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
        // preserve previous messages length to decide whether to auto-scroll
        const prevLen = messages.value.length;
        messages.value = data.messages || [];
        // Use backend-provided currentUserId for robust side detection
        if (data.currentUserId) {
            currentUserId.value = String(data.currentUserId);
        }
        // Only auto-scroll when we had no messages (initial load) or when the user is already near the bottom
        const wasEmpty = prevLen === 0;
        if (wasEmpty) {
            // initial load: allow a short initial phase where image loads can still auto-scroll
            initialPhase.value = true;
            if (initialPhaseTimeout) clearTimeout(initialPhaseTimeout);
            initialPhaseTimeout = window.setTimeout(() => {
                initialPhase.value = false;
            }, 1500);
            scrollToBottom();
            showScrollToBottom.value = false;
        } else if (isUserNearBottom()) {
            // user is at bottom: keep them pinned
            scrollToBottom();
            showScrollToBottom.value = false;
        } else {
            // New messages arrived while user is reading older ones: do not force-scroll.
            // Show the floating button so they can jump to the end when ready.
            showScrollToBottom.value = true;
        }
        attachImageLoadHandlers();
    }
}

let intervalId: number | undefined;
const messagesContainer = ref<HTMLElement | null>(null);
// File uploader component is used for file selection, preview and upload
const fileUploader = ref<any | null>(null);
const showScrollToBottom = ref(false);
// allow auto-scrolling only during the initial phase (to land at bottom),
// afterwards the user is free to scroll without being pulled down.
const initialPhase = ref(true);
let initialPhaseTimeout: number | undefined;

function triggerUploader() {
    fileUploader.value?.trigger?.();
}

function scrollToBottom(smooth = false) {
    nextTick(() => {
        const el = messagesContainer.value;
        if (!el) return;
        try {
            if (smooth) {
                el.scrollTo({ top: el.scrollHeight, behavior: 'smooth' });
            } else {
                el.scrollTop = el.scrollHeight;
            }
        } catch (e) {
            el.scrollTop = el.scrollHeight;
        }
    });
}

// Re-scroll when images inside messages load (they can change height)
function attachImageLoadHandlers() {
    nextTick(() => {
        const el = messagesContainer.value;
        if (!el) return;
        const imgs = el.querySelectorAll('img');
        imgs.forEach((img: HTMLImageElement) => {
            if (!img.dataset.__handled) {
                img.addEventListener('load', () => {
                    // During initialPhase we still want to follow image loads
                    // so content doesn't shift unexpectedly. After that, do not auto-scroll.
                    if (initialPhase.value) {
                        scrollToBottom(true);
                        if (initialPhaseTimeout) clearTimeout(initialPhaseTimeout);
                        initialPhaseTimeout = window.setTimeout(() => {
                            initialPhase.value = false;
                        }, 1200);
                        return;
                    }
                    // If user is already near bottom, keep them pinned to the bottom
                    // so incoming images don't move the view away from the latest messages.
                    if (isUserNearBottom()) {
                        scrollToBottom(true);
                    }
                });
                img.dataset.__handled = '1';
            }
        });
    });
}

function isUserNearBottom(threshold = 150) {
    const el = messagesContainer.value;
    if (!el) return true;
    const distance = el.scrollHeight - (el.scrollTop + el.clientHeight);
    return distance <= threshold;
}

// setup: fetch and poll, and attach scroll listener
onMounted(() => {
    if (props.roomId) {
        fetchMessages(props.roomId);
    }
    intervalId = window.setInterval(() => {
        fetchMessages(props.roomId);
    }, 2000);
    // Attach scroll listener
    nextTick(() => {
        const el = messagesContainer.value;
        if (el) el.addEventListener('scroll', onScroll, { passive: true });
    });
});

onUnmounted(() => {
    if (intervalId) {
        clearInterval(intervalId);
    }
    const el = messagesContainer.value;
    if (el) el.removeEventListener('scroll', onScroll);
    if (initialPhaseTimeout) clearTimeout(initialPhaseTimeout);
});

watch(() => props.roomId, (newRoomId) => {
    fetchMessages(newRoomId);
});

watch(() => props.roomId, async (roomId) => {
    members.value = [];
});

// When messages update (polling or new ones), scroll to bottom and attach handlers
watch(messages, (newVal, oldVal) => {
    // Don't auto-scroll here; image loads and fetchMessages handle necessary adjustments.
    attachImageLoadHandlers();
});

// Update showScrollToBottom based on user scroll position
function onScroll() {
    showScrollToBottom.value = !isUserNearBottom(100);
}

onMounted(() => {
    if (props.roomId) {
        fetchMessages(props.roomId);
    }
    intervalId = window.setInterval(() => {
        fetchMessages(props.roomId);
    }, 2000);
    // Attach scroll listener
    nextTick(() => {
        const el = messagesContainer.value;
        if (el) el.addEventListener('scroll', onScroll, { passive: true });
    });
});

onUnmounted(() => {
    if (intervalId) {
        clearInterval(intervalId);
    }
    const el = messagesContainer.value;
    if (el) el.removeEventListener('scroll', onScroll);
});

async function send() {
    // If file uploader has a file, let it handle sending both file and text
    if (fileUploader.value && typeof fileUploader.value.sendFile === 'function') {
        const saved = await fileUploader.value.sendFile(text.value);
        if (saved) {
            messages.value.push(saved);
            text.value = '';
            scrollToBottom(true);
            return;
        }
    }
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
        // scroll to bottom when message is sent
        scrollToBottom(true);
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

/* Custom scrollbar for messages container */
.messages::-webkit-scrollbar {
    width: 10px;
}
.messages::-webkit-scrollbar-track {
    background: transparent;
}
.messages::-webkit-scrollbar-thumb {
    background-color: rgba(100,100,100,0.3);
    border-radius: 8px;
    border: 2px solid transparent;
    background-clip: padding-box;
}
.messages::-webkit-scrollbar-thumb:hover {
    background-color: rgba(100,100,100,0.5);
}

/* Firefox */
.messages {
    scrollbar-width: thin;
    scrollbar-color: rgba(100,100,100,0.3) transparent;
}
</style>
