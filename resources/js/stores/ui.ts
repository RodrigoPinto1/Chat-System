import { ref } from 'vue';

// Simple shared UI state used across header and chat panel
export const currentRoomName = ref<string | null>(null);
export const inviteSearchVisible = ref<boolean>(false);

export function showInviteSearch() {
  inviteSearchVisible.value = true;
}

export function hideInviteSearch() {
  inviteSearchVisible.value = false;
}

export function setCurrentRoomName(name: string | null) {
  currentRoomName.value = name;
}
