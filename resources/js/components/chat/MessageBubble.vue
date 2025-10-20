
<template>
  <div class="message-bubble">
    <!-- Show content and small failed indicator when present -->
    <div>{{ message?.content }}</div>
    <div v-if="message?._failed" class="text-red-500 text-xs">(failed)</div>
  </div>
</template>

<script setup lang="ts">
const props = defineProps<{ message: any }>();
const { message } = props as any;
</script>

<style scoped>
.message-bubble { white-space: pre-wrap; }
</style>
