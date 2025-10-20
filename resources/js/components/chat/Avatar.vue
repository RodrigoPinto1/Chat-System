<template>
  <!-- Simple avatar component with fixed size to avoid layout shifts -->
  <img
    :src="props.src || fallback"
    :alt="props.alt"
    class="rounded-full"
    :style="sizeStyleAny"
    loading="lazy"
  />
</template>

<script setup lang="ts">
import { computed } from 'vue';

const props = defineProps({
  src: String,
  alt: { type: String, default: 'Avatar' },
  size: { type: Number, default: 32 },
});

const fallback = 'https://assets.edlin.app/images/rossedlin/03/rossedlin-03-100.jpg';

const sizeStyle = computed(() => ({ width: `${props.size}px`, height: `${props.size}px`, objectFit: 'cover' }));
// Cast here in script to avoid using `as` expressions in the template
const sizeStyleAny: any = sizeStyle;
</script>

<style scoped>
/* Tailwind-like utility classes are used in the app; keep scope minimal */
</style>
