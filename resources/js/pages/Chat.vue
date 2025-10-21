<script setup lang="ts">
import { ref, onMounted } from 'vue';
import ChatPanel from '@/components/chat/ChatPanel.vue';
import RoomsList from '@/components/chat/RoomsList.vue';
import AppLayout from '@/layouts/AppLayout.vue';

const ROOM_KEY = 'selectedRoomId';
const selectedRoomId = ref(1); // fallback default

onMounted(() => {
    const stored = localStorage.getItem(ROOM_KEY);
    if (stored) {
        selectedRoomId.value = Number(stored);
    }
});

function handleRoomSelect(roomId: number) {
    selectedRoomId.value = roomId;
    localStorage.setItem(ROOM_KEY, String(roomId));
}
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
                    <ChatPanel :room-id="selectedRoomId" />
                </div>
            </div>
        </template>
    </AppLayout>
</template>

