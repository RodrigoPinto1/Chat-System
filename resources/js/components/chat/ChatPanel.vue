<template>
    <div class="chat-panel flex flex-col p-4" style="width: 100%">
        <div class="messages mb-4 flex-1 overflow-auto">
            <div v-for="m in messages" :key="m.id" class="mb-3">
                <div class="text-sm text-muted">{{ m.user_name }}</div>
                <div class="rounded bg-card p-2">{{ m.content }}</div>
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
import { onMounted, ref } from 'vue';

const messages = ref([] as any[]);
const text = ref('');

onMounted(() => {
    messages.value = [
        { id: 1, user_name: 'Admin User', content: 'Bem-vindo ao chat!' },
        { id: 2, user_name: 'Test User', content: 'Olá a todos' },
    ];
});

function send() {
    if (!text.value.trim()) return;
    messages.value.push({
        id: Date.now(),
        user_name: 'Tu',
        content: text.value,
    });
    text.value = '';
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
