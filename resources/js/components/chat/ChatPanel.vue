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
        <div class="messages mb-4 flex-1 overflow-auto">
            <div v-for="m in messages" :key="m.id" class="mb-3">
                <MessageItem :message="m" :current-user-id="currentUserId" :current-user-name="currentUserName" />
            </div>
        </div>
        <form @submit.prevent="send">
            <div v-if="filePreview || selectedFile" style="border: 2px solid red; background: #ffe;" class="mb-2 flex items-center gap-3 bg-gray-50 border rounded p-2">
                <span style="color: red; font-weight: bold;"></span>
                <template v-if="isImagePreview && filePreview">
                    <img :src="filePreview" alt="preview" class="max-h-24 max-w-xs rounded border" />
                </template>
                <template v-else>
                    <span class="text-gray-700">{{ selectedFile?.name }}</span>
                </template>
                <button type="button" class="ml-2 text-red-500 hover:text-red-700" @click="removeFilePreview" title="Remover arquivo">
                    &times;
                </button>
            </div>
            <div class="flex gap-2 items-center">
                <input
                    v-model="text"
                    class="flex-1 rounded border p-2"
                    placeholder="Escreve uma mensagem..."
                />
                <input ref="fileInput" type="file" class="hidden" @change="onFileChange" />
                <button type="button" class="btn btn-ghost p-1" @click="triggerFileInput" title="Enviar arquivo ou imagem">
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
// --- File upload logic ---
const fileInput = ref<HTMLInputElement | null>(null);
const selectedFile = ref<File|null>(null);
const filePreview = ref<string|null>(null);
const isImagePreview = ref(false);

function triggerFileInput() {
    fileInput.value?.click();
}

async function onFileChange(e: Event) {
    const target = e.target as HTMLInputElement;
    if (target.files && target.files.length > 0) {
        selectedFile.value = target.files[0];
        console.log('[FilePreview] File selected:', selectedFile.value);
        // Preview
        if (selectedFile.value.type.startsWith('image/')) {
            filePreview.value = URL.createObjectURL(selectedFile.value);
            isImagePreview.value = true;
            console.log('[FilePreview] Image preview URL:', filePreview.value);
        } else {
            filePreview.value = null;
            isImagePreview.value = false;
            console.log('[FilePreview] Not an image, file name:', selectedFile.value.name);
        }
    } else {
        console.log('[FilePreview] No file selected');
    }
}

function removeFilePreview() {
    selectedFile.value = null;
    filePreview.value = null;
    isImagePreview.value = false;
    if (fileInput.value) fileInput.value.value = '';
}

// Call this to actually send the file
async function sendFile() {
    if (!selectedFile.value || !props.roomId) return;
    const formData = new FormData();
    formData.append('file', selectedFile.value);
    formData.append('room_id', String(props.roomId));
    if (text.value && text.value.trim()) {
        formData.append('meta', JSON.stringify({ text: text.value }));
    }
    try {
        const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
        const res = await fetch('/messages/file', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'X-CSRF-TOKEN': token,
                'Accept': 'application/json',
            },
            body: formData,
        });
        if (!res.ok) {
            const body = await res.text();
            console.error('File upload failed', res.status, body);
            return;
        }
        const saved = await res.json();
        messages.value.push(saved);
        removeFilePreview();
        text.value = '';
    } catch (e) {
        console.error('File upload error', e);
    }
}
import { onMounted, ref, watch, onUnmounted } from 'vue';
import { usePage } from '@inertiajs/vue3';
import MessageItem from './MessageItem.vue';
import UserSearch from './UserSearch.vue';
import RoomMembers from './RoomMembers.vue';

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

watch(() => props.roomId, async (roomId) => {
    members.value = [];
});

async function send() {
    // If a file is selected, send it first and skip text
    if (selectedFile.value) {
        await sendFile();
        return;
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
