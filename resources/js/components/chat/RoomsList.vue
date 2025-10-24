<template>
    <div class="rooms-list p-4">
        <div class="mb-4 flex items-center justify-between">
            <h3 class="text-lg font-semibold">Salas</h3>
            <div class="text-sm text-gray-500">
                <span v-if="refreshing"></span>
            </div>
            <div class="flex items-center gap-4" style="min-width: 220px">
                <!-- Search input -->
                <div class="relative" style="min-width: 180px">
                    <input
                        v-model="searchQuery"
                        @input="onSearchInput"
                        placeholder="Procurar salas..."
                        class="input h-8 w-full pr-8 pl-3"
                    />
                    <svg
                        class="absolute top-1/2 right-2 size-4 -translate-y-1/2 text-gray-400"
                        xmlns="http://www.w3.org/2000/svg"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke-width="1.5"
                        stroke="currentColor"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"
                        />
                    </svg>
                </div>

                <!-- Filter dropdown -->
                <div class="relative">
                    <button class="btn btn-sm" @click="toggleFilterMenu">
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke-width="1.5"
                            stroke="currentColor"
                            class="size-5"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M3 5h18M3 12h18M3 19h18"
                            />
                        </svg>
                    </button>
                    <div
                        v-if="showFilterMenu"
                        class="absolute right-0 z-20 mt-2 w-40 rounded border bg-white shadow-md"
                    >
                        <ul>
                            <li
                                class="cursor-pointer px-3 py-2 text-black hover:bg-gray-100"
                                @click="setFilter('all')"
                            >
                                All rooms
                            </li>
                            <li
                                class="cursor-pointer px-3 py-2 text-black hover:bg-gray-100"
                                @click="setFilter('owned')"
                            >
                                Only my rooms
                            </li>
                            <li
                                class="cursor-pointer px-3 py-2 text-black hover:bg-gray-100"
                                @click="setFilter('unread')"
                            >
                                Unread only
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Add -->
                <button class="btn btn-sm" @click="goCreate">
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke-width="1.5"
                        stroke="currentColor"
                        class="size-5"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M12 4.5v15m7.5-7.5h-15"
                        />
                    </svg>
                </button>
            </div>
        </div>

        <div>
            <!-- TODO: Implement filter functionality -->
            <div class="flex gap-5 mb-6 mt-6">
                <div class="active border border-gray-300 rounded px-3 py-1 cursor-pointer" @click="goFilter()">
                    <input type="button" value="Grupos"></input>
                </div>
                <div class="border border-gray-300 rounded px-3 py-1 cursor-pointer" @click="goFilter()">
                    <input type="button" value="Privados"></input>
                </div>
            </div>

            <div v-if="loading" class="py-4 text-center text-sm text-gray-500">
                Carregando salas...
            </div>
            <div
                v-else-if="errorMsg"
                class="py-4 text-center text-sm text-red-500"
            >
                {{ errorMsg }}
            </div>
            <div
                v-else-if="filteredRooms.length === 0"
                class="py-4 text-center text-sm text-gray-500"
            >
                Nenhuma sala encontrada.
            </div>
            <ul v-else>
                <li
                    v-for="room in filteredRooms"
                    :key="room.id"
                    class="cursor-pointer border-b py-2"
                    @click="emit('select', room.id)"
                >
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <img
                                :src="room.avatar"
                                class="h-10 w-10 rounded-full"
                            />
                            <div>
                                <div class="font-medium">{{ room.name }}</div>
                                <div class="text-sm text-muted">
                                    {{ room.reference }}
                                </div>
                            </div>
                        </div>

                        <div class="ml-4 flex items-center">
                            <span
                                v-if="Number(room.unread_count) > 0"
                                class="rounded bg-orange-500 px-2 py-0.5 text-xs text-white"
                                >{{ room.unread_count }}</span
                            >
                        </div>
                    </div>
                </li>
            </ul>
        </div>

        <!-- create form moved to dedicated page -->
    </div>
</template>

<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { computed, onMounted, onUnmounted, ref } from 'vue';

const rooms = ref([] as any[]);
const loading = ref(false);
const initialLoad = ref(true);
const refreshing = ref(false);
const errorMsg = ref('');
const isFetching = ref(false);
const emit = defineEmits(['select', 'rooms-loaded']);
const searchQuery = ref('');
const showFilterMenu = ref(false);
const activeFilter = ref<'all' | 'owned' | 'unread'>('all');
const page: any = usePage();
const authUserId = page.props.value?.auth?.user?.id ?? null;

const filteredRooms = computed(() => {
    const q = String(searchQuery.value || '')
        .trim()
        .toLowerCase();
    return rooms.value.filter((r: any) => {
        // filter by activeFilter
        if (activeFilter.value === 'owned') {
            // backend provides is_owner boolean per room (true when user is owner)
            if (!r.is_owner) return false;
        }
        if (activeFilter.value === 'unread') {
            if (!(Number(r.unread_count) > 0)) return false;
        }
        if (!q) return true;
        return (
            (r.name || '').toLowerCase().includes(q) ||
            (r.reference || '').toLowerCase().includes(q)
        );
    });
});

async function fetchRooms() {
    // avoid overlapping fetches (polling may trigger while a previous fetch hasn't finished)
    if (isFetching.value) return;
    isFetching.value = true;
    // Show initial loader only on first load. Subsequent poll refreshes show a small 'refreshing' tag.
    if (initialLoad.value) {
        loading.value = true;
    } else {
        refreshing.value = true;
    }
    errorMsg.value = '';
    try {
        const controller = new AbortController();
        const timeoutId = window.setTimeout(() => controller.abort(), 8000); // 8s timeout
        const res = await fetch('/rooms', {
            credentials: 'same-origin',
            headers: { Accept: 'application/json' },
            signal: controller.signal,
        });
        clearTimeout(timeoutId);
        if (!res.ok) {
            const text = await res.text();
            console.error('[RoomsList] /rooms fetch failed', res.status, text);
            errorMsg.value =
                res.status === 401
                    ? 'Você precisa fazer login.'
                    : 'Erro ao carregar salas.';
            // preserve rooms.value so we don't flash empty list on refresh failure
        } else {
            rooms.value = await res.json();
            console.debug('[RoomsList] fetched rooms', rooms.value);
            emit('rooms-loaded', rooms.value);
            if (!rooms.value || rooms.value.length === 0) {
                // Empty but not an error
                console.info('[RoomsList] no rooms available for this user');
            }
            if (initialLoad.value) initialLoad.value = false;
        }
    } catch (e) {
        console.error('[RoomsList] fetch error', e);
        errorMsg.value = 'Erro de rede ao carregar salas.';
        rooms.value = [];
    } finally {
        loading.value = false;
        refreshing.value = false;
        isFetching.value = false;
    }
}

let searchTimeout: number | undefined;
function onSearchInput() {
    // debounce local filtering; still fetch to keep data fresh every few seconds
    if (searchTimeout) clearTimeout(searchTimeout);
    searchTimeout = window.setTimeout(() => {
        // no network call required; filteredRooms will update automatically
        searchTimeout = undefined;
    }, 250);
}

function toggleFilterMenu() {
    showFilterMenu.value = !showFilterMenu.value;
}

function setFilter(f: 'all' | 'owned' | 'unread') {
    activeFilter.value = f;
    showFilterMenu.value = false;
}

onMounted(() => {
    fetchRooms();
    // poll every 5s so invited users see new rooms
    const poll = window.setInterval(() => fetchRooms(), 5000);
    window.addEventListener('rooms:updated', fetchRooms as EventListener);
    onUnmounted(() => {
        clearInterval(poll);
        window.removeEventListener(
            'rooms:updated',
            fetchRooms as EventListener,
        );
    });
});
function goCreate() {
    window.location.href = '/rooms/create';
}

function goFilter() {
    // keep compatibility: open filter menu
    toggleFilterMenu();
}
</script>

<style scoped>
.rooms-list {
    background: var(--card-bg);
    height: 100%;
    overflow: auto;
}
.input {
    padding: 0.5em;
    border: 1px solid var(--color-border);
    border-radius: 4px;
}
.bg-card {
    background: var(--card-bg);
}
</style>
