<template>
    <div class="rooms-list p-4">
        <div class="mb-4 flex items-center justify-between">
            <h3 class="text-lg font-semibold">Salas</h3>
            <button class="btn btn-sm">Nova</button>
        </div>
        <ul>
            <li v-for="room in rooms" :key="room.id" class="border-b py-2">
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
    </div>
</template>

<script setup lang="ts">
import { onMounted, ref } from 'vue';

const rooms = ref([] as any[]);

onMounted(async () => {
    // TODO: fetch rooms via API
    rooms.value = [
        {
            id: 1,
            name: 'General',
            reference: 'general',
            avatar: 'https://api.dicebear.com/6.x/bottts/svg?seed=general',
        },
        {
            id: 2,
            name: 'Design',
            reference: 'design',
            avatar: 'https://api.dicebear.com/6.x/bottts/svg?seed=design',
        },
    ];
});
</script>

<style scoped>
.rooms-list {
    background: var(--card-bg);
    height: 100%;
    overflow: auto;
}
</style>
