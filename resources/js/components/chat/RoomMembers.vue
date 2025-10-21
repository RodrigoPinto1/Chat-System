<template>
  <div class="relative">
    <button class="btn btn-xs" @click="show = !show">Membros</button>
    <div v-if="show" class="absolute right-0 mt-2 w-64 bg-white border rounded shadow-lg z-20">
      <div v-if="members.length === 0" class="p-4 text-center text-muted">
        Nenhum membro
        <div class="text-xs text-gray-400 mt-2">roomId: {{ props.roomId }}</div>
        <div class="text-xs text-gray-400 mt-1">members: {{ members }}</div>
      </div>
      <ul v-else>
        <li v-for="member in members" :key="member.id" class="flex items-center gap-2 px-3 py-2 border-b last:border-b-0">
          <img :src="member.avatar || defaultAvatar" class="w-7 h-7 rounded-full border" />
          <span class="font-medium text-black">{{ member.name }}</span>
          <span class="ml-auto px-2 py-1 rounded text-xs" :class="roleClass(member.role)">{{ member.role }}</span>
        </li>
      </ul>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, watch, onMounted } from 'vue';

export interface RoomMember {
  id: number;
  name: string;
  avatar?: string;
  role: string;
}

const props = defineProps<{ roomId: number }>();
const show = ref(false);
const members = ref<RoomMember[]>([]);
const defaultAvatar = 'https://ui-avatars.com/api/?name=User&background=eee&color=555';

function roleClass(role: string) {
  if (role === 'owner') return 'bg-yellow-100 text-yellow-800';
  if (role === 'admin') return 'bg-blue-100 text-blue-800';
  return 'bg-gray-100 text-gray-700';
}


function fetchMembers(roomId: number) {
  show.value = false;
  if (roomId) {
    console.log('[RoomMembers] Fetching members for roomId:', roomId);
    fetch(`/rooms/${roomId}/members`, {
      credentials: 'same-origin',
      headers: { Accept: 'application/json' },
    })
      .then(async (res) => {
        if (res.ok) {
          members.value = await res.json();
          console.log('[RoomMembers] Fetched members:', members.value);
        } else {
          members.value = [];
          console.log('[RoomMembers] Failed to fetch members, status:', res.status);
        }
      })
      .catch((err) => {
        members.value = [];
        console.log('[RoomMembers] Fetch error:', err);
      });
  } else {
    members.value = [];
    console.log('[RoomMembers] No roomId provided');
  }
}

onMounted(() => {
  fetchMembers(props.roomId);
});

watch(() => props.roomId, (roomId) => {
  fetchMembers(roomId);
});
</script>

<style scoped>
.btn { cursor: pointer; }
</style>
