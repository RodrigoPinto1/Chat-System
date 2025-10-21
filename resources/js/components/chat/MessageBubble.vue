
<template>
  <div class="message-bubble">
    <template v-if="isImage">
      <img :src="message.content" alt="imagem" class="max-w-xs max-h-60 rounded border" />
      <div v-if="message.meta && message.meta.text" class="mt-1">{{ message.meta.text }}</div>
    </template>
    <template v-else-if="isFile">
      <a :href="message.content" target="_blank" class="text-blue-600 underline">Arquivo enviado</a>
      <div v-if="message.meta && message.meta.text" class="mt-1">{{ message.meta.text }}</div>
    </template>
    <template v-else>
      <div>{{ message?.content }}</div>
    </template>
    <div v-if="message?._failed" class="text-red-500 text-xs">(failed)</div>
  </div>
</template>

<script setup lang="ts">
const props = defineProps<{ message: any }>();
const { message } = props as any;

const isImage = message?.type && message.type.startsWith('image/');
const isFile = message?.type && !isImage && message.type !== undefined;
</script>

<style scoped>
.message-bubble { white-space: pre-wrap; }
</style>
