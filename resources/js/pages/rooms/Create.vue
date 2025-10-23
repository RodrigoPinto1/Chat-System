<template>
  <div class="p-6 max-w-3xl mx-auto">
    <h1 class="text-2xl font-semibold mb-4">Criar nova sala</h1>

    <div class="bg-card rounded p-4">
  <form action="/rooms" method="POST" class="grid gap-4" data-inertia="false">
        <input type="hidden" name="_token" :value="csrf" />
        <input type="hidden" name="avatar" :value="newRoom.avatar" />
        <div class="flex items-center gap-4">
          <div class="h-16 w-16 rounded-full overflow-hidden bg-gray-100 flex items-center justify-center">
            <img v-if="avatarPreview" :src="avatarPreview" class="h-full w-full object-cover" />
            <div v-else class="font-medium text-lg text-black">{{ newRoom.name ? initials : '+' }}</div>
          </div>
          <div class="flex-1 grid gap-2">
            <input name="name" v-model="newRoom.name" ref="nameInput" placeholder="Nome da sala" class="input input-bordered w-full text-white bg-transparent placeholder-gray-300" />
            <input name="reference" v-model="newRoom.reference" placeholder="Referência única" class="input input-bordered w-full text-white bg-transparent placeholder-gray-300" />
            <div class="flex items-center gap-2">
              <input ref="fileInput" type="file" accept="image/*" class="hidden" @change="onFileChange" />
              <button type="button" class="btn btn-ghost" @click="triggerFileInput">Escolher imagem</button>
              <div v-if="avatarPreview" class="text-sm text-white">Imagem pronta</div>
            </div>
          </div>
        </div>

        <div class="flex gap-3 justify-end">
          <button class="btn btn-ghost px-4 py-2 rounded-md border border-gray-700 text-white hover:bg-gray-800" type="button" @click="cancel">Cancelar</button>
          <button class="btn px-4 py-2 rounded-md shadow-sm bg-linear-to-br from-blue-600 to-blue-500 hover:from-blue-700 hover:to-blue-600 text-white font-semibold" type="submit">Criar sala</button>
        </div>

        <div v-if="error" class="text-sm text-red-600">{{ error }}</div>
      </form>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { useInitials } from '@/composables/useInitials';

const newRoom = ref({ name: '', reference: '', avatar: '' });
const avatarPreview = ref<string | null>(null);
const fileInput = ref<HTMLInputElement | null>(null);
const nameInput = ref<HTMLInputElement | null>(null);
const error = ref('');
const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

const { getInitials } = useInitials();
const initials = computed(() => getInitials(newRoom.value.name || ''));

onMounted(() => {
  // focus name input for faster UX
  nameInput.value?.focus();
});

function triggerFileInput() {
  fileInput.value?.click();
}

function onFileChange(e: Event) {
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

// Using normal form submit (no JS) so server-side redirect is followed. Keep JS just to populate avatar hidden input.

function cancel() {
  history.back();
}
</script>

<style scoped>
.input { padding: 0.5em; border: 1px solid var(--color-border); border-radius: 4px; }
.bg-card { background: var(--card-bg); }
</style>
