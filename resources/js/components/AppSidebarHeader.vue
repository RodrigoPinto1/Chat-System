<script setup lang="ts">
import Breadcrumbs from '@/components/Breadcrumbs.vue';
import { SidebarTrigger } from '@/components/ui/sidebar';
import type { BreadcrumbItemType } from '@/types';

import UserSearch from '@/components/chat/UserSearch.vue';
import { inviteSearchVisible, hideInviteSearch } from '@/stores/ui';

function onHeaderUserSelect(user: any) {
    console.log('[AppSidebarHeader] user selected from header search', user);
    // forward to any listeners (ChatPanel listens for this)
    window.dispatchEvent(new CustomEvent('invite:selected', { detail: { user } }));
    // hide the search UI after selecting
    try { hideInviteSearch(); } catch (e) { inviteSearchVisible.value = false; }
}

import { onMounted, watch } from 'vue';

onMounted(() => {
    console.log('[AppSidebarHeader] mounted, inviteSearchVisible initial =', inviteSearchVisible.value);
    // expose to window for debugging
    try { (window as any).__inviteSearchVisible = inviteSearchVisible; } catch (e) {}
});

watch(inviteSearchVisible, (v) => {
    console.log('[AppSidebarHeader] inviteSearchVisible changed ->', v);
});

withDefaults(
    defineProps<{
        breadcrumbs?: BreadcrumbItemType[];
    }>(),
    {
        breadcrumbs: () => [],
    },
);
</script>

<template>
    <header
        class="flex h-16 shrink-0 items-center gap-2 border-b border-sidebar-border/70 px-6 transition-[width,height] ease-linear group-has-data-[collapsible=icon]/sidebar-wrapper:h-12 md:px-4"
    >
        <div class="flex items-center gap-2">
            <SidebarTrigger class="-ml-1" />
            <template v-if="breadcrumbs && breadcrumbs.length > 0">
                <Breadcrumbs :breadcrumbs="breadcrumbs" />
            </template>
            <!-- Centered search for chat navbar -->
        </div>
        <div class="flex-1 flex items-center justify-center">
            <transition name="search" appear>
                <div v-if="inviteSearchVisible" class="w-96">
                    <UserSearch @select="onHeaderUserSelect" @error="(e) => console.error('Invite search error', e)" />
                </div>
            </transition>
        </div>
    </header>
</template>

<style scoped>
.search-enter-active, .search-leave-active {
    transition: opacity 200ms ease, transform 200ms ease;
}
.search-enter-from, .search-leave-to {
    opacity: 0;
    transform: translateY(-6px) scale(0.99);
}
.search-enter-to, .search-leave-from {
    opacity: 1;
    transform: translateY(0) scale(1);
}
</style>
