<template>
  <div>
    <div v-if="filePreview || selectedFile" class="mb-2 flex items-center gap-3 bg-gray-50 border rounded p-2">
      <template v-if="isImagePreview && filePreview">
        <img :src="filePreview" alt="preview" class="max-h-24 max-w-xs rounded border" />
      </template>
      <template v-else>
        <span class="text-gray-700">{{ selectedFile?.name }}</span>
      </template>
      <button type="button" class="ml-2 text-red-500 hover:text-red-700" @click="removeFile" title="Remove file">
        &times;
      </button>
    </div>

    <input ref="fileInput" type="file" class="hidden" @change="onFileChange" />
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue';

const props = defineProps<{ roomId: number }>();
const emit = defineEmits(['sent']);

const fileInput = ref<HTMLInputElement | null>(null);
const selectedFile = ref<File | null>(null);
const filePreview = ref<string | null>(null);
const isImagePreview = ref(false);

function triggerFileInput() {
  fileInput.value?.click();
}

function onFileChange(e: Event) {
  const target = e.target as HTMLInputElement;
  if (target.files && target.files.length > 0) {
    selectedFile.value = target.files[0];
    if (selectedFile.value.type.startsWith('image/')) {
      filePreview.value = URL.createObjectURL(selectedFile.value);
      isImagePreview.value = true;
    } else {
      filePreview.value = null;
      isImagePreview.value = false;
    }
  }
}

function removeFile() {
  selectedFile.value = null;
  filePreview.value = null;
  isImagePreview.value = false;
  if (fileInput.value) fileInput.value.value = '';
}

async function sendFile(text?: string) {
  if (!selectedFile.value || !props.roomId) return null;
  const formData = new FormData();
  formData.append('file', selectedFile.value);
  formData.append('room_id', String(props.roomId));
  if (text && text.trim()) {
    formData.append('meta', JSON.stringify({ text }));
  }
  const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
  formData.append('_token', token);

  try {
    const res = await fetch('/messages/file', {
      method: 'POST',
      credentials: 'same-origin',
      headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
      body: formData,
    });
    if (!res.ok) {
      console.error('File upload failed', res.status, await res.text());
      return null;
    }
    const saved = await res.json();
    emit('sent', saved);
    removeFile();
    return saved;
  } catch (e) {
    console.error('File upload error', e);
    return null;
  }
}

// expose sendFile and trigger so parent can call them
defineExpose({ sendFile, trigger: triggerFileInput });
</script>

<style scoped>
</style>
