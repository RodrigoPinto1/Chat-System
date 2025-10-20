<template>
    <AppLayout>
        <template #default>
            <div class="chat-app">
                <div class="chat-sidebar">
                    <RoomsList />
                </div>
                <div class="chat-main">
                    <ChatPanel />
                </div>
            </div>
        </template>
    </AppLayout>
</template>

<script setup lang="ts">
import ChatPanel from '@/components/chat/ChatPanel.vue';
import RoomsList from '@/components/chat/RoomsList.vue';
import AppLayout from '@/layouts/AppLayout.vue';
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
