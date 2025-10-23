<script setup lang="ts">
import { ref, onMounted } from 'vue';
import ChatPanel from '@/components/chat/ChatPanel.vue';
import RoomsList from '@/components/chat/RoomsList.vue';
import AppLayout from '@/layouts/AppLayout.vue';

const selectedRoomId = ref<number|null>(null); // null means no room selected

function handleRoomSelect(roomId: number) {
    selectedRoomId.value = roomId;
}

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
    width: 320px;
    border-right: 1px solid var(--color-border);
}
.chat-main {
    flex: 1;
    display: flex;
}
</style>
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
                        <div class="flex flex-col items-center justify-center w-full h-full text-center p-12">
                            <h1 class="text-4xl font-bold mb-4">Bem-vindo ao Chat!</h1>
                            <p class="text-lg text-muted mb-6">Selecione uma sala à esquerda para começar a conversar.<br>Crie uma nova sala para iniciar um chat com seus amigos.</p>
                            <img src="/favicon.svg" alt="Chat Hero" class="w-32 h-32 mb-6 opacity-80" />
                        </div>
                    </template>
                </div>
            </div>
        </template>
    </AppLayout>
</template>

