<template>
    <div class="chat-panel flex flex-col p-4" style="width: 100%">
        <!-- Navbar for members and invite -->
        <div class="mb-2 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <img
                    :src="roomAvatar"
                    class="h-11 w-11 rounded-full object-cover"
                    @error="onAvatarError"
                    :alt="roomName"
                />
                <div class="font-semibold">{{ roomName }}</div>
            </div>
            <div class="flex h-8 items-center gap-2">
                <!-- <RoomMembers ref="roomMembersRef" :room-id="props.roomId" /> -->
                <!-- Invite trigger (header handles search when visible) -->
                <div class="flex items-center gap-2">
                    <!--
                    <button
                        class="btn btn-xs btn-primary cursor-pointer"
                        type="button"
                        @click="toggleHeaderInvite"
                    >
                        Convidar
                    </button>
                    <span v-if="inviteUser" class="ml-2 text-xs text-gray-400"
                        >Selecionado: {{ inviteUser.name }} (id:
                        {{ inviteUser.id }})</span
                    >
                    <span v-if="inviteError" class="ml-2 text-red-500">{{
                        inviteError
                    }}</span>
                    <span v-if="inviteSuccess" class="ml-2 text-green-600">{{
                        inviteSuccess
                    }}</span>
                    -->

                    <!-- Search -->
                    <button
                        class="btn-ghost flex h-8 w-8 items-center justify-center rounded"
                        title="Pesquisar"
                    >
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke-width="1.5"
                            stroke="currentColor"
                            class="h-5 w-5 text-gray-400"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"
                            />
                        </svg>
                    </button>

                    <!-- Info -->
                    <!-- Search -->
                    <button
                        class="btn-ghost flex h-8 w-8 cursor-pointer items-center justify-center rounded"
                        title="Pesquisar"
                    >
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke-width="1.5"
                            stroke="currentColor"
                            class="h-5 w-5 text-gray-400"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z"
                            />
                        </svg>
                    </button>
                </div>
            </div>
        </div>
        <div ref="messagesContainer" class="messages mb-4 flex-1 overflow-auto">
            <div v-for="m in messages" :key="m.id" class="mb-3">
                <MessageItem
                    :message="m"
                    :current-user-id="currentUserId"
                    :current-user-name="currentUserName"
                />
            </div>
        </div>
        <!-- Floating button to jump to bottom when user is scrolled up -->
        <button
            v-if="showScrollToBottom"
            @click="scrollToBottom(true)"
            class="scroll-bottom-btn"
            title="Ir para o final"
        >
            <svg
                xmlns="http://www.w3.org/2000/svg"
                class="h-5 w-5"
                viewBox="0 0 20 20"
                fill="currentColor"
            >
                <path
                    fill-rule="evenodd"
                    d="M3.293 9.293a1 1 0 011.414 0L10 14.586l5.293-5.293a1 1 0 011.414 1.414l-6 6a1 1 0 01-1.414 0l-6-6a1 1 0 010-1.414z"
                    clip-rule="evenodd"
                />
            </svg>
        </button>
        <div class="mb-2 flex w-full items-center gap-3">
            <template v-for="(m, idx) in filteredCanned" :key="idx">
                <button
                    type="button"
                    class="rounded border border-gray-200 px-3 py-1.5 text-sm text-gray-600 hover:bg-gray-50 hover:text-black"
                    @click.prevent="sendPreMade(m)"
                    :title="m"
                >
                    {{ m }}
                </button>
            </template>
        </div>

        <form @submit.prevent="send">
            <div class="mb-2 w-full">
                <FileUploader ref="fileUploader" :room-id="props.roomId" />
            </div>
            <div class="flex items-center gap-2">
                <input
                    v-model="text"
                    class="flex-1 rounded border p-2"
                    placeholder="Escreve uma mensagem..."
                />

                <div v-if="sendError" class="ml-2 text-sm text-red-500">
                    {{ sendError }}
                </div>

                <div
                    class="flex h-10 w-10 items-center justify-center rounded-4xl border bg-orange-500 text-center"
                >
                    <button class="btn btn-primary">
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke-width="1.5"
                            stroke="currentColor"
                            class="h-6 w-6 text-white"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M6 12 3.269 3.125A59.769 59.769 0 0 1 21.485 12 59.768 59.768 0 0 1 3.27 20.875L5.999 12Zm0 0h7.5"
                            />
                        </svg>
                    </button>
                </div>
                <!-- Recording controls -->
                <div class="flex items-center gap-2">
                    <button
                        type="button"
                        :title="
                            isRecording
                                ? 'Parar gravação'
                                : 'Gravar mensagem de voz'
                        "
                        @click.prevent="
                            isRecording ? stopRecording() : startRecording()
                        "
                        class="flex h-10 w-10 items-center justify-center rounded-4xl border bg-gray-50"
                    >
                        <svg
                            v-if="!isRecording"
                            xmlns="http://www.w3.org/2000/svg"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke-width="1.5"
                            stroke="currentColor"
                            class="h-6 w-6 text-gray-500 hover:text-blue-600"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M12 15a3 3 0 0 0 3-3V6a3 3 0 1 0-6 0v6a3 3 0 0 0 3 3z"
                            />
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M19.5 12a7.5 7.5 0 0 1-15 0"
                            />
                        </svg>
                        <svg
                            v-else
                            xmlns="http://www.w3.org/2000/svg"
                            viewBox="0 0 24 24"
                            class="h-6 w-6 text-red-600"
                        >
                            <rect
                                x="6"
                                y="6"
                                width="12"
                                height="12"
                                rx="2"
                                ry="2"
                            />
                        </svg>
                    </button>
                </div>
            </div>
        </form>
        <!-- Recording preview and send/cancel actions -->
        <div v-if="recordedUrl" class="mt-2 flex items-center gap-3">
            <audio :src="recordedUrl" controls class="max-w-xs" />
            <div class="flex items-center gap-2">
                <button
                    type="button"
                    class="btn btn-sm btn-primary"
                    @click="sendRecording"
                >
                    Enviar
                </button>
                <button
                    type="button"
                    class="btn btn-sm"
                    @click="cancelRecording"
                >
                    Cancelar
                </button>
                <div class="text-sm text-gray-500">
                    {{ formatSeconds(recordSeconds) }}
                </div>
            </div>
        </div>
        <div class="mt-2 flex w-full items-center justify-start gap-3">
            <button
                type="button"
                class="btn btn-ghost p-1"
                @click="triggerUploader"
                title="Enviar arquivo ou imagem"
            >
                <svg
                    xmlns="http://www.w3.org/2000/svg"
                    class="h-6 w-6 text-gray-500 hover:text-blue-600"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l7.071-7.071a4 4 0 00-5.657-5.657l-7.071 7.07a6 6 0 108.485 8.486l6.364-6.364"
                    />
                </svg>
            </button>
            <svg
                xmlns="http://www.w3.org/2000/svg"
                fill="none"
                viewBox="0 0 24 24"
                stroke-width="1.5"
                stroke="currentColor"
                class="h-6 w-6 text-gray-500 hover:text-blue-600"
            >
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    d="M15.182 15.182a4.5 4.5 0 0 1-6.364 0M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0ZM9.75 9.75c0 .414-.168.75-.375.75S9 10.164 9 9.75 9.168 9 9.375 9s.375.336.375.75Zm-.375 0h.008v.015h-.008V9.75Zm5.625 0c0 .414-.168.75-.375.75s-.375-.336-.375-.75.168-.75.375-.75.375.336.375.75Zm-.375 0h.008v.015h-.008V9.75Z"
                />
            </svg>
            <svg
                xmlns="http://www.w3.org/2000/svg"
                fill="none"
                viewBox="0 0 24 24"
                stroke-width="1.5"
                stroke="currentColor"
                class="h-6 w-6 text-gray-500 hover:text-blue-600"
            >
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    d="M17.995 3.744v7.5a6 6 0 1 1-12 0v-7.5m-2.25 16.502h16.5"
                />
            </svg>
            <svg
                xmlns="http://www.w3.org/2000/svg"
                fill="none"
                viewBox="0 0 24 24"
                stroke-width="1.5"
                stroke="currentColor"
                class="h-6 w-6 text-gray-500 hover:text-blue-600"
            >
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5m-9-6h.008v.008H12v-.008ZM12 15h.008v.008H12V15Zm0 2.25h.008v.008H12v-.008ZM9.75 15h.008v.008H9.75V15Zm0 2.25h.008v.008H9.75v-.008ZM7.5 15h.008v.008H7.5V15Zm0 2.25h.008v.008H7.5v-.008Zm6.75-4.5h.008v.008h-.008v-.008Zm0 2.25h.008v.008h-.008V15Zm0 2.25h.008v.008h-.008v-.008Zm2.25-4.5h.008v.008H16.5v-.008Zm0 2.25h.008v.008H16.5V15Z"
                />
            </svg>
            <svg
                xmlns="http://www.w3.org/2000/svg"
                fill="none"
                viewBox="0 0 24 24"
                stroke-width="1.5"
                stroke="currentColor"
                class="h-6 w-6 text-gray-500 hover:text-blue-600"
            >
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    d="m15.75 10.5 4.72-4.72a.75.75 0 0 1 1.28.53v11.38a.75.75 0 0 1-1.28.53l-4.72-4.72M4.5 18.75h9a2.25 2.25 0 0 0 2.25-2.25v-9a2.25 2.25 0 0 0-2.25-2.25h-9A2.25 2.25 0 0 0 2.25 7.5v9a2.25 2.25 0 0 0 2.25 2.25Z"
                />
            </svg>
            <svg
                xmlns="http://www.w3.org/2000/svg"
                fill="none"
                viewBox="0 0 24 24"
                stroke-width="1.5"
                stroke="currentColor"
                class="h-6 w-6 text-gray-500 hover:text-blue-600"
            >
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    d="M11.48 3.499a.562.562 0 0 1 1.04 0l2.125 5.111a.563.563 0 0 0 .475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 0 0-.182.557l1.285 5.385a.562.562 0 0 1-.84.61l-4.725-2.885a.562.562 0 0 0-.586 0L6.982 20.54a.562.562 0 0 1-.84-.61l1.285-5.386a.562.562 0 0 0-.182-.557l-4.204-3.602a.562.562 0 0 1 .321-.988l5.518-.442a.563.563 0 0 0 .475-.345L11.48 3.5Z"
                />
            </svg>
            <svg
                xmlns="http://www.w3.org/2000/svg"
                fill="none"
                viewBox="0 0 24 24"
                stroke-width="1.5"
                stroke="currentColor"
                class="h-6 w-6 text-gray-500 hover:text-blue-600"
            >
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z"
                />
            </svg>
            <svg
                xmlns="http://www.w3.org/2000/svg"
                fill="none"
                viewBox="0 0 24 24"
                stroke-width="1.5"
                stroke="currentColor"
                class="h-6 w-6 text-gray-500 hover:text-blue-600"
            >
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"
                />
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z"
                />
            </svg>
            <svg
                xmlns="http://www.w3.org/2000/svg"
                fill="none"
                viewBox="0 0 24 24"
                stroke-width="1.5"
                stroke="currentColor"
                class="h-6 w-6 text-gray-500 hover:text-blue-600"
            >
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    d="M14.25 6.087c0-.355.186-.676.401-.959.221-.29.349-.634.349-1.003 0-1.036-1.007-1.875-2.25-1.875s-2.25.84-2.25 1.875c0 .369.128.713.349 1.003.215.283.401.604.401.959v0a.64.64 0 0 1-.657.643 48.39 48.39 0 0 1-4.163-.3c.186 1.613.293 3.25.315 4.907a.656.656 0 0 1-.658.663v0c-.355 0-.676-.186-.959-.401a1.647 1.647 0 0 0-1.003-.349c-1.036 0-1.875 1.007-1.875 2.25s.84 2.25 1.875 2.25c.369 0 .713-.128 1.003-.349.283-.215.604-.401.959-.401v0c.31 0 .555.26.532.57a48.039 48.039 0 0 1-.642 5.056c1.518.19 3.058.309 4.616.354a.64.64 0 0 0 .657-.643v0c0-.355-.186-.676-.401-.959a1.647 1.647 0 0 1-.349-1.003c0-1.035 1.008-1.875 2.25-1.875 1.243 0 2.25.84 2.25 1.875 0 .369-.128.713-.349 1.003-.215.283-.4.604-.4.959v0c0 .333.277.599.61.58a48.1 48.1 0 0 0 5.427-.63 48.05 48.05 0 0 0 .582-4.717.532.532 0 0 0-.533-.57v0c-.355 0-.676.186-.959.401-.29.221-.634.349-1.003.349-1.035 0-1.875-1.007-1.875-2.25s.84-2.25 1.875-2.25c.37 0 .713.128 1.003.349.283.215.604.401.96.401v0a.656.656 0 0 0 .658-.663 48.422 48.422 0 0 0-.37-5.36c-1.886.342-3.81.574-5.766.689a.578.578 0 0 1-.61-.58v0Z"
                />
            </svg>
            <svg
                xmlns="http://www.w3.org/2000/svg"
                fill="none"
                viewBox="0 0 24 24"
                stroke-width="1.5"
                stroke="currentColor"
                class="h-6 w-6 text-gray-500 hover:text-blue-600"
            >
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    d="M6.75 12a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0ZM12.75 12a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0ZM18.75 12a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Z"
                />
            </svg>
        </div>
    </div>
</template>

<script setup lang="ts">
import { inviteSearchVisible } from '@/stores/ui';
import { usePage } from '@inertiajs/vue3';
import { computed, nextTick, onMounted, onUnmounted, ref, watch } from 'vue';
import FileUploader from './FileUploader.vue';
import MessageItem from './MessageItem.vue';

const props = defineProps<{ roomId: number }>();
const messages = ref([] as any[]);
const text = ref('');
const inviteUser = ref<any | null>(null);
const sendError = ref('');
const inviteError = ref('');
const inviteSuccess = ref('');
const members = ref<any[]>([]);
const defaultAvatar =
    'https://ui-avatars.com/api/?name=User&background=eee&color=555';
const roomMembersRef = ref<any | null>(null);
const roomName = ref('Chat');
const roomAvatar = ref(defaultAvatar);
// canned (pre-made) messages that can be clicked to quickly send
const cannedMessages = [
    'Hi! Awesome',
    'Hi! Will check it out',
    'Hi! How are you doing',
    'Thanks, I will follow up',
    'Got it, thanks!',
];

// filtered suggestions that update as the user types
const filteredCanned = computed(() => {
    const q = String(text.value || '')
        .trim()
        .toLowerCase();
    if (!q) return cannedMessages.slice(0, 5);
    return cannedMessages
        .filter((m) => m.toLowerCase().includes(q))
        .slice(0, 5);
});

async function sendPreMade(message: string) {
    // Set the input and send immediately
    text.value = message;
    try {
        await send();
    } catch (e) {
        console.warn('[ChatPanel] failed to send pre-made message', e);
    }
}
// Listen to header invite search/select events
function toggleHeaderInvite() {
    console.log(
        '[ChatPanel] toggleHeaderInvite clicked, toggling shared store inviteSearchVisible and dispatching invite:toggle',
    );
    // toggle the shared store value directly for reliability
    try {
        inviteSearchVisible.value = !inviteSearchVisible.value;
    } catch (e) {
        console.warn(
            '[ChatPanel] failed to toggle shared inviteSearchVisible',
            e,
        );
    }
    // keep the custom event for any listeners still using it
    window.dispatchEvent(new CustomEvent('invite:toggle'));
}

function onHeaderInviteSearch(e: any) {
    // header sent a search query (not used here directly)
    console.log('[ChatPanel] header invite search', e.detail.query);
}

function onHeaderInviteSelect(e: any) {
    // header selected a user to invite
    console.log('[ChatPanel] header invite selected', e.detail.user);
    onUserSelect(e.detail.user);
    // auto-submit invite
    submitInvite();
}

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

async function fetchRoomInfo(roomId: number) {
    if (!roomId) {
        roomName.value = 'Chat';
        roomAvatar.value = defaultAvatar;
        return;
    }
    try {
        const res = await fetch(`/rooms/${roomId}`, {
            credentials: 'same-origin',
            headers: { Accept: 'application/json' },
        });
        if (!res.ok) {
            roomName.value = 'Chat';
            roomAvatar.value = defaultAvatar;
            return;
        }
        const data = await res.json();
        roomName.value = data.name || `Sala ${roomId}`;
        // normalize avatar URL: accept absolute http(s), data:, or absolute path; otherwise prefix with '/'
        const raw = data.avatar || '';
        function normalize(src: string) {
            if (!src) return defaultAvatar;
            if (
                src.startsWith('http://') ||
                src.startsWith('https://') ||
                src.startsWith('data:') ||
                src.startsWith('/')
            )
                return src;
            // relative path stored in DB (e.g., storage/avatars/...) -> make it absolute
            return '/' + src.replace(/^\//, '');
        }
        roomAvatar.value = normalize(String(raw));
    } catch (e) {
        roomName.value = 'Chat';
        roomAvatar.value = defaultAvatar;
    }
}

function onAvatarError(e: Event) {
    // Fallback to default avatar if image fails to load
    roomAvatar.value = defaultAvatar;
}

async function submitInvite() {
    inviteError.value = '';
    if (!props.roomId || !inviteUser.value) {
        console.log(
            '[ChatPanel] Invite blocked: missing roomId or inviteUser',
            { roomId: props.roomId, inviteUser: inviteUser.value },
        );
        return;
    }
    try {
        const token =
            document
                .querySelector('meta[name="csrf-token"]')
                ?.getAttribute('content') || '';
        console.log('[ChatPanel] Sending invite POST', {
            roomId: props.roomId,
            userId: inviteUser.value.id,
        });
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
        inviteError.value = '';
        // Refresh the members list exposed by the RoomMembers component without a full page reload
        try {
            if (
                roomMembersRef.value &&
                typeof roomMembersRef.value.fetchMembers === 'function'
            ) {
                roomMembersRef.value.fetchMembers(props.roomId);
            }
        } catch (e) {
            console.warn('[ChatPanel] failed to refresh RoomMembers', e);
        }
        // show a short success message
        inviteSuccess.value = 'Convidado com sucesso.';
        setTimeout(() => {
            inviteSuccess.value = '';
        }, 3000);
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
// Recording state
const isRecording = ref(false);
const recorder = ref<MediaRecorder | null>(null);
const recorderStream = ref<MediaStream | null>(null);
const recordedBlob = ref<Blob | null>(null);
const recordedUrl = ref<string | null>(null);
const recordStartedAt = ref<number | null>(null);
const recordSeconds = ref(0);
let recordTimerId: number | undefined;
const showScrollToBottom = ref(false);
// allow auto-scrolling only during the initial phase (to land at bottom),
// afterwards the user is free to scroll without being pulled down.
const initialPhase = ref(true);
let initialPhaseTimeout: number | undefined;

function triggerUploader() {
    fileUploader.value?.trigger?.();
}

function formatSeconds(sec: number) {
    const s = Math.floor(sec % 60)
        .toString()
        .padStart(2, '0');
    const m = Math.floor(sec / 60)
        .toString()
        .padStart(2, '0');
    return `${m}:${s}`;
}

async function startRecording() {
    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
        console.warn('Media devices not supported');
        return;
    }
    try {
        const stream = await navigator.mediaDevices.getUserMedia({
            audio: true,
        });
        recorderStream.value = stream;
        const mr = new MediaRecorder(stream);
        const chunks: BlobPart[] = [];
        mr.ondataavailable = (ev: BlobEvent) => {
            if (ev.data && ev.data.size) chunks.push(ev.data);
        };
        mr.onstop = () => {
            const mime =
                (chunks[0] && (chunks[0] as Blob).type) || 'audio/webm';
            recordedBlob.value = new Blob(chunks, { type: mime });
            if (recordedUrl.value) {
                try {
                    URL.revokeObjectURL(recordedUrl.value);
                } catch (e) {}
            }
            recordedUrl.value = URL.createObjectURL(recordedBlob.value);
            isRecording.value = false;
            // stop any tracks
            if (recorderStream.value) {
                recorderStream.value.getTracks().forEach((t) => t.stop());
                recorderStream.value = null;
            }
            if (recordTimerId) clearInterval(recordTimerId);
            recordStartedAt.value = null;
            recordSeconds.value = Math.max(recordSeconds.value, 0);
        };
        recorder.value = mr;
        mr.start();
        isRecording.value = true;
        recordStartedAt.value = Date.now();
        recordSeconds.value = 0;
        recordTimerId = window.setInterval(() => {
            if (recordStartedAt.value) {
                recordSeconds.value = Math.floor(
                    (Date.now() - recordStartedAt.value) / 1000,
                );
            }
        }, 250);
    } catch (e) {
        console.error('Failed to start recording', e);
    }
}

function stopRecording() {
    try {
        if (recorder.value && recorder.value.state !== 'inactive') {
            recorder.value.stop();
        }
    } catch (e) {
        console.warn('stopRecording error', e);
    }
}

function cancelRecording() {
    // discard recorded blob and cleanup
    recordedBlob.value = null;
    if (recordedUrl.value) {
        try {
            URL.revokeObjectURL(recordedUrl.value);
        } catch (e) {}
    }
    recordedUrl.value = null;
    recordSeconds.value = 0;
    if (recorderStream.value) {
        recorderStream.value.getTracks().forEach((t) => t.stop());
        recorderStream.value = null;
    }
    if (recorder.value && recorder.value.state !== 'inactive') {
        try {
            recorder.value.stop();
        } catch (e) {}
    }
    recorder.value = null;
    isRecording.value = false;
    if (recordTimerId) clearInterval(recordTimerId);
    recordStartedAt.value = null;
}

async function sendRecording() {
    if (!recordedBlob.value || !props.roomId) return;
    // Convert blob to a file and set it in the file uploader, then call sendFile()
    const filename = `voice_${Date.now()}.webm`;
    const file = new File([recordedBlob.value], filename, {
        type: recordedBlob.value.type || 'audio/webm',
    });
    try {
        if (
            fileUploader.value &&
            typeof fileUploader.value.setFile === 'function'
        ) {
            fileUploader.value.setFile(file, recordedUrl.value);
            const saved = await fileUploader.value.sendFile();
            if (saved) {
                messages.value.push(saved);
                // cleanup
                cancelRecording();
                scrollToBottom(true);
            }
        } else {
            // fallback: post directly to /messages/file
            const formData = new FormData();
            formData.append('file', file);
            formData.append('room_id', String(props.roomId));
            const token =
                document
                    .querySelector('meta[name="csrf-token"]')
                    ?.getAttribute('content') || '';
            formData.append('_token', token);
            const res = await fetch('/messages/file', {
                method: 'POST',
                credentials: 'same-origin',
                body: formData,
            });
            if (res.ok) {
                const saved = await res.json();
                messages.value.push(saved);
                cancelRecording();
                scrollToBottom(true);
            } else {
                console.error('Audio upload failed', await res.text());
            }
        }
    } catch (e) {
        console.error('sendRecording error', e);
    }
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
                        if (initialPhaseTimeout)
                            clearTimeout(initialPhaseTimeout);
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
        fetchRoomInfo(props.roomId);
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

// global listeners for header invite actions
window.addEventListener('invite:search', onHeaderInviteSearch);
window.addEventListener('invite:selected', onHeaderInviteSelect);

onUnmounted(() => {
    window.removeEventListener('invite:search', onHeaderInviteSearch);
    window.removeEventListener('invite:selected', onHeaderInviteSelect);
});

onUnmounted(() => {
    if (intervalId) {
        clearInterval(intervalId);
    }
    const el = messagesContainer.value;
    if (el) el.removeEventListener('scroll', onScroll);
    if (initialPhaseTimeout) clearTimeout(initialPhaseTimeout);
});

watch(
    () => props.roomId,
    (newRoomId) => {
        fetchMessages(newRoomId);
        fetchRoomInfo(newRoomId);
    },
);

watch(
    () => props.roomId,
    async (roomId) => {
        members.value = [];
    },
);

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
    sendError.value = '';
    // Guard: must have a room id to send
    if (!props.roomId) {
        console.warn('[ChatPanel] send blocked: missing roomId', props.roomId);
        sendError.value = 'Sala não selecionada.';
        return;
    }

    // If file uploader has a file, let it handle sending both file and text
    if (
        fileUploader.value &&
        typeof fileUploader.value.sendFile === 'function'
    ) {
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
        const token =
            document
                .querySelector('meta[name="csrf-token"]')
                ?.getAttribute('content') || '';
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
                sendError.value = 'Erro ao enviar mensagem.';
            } catch (e) {
                console.error('Failed to save message', res.status);
                sendError.value = 'Erro ao enviar mensagem.';
            }
            return;
        }

        const contentType = res.headers.get('content-type') || '';
        if (!contentType.includes('application/json')) {
            const body = await res.text();
            console.error(
                'Unexpected non-JSON response saving message',
                res.status,
                body,
            );
            optimistic._pending = false;
            optimistic._failed = true;
            return;
        }

        const saved = await res.json();

        // replace optimistic with saved (match by optimistic id)
        const idx = messages.value.findIndex((m) => m.id === optimistic.id);
        if (idx !== -1) {
            const target = messages.value[idx];
            console.debug('Merging optimistic => saved', {
                optimistic: target,
                saved,
            });
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
        sendError.value = 'Erro de rede ao enviar mensagem.';
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
    background-color: rgba(100, 100, 100, 0.3);
    border-radius: 8px;
    border: 2px solid transparent;
    background-clip: padding-box;
}
.messages::-webkit-scrollbar-thumb:hover {
    background-color: rgba(100, 100, 100, 0.5);
}

/* Firefox */
.messages {
    scrollbar-width: thin;
    scrollbar-color: rgba(100, 100, 100, 0.3) transparent;
}
</style>
