<template>
    <div class="rooms-list p-4">
        <div class="mb-4 flex items-center justify-between">
            <h3 class="text-lg font-semibold">Salas</h3>
            <button class="btn btn-sm" @click="showForm = true">Nova</button>
        </div>
        <ul>
            <li v-for="room in rooms" :key="room.id" class="border-b py-2 cursor-pointer" @click="emit('select', room.id)">
                <div class="flex items-center gap-3">
                    <img :src="room.avatar" class="h-10 w-10 rounded-full" />
                    <div>
                        <div class="font-medium">{{ room.name }}</div>
                        <div class="text-sm text-muted">{{ room.reference }}</div>
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
                <div class="mb-2 ">
                    <div class="flex items-center gap-2">
                        <input ref="roomFileInput" type="file" accept="image/*" class="hidden" @change="onRoomFileChange" />
                        <button type="button" class="btn btn-ghost p-1" @click="triggerRoomFileInput" title="Escolher imagem">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-gray-500 hover:text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l7.071-7.071a4 4 0 00-5.657-5.657l-7.071 7.07a6 6 0 108.485 8.486l6.364-6.364" />
                            </svg>
                        </button>
                        <div class="text-sm text-muted">Selecione uma imagem</div>
                    </div>
                    <div v-if="avatarPreview" class="mt-2 flex items-center gap-3 bg-gray-50 border rounded p-2">
                        <img :src="avatarPreview" class="h-12 w-12 rounded-full object-cover" />
                        <div class="flex-1 text-sm text-black">Imagem selecionada </div>
                        <button type="button" class="btn btn-xs btn-outline text-red-600" @click="removeAvatar">Remover</button>
                    </div>
                </div>
                <div class="flex gap-2">
                    <button class="btn btn-primary" type="submit">Criar</button>
                    <button class="btn btn-secondary" type="button" @click="showForm = false">Cancelar</button>
                </div>
                <div v-if="error" class="mt-2">
                    <div class="space-y-2 rounded-lg border border-red-100 bg-red-50 p-3">
                        <div class="flex items-start gap-2 text-red-700">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M8.257 3.099c.766-1.36 2.72-1.36 3.486 0l5.516 9.8c.75 1.332-.213 2.999-1.742 2.999H4.483c-1.53 0-2.492-1.667-1.742-2.999l5.516-9.8zM11 13a1 1 0 10-2 0 1 1 0 002 0zm-1-6a1 1 0 00-.993.883L9 8v3a1 1 0 001.993.117L11 11V8a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                            <div>
                                <div class="font-medium">Erro</div>
                                <div class="text-sm">{{ error }}</div>
                            </div>
                        </div>
                    </div>
                </div>
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
        emit('rooms-loaded', rooms.value);
    }
}

const emit = defineEmits(['select', 'rooms-loaded']);

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

// file picker for avatar replacement
const roomFileInput = ref<HTMLInputElement | null>(null);
const avatarPreview = ref<string | null>(null);

function triggerRoomFileInput() {
    roomFileInput.value?.click();
}

function onRoomFileChange(e: Event) {
    const t = e.target as HTMLInputElement;
    if (!t.files || t.files.length === 0) return;
    const f = t.files[0];
    if (!f.type.startsWith('image/')) return;
    const reader = new FileReader();
    reader.onload = () => {
        avatarPreview.value = String(reader.result || '');
        newRoom.value.avatar = avatarPreview.value || '';
    };
    reader.readAsDataURL(f);
}

function removeAvatar() {
    avatarPreview.value = null;
    newRoom.value.avatar = '';
    if (roomFileInput.value) roomFileInput.value.value = '';
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
