<template>
    <AppLayout>
        <template #default>
            <div class="chat-app">
                <div class="chat-sidebar">
                    <RoomsList @select="handleRoomSelect" />
                </div>
                <div class="chat-main">
                    <template v-if="selectedRoomId">
                        <ChatPanel :room-id="selectedRoomId" />
                    </template>
                    <template v-else>
                        <div
                            class="flex h-full w-full flex-col items-center justify-center p-12 text-center"
                        >
                            <h1 class="mb-4 text-4xl font-bold">
                                Bem-vindo ao Chat!
                            </h1>
                            <p class="mb-6 text-lg text-muted">
                                Selecione uma sala à esquerda para começar a
                                conversar.<br />Crie uma nova sala para iniciar
                                um chat com seus amigos.
                            </p>
                            <img
                                src="/favicon.svg"
                                alt="Chat Hero"
                                class="mb-6 h-32 w-32 opacity-80"
                            />
                        </div>
                    </template>
                </div>
            </div>
        </template>
    </AppLayout>
</template>

<script setup lang="ts">
import ChatPanel from '@/components/chat/ChatPanel.vue';
import RoomsList from '@/components/chat/RoomsList.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { setCurrentRoomName } from '@/stores/ui';
import { onMounted, ref, watch } from 'vue';

const selectedRoomId = ref<number | null>(null); // null means no room selected

async function fetchAndSetRoomName(id: number) {
    try {
        const res = await fetch(`/rooms/${id}`, {
            credentials: 'same-origin',
            headers: { Accept: 'application/json' },
        });
        if (res.ok) {
            const data = await res.json();
            setCurrentRoomName(data.name || null);
            return;
        }
    } catch (e) {
        // ignore
    }
    setCurrentRoomName(null);
}

function handleRoomSelect(roomId: number) {
    selectedRoomId.value = roomId;
    // mark room as read for current user so unread_count updates
    // include CSRF token and notify the rooms list to refresh
    (async () => {
        try {
            const tokenEl = document.querySelector(
                'meta[name="csrf-token"]',
            ) as HTMLMetaElement | null;
            const token = tokenEl?.getAttribute('content') ?? '';
            const res = await fetch(`/rooms/${roomId}/read`, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': token,
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });
            if (!res.ok) {
                console.warn('[Chat] mark-as-read failed', res.status);
            } else {
                // notify rooms list to refresh counts
                window.dispatchEvent(new Event('rooms:updated'));
            }
        } catch (e) {
            console.error('[Chat] mark-as-read error', e);
        }
    })();
}

watch(selectedRoomId, (id) => {
    if (id) fetchAndSetRoomName(id);
    else setCurrentRoomName(null);
});

onMounted(() => {
    // prefer query param when present
    const params = new URLSearchParams(window.location.search);
    const room = params.get('room');
    if (room) {
        selectedRoomId.value = Number(room);
    }
});
</script>

<style scoped>
.chat-app {
    display: flex;
    height: calc(100vh - 64px);
}
.chat-sidebar {
    width: 450px;
    border-right: 1px solid var(--color-border);
}
.chat-main {
    flex: 1;
    display: flex;
}
</style>
