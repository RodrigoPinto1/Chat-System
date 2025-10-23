<template>
    <div class="rooms-list p-4">
        <div class="mb-4 flex items-center justify-between">
            <h3 class="text-lg font-semibold">Salas</h3>
            <button class="btn btn-sm" @click="goCreate">Nova</button>
        </div>

        <ul>
            <li
                v-for="room in rooms"
                :key="room.id"
                class="border-b py-2 cursor-pointer"
                @click="emit('select', room.id)"
            >
                <div class="flex items-center gap-3">
                    <img :src="room.avatar" class="h-10 w-10 rounded-full" />
                    <div>
                        <div class="font-medium">{{ room.name }}</div>
                        <div class="text-sm text-muted">{{ room.reference }}</div>
                    </div>
                </div>
            </li>
        </ul>

        <!-- create form moved to dedicated page -->
    </div>
</template>

<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue';

const rooms = ref([] as any[]);
const emit = defineEmits(['select', 'rooms-loaded']);

async function fetchRooms() {
    const res = await fetch('/rooms', { credentials: 'same-origin', headers: { Accept: 'application/json' } });
    if (res.ok) {
        rooms.value = await res.json();
        emit('rooms-loaded', rooms.value);
    }
}

onMounted(() => {
    fetchRooms();
    // poll every 5s so invited users see new rooms
    const poll = window.setInterval(() => fetchRooms(), 5000);
    window.addEventListener('rooms:updated', fetchRooms as EventListener);
    onUnmounted(() => {
        clearInterval(poll);
        window.removeEventListener('rooms:updated', fetchRooms as EventListener);
    });
});
function goCreate() {
    window.location.href = '/rooms/create';
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
            }
