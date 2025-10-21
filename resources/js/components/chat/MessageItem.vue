<template>
  <div :class="['mb-3', isRight ? 'flex justify-end' : 'flex justify-start']">
    <div v-if="isRight" class="flex flex-col items-end gap-2">
      <div class="text-sm text-color-white">{{ isRight ? (currentUserName || 'You') : (message.user?.name || message.user_name) }}</div>
      <div class="flex items-end gap-2">
  <MessageBubble :message="message" />
  <Avatar :src="message.user?.avatar" :alt="message.user?.name || 'Avatar'" :size="32" />
      </div>
    </div>
    <div v-else class="flex items-start gap-2">
  <Avatar :src="message.user?.avatar" :alt="message.user?.name || 'Avatar'" :size="32" />
      <div>
        <div class="text-sm text-color-white">{{ message.user?.name || message.user_name }}</div>
        <MessageBubble :message="message" />
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import Avatar from './Avatar.vue';
import MessageBubble from './MessageBubble.vue';

const props = defineProps<{ message: any; currentUserId: string; currentUserName?: string }>();
const { message, currentUserId, currentUserName } = props as any;

// Decide side: always compare senderId and currentUserId as strings
const senderId = message?.user?.id ?? message?.user_id ?? message?.sender_id ?? null;
const isRight = String(senderId) === currentUserId || message?._pending === true || message?._forceMine === true;
</script>

<style scoped>
.text-color-white { color: var(--color-muted); }
</style>
