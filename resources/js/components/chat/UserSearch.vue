<template>
  <div class="user-search relative w-64">
  <div class="flex items-center bg-white rounded-full border px-3 py-2 shadow-sm focus-within:ring-2 focus-within:ring-blue-300">
      <svg class="w-5 h-5 text-gray-400 mr-2" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
        <input v-model="query" @input="onInput" @keydown.enter.prevent="trySelectByName" placeholder="Buscar user..." class="flex-1 bg-transparent outline-none text-sm" />
    </div>
    <transition name="fade">
      <ul v-if="results.length && showResults" class="dropdown bg-white border rounded-lg shadow-lg mt-2 absolute w-full z-10">
        <li v-for="user in results" :key="user.id" @click="selectUser(user)" class="flex items-center px-3 py-2 cursor-pointer hover:bg-blue-50 transition">
          <Avatar class="w-7 h-7 rounded-full mr-2 border">
            <AvatarImage v-if="user.avatar" :src="user.avatar" />
            <AvatarFallback class="font-medium text-sm text-black">{{ getInitials(user.name) }}</AvatarFallback>
          </Avatar>
          <span class="font-medium text-gray-800">{{ user.name }}</span>
        </li>
      </ul>
    </transition>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { useInitials } from '@/composables/useInitials';
const emit = defineEmits(['select', 'error']);
const query = ref('');
interface UserResult { id: number; name: string; avatar?: string }
const results = ref<UserResult[]>([]);
const showResults = ref(false);
const { getInitials } = useInitials();

async function onInput() {
  showResults.value = !!query.value;
  if (!query.value) {
    results.value = [];
    return;
  }
  const res = await fetch(`/users/search?name=${encodeURIComponent(query.value)}`, {
    credentials: 'same-origin',
    headers: { Accept: 'application/json' },
  });
  if (res.ok) {
    results.value = await res.json();
  }
}

function selectUser(user: any) {
  emit('select', user);
  query.value = user.name;
  showResults.value = false;
}

function trySelectByName() {
  if (!query.value) return;
  const matches = results.value.filter(u => u.name.toLowerCase() === query.value.toLowerCase());
  if (matches.length === 1) {
    selectUser(matches[0]);
  } else if (matches.length > 1) {
    emit('error', 'Selecione o usuário na lista.');
  } else {
    emit('error', 'Usuário não encontrado.');
  }
}
</script>

<style scoped>
.user-search { font-family: inherit; }
.user-search input { min-width: 0; }
.dropdown { list-style: none; margin: 0; padding: 0; }
.dropdown li { border-bottom: 1px solid #f3f3f3; }
.dropdown li:last-child { border-bottom: none; }
.fade-enter-active, .fade-leave-active { transition: opacity 0.2s; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
</style>
