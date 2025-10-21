<template>
    <div class="rooms-list p-4">
        <div class="mb-4 flex items-center justify-between">
            <h3 class="text-lg font-semibold">Salas</h3>
            <button class="btn btn-sm" @click="showForm = true">Nova</button>
        </div>
        <ul>
            <li v-for="room in rooms" :key="room.id" class="border-b py-2 cursor-pointer" @click="$emit('select', room.id)">
                <div class="flex items-center gap-3">
                    <img :src="room.avatar" class="h-10 w-10 rounded-full" />
                    <div>
                        <div class="font-medium">{{ room.name }}</div>
                        <div class="text-sm text-muted">
                            {{ room.reference }}
                        </div>
                    </div>
                </div>
            </li>
        </ul>
        <div v-if="showForm" class="mt-4 p-4 bg-card rounded shadow">
            <form @submit.prevent="createRoom">
                <div class="mb-2">
                    <input v-model="newRoom.name" placeholder="Nome da sala" class="input input-bordered w-full" />
                </div>
                <div class="mb-2">
                    <input v-model="newRoom.reference" placeholder="Referência única" class="input input-bordered w-full" />
                </div>
                <div class="mb-2">
                    <input v-model="newRoom.avatar" placeholder="URL do avatar (opcional)" class="input input-bordered w-full" />
                </div>
                <div class="flex gap-2">
                    <button class="btn btn-primary" type="submit">Criar</button>
                    <button class="btn btn-secondary" type="button" @click="showForm = false">Cancelar</button>
                </div>
                <div v-if="error" class="text-red-500 mt-2">{{ error }}</div>
            </form>
        </div>
    </div>
</template>

<script setup lang="ts">
import { onMounted, ref } from 'vue';

const rooms = ref([] as any[]);
const showForm = ref(false);
const newRoom = ref({ name: '', reference: '', avatar: '' });
const error = ref('');

async function fetchRooms() {
    const res = await fetch('/rooms', { credentials: 'same-origin', headers: { Accept: 'application/json' } });
    if (res.ok) {
        rooms.value = await res.json();
    }
}

onMounted(fetchRooms);

async function createRoom() {
    error.value = '';
    try {
        const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
        const res = await fetch('/rooms', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': token,
                Accept: 'application/json',
            },
            body: JSON.stringify(newRoom.value),
        });
        if (!res.ok) {
            const body = await res.json();
            error.value = body.message || 'Erro ao criar sala.';
            return;
        }
        const room = await res.json();
        rooms.value.push(room);
        showForm.value = false;
        newRoom.value = { name: '', reference: '', avatar: '' };
    } catch (e) {
        error.value = 'Erro de rede.';
    }
}
</script>

<style scoped>
.rooms-list {
    background: var(--card-bg);
    height: 100%;
    overflow: auto;
}
.input { padding: 0.5em; border: 1px solid var(--color-border); border-radius: 4px; }
.bg-card { background: var(--card-bg); }
</style>
