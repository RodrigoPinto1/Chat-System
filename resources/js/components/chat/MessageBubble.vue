<template>
    <div class="message-bubble relative">
        <div
            v-if="isImportant"
            class="important-badge absolute"
            title="Importante"
            aria-hidden="true"
        >
            <svg
                xmlns="http://www.w3.org/2000/svg"
                viewBox="0 0 24 24"
                class="h-4 w-4 fill-current"
            >
                <path
                    d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"
                />
            </svg>
        </div>
        <template v-if="isImage">
            <img
                :src="message.content"
                alt="imagem"
                class="max-h-60 max-w-xs rounded border"
            />
            <div v-if="message.meta && message.meta.text" class="mt-1">
                {{ message.meta.text }}
            </div>
        </template>

        <template v-else-if="isAudio">
            <div
                class="audio-bubble flex items-center gap-3 rounded-lg border bg-black p-2 text-white shadow-sm"
            >
                <div class="flex items-center gap-2">
                    <button
                        class="play-ring relative flex items-center justify-center rounded-full bg-white shadow-sm"
                        @click="togglePlay"
                        @keydown.enter.prevent="togglePlay"
                        role="button"
                        :aria-pressed="isPlaying"
                        :title="isPlaying ? 'Pausar' : 'Tocar'"
                    >
                        <!-- circular progress ring -->
                        <svg viewBox="0 0 36 36" class="h-9 w-9">
                            <path
                                class="ring-bg"
                                d="M18 2.0845a15.9155 15.9155 0 1 0 0 31.831 15.9155 15.9155 0 1 0 0-31.831"
                                fill="none"
                                stroke="rgba(255,255,255,0.12)"
                                stroke-width="2"
                            />
                            <path
                                class="ring-fill"
                                d="M18 2.0845a15.9155 15.9155 0 1 0 0 31.831 15.9155 15.9155 0 1 0 0-31.831"
                                fill="none"
                                stroke="#ffffff"
                                stroke-width="2"
                                :stroke-dasharray="progressDash + ', 100'"
                                stroke-linecap="round"
                            />
                        </svg>
                        <div
                            class="pointer-events-none absolute inset-0 flex items-center justify-center"
                        >
                            <svg
                                v-if="!isPlaying"
                                xmlns="http://www.w3.org/2000/svg"
                                class="h-4 w-4 text-white"
                                viewBox="0 0 20 20"
                                fill="currentColor"
                            >
                                <path d="M6.5 5.5v9l7-4.5-7-4.5z" />
                            </svg>
                            <svg
                                v-else
                                xmlns="http://www.w3.org/2000/svg"
                                class="h-4 w-4 text-white"
                                viewBox="0 0 20 20"
                                fill="currentColor"
                            >
                                <path
                                    fill-rule="evenodd"
                                    d="M6 5h2v10H6V5zm6 0h2v10h-2V5z"
                                    clip-rule="evenodd"
                                />
                            </svg>
                        </div>
                    </button>
                </div>

                <div class="min-w-0 flex-1">
                    <div class="w-full">
                        <div
                            class="progress relative h-2 cursor-pointer overflow-hidden rounded bg-gray-100"
                            @click="seek($event)"
                        >
                            <div
                                class="progress-filled h-full bg-blue-500"
                                :style="{ width: progressPercent + '%' }"
                            ></div>
                        </div>
                        <div
                            class="mt-1 flex items-center justify-between text-xs text-gray-600"
                        >
                            <div class="truncate">{{ displayName }}</div>
                            <div class="ml-2">
                                {{ formattedCurrent }} / {{ formattedDuration }}
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <a
                        :href="message.content"
                        :download="downloadName"
                        class="rounded-md bg-transparent px-3 py-1 text-xl text-white hover:bg-white/5"
                        title="Download"
                        >⤓</a
                    >
                </div>
            </div>
            <!-- native audio element used for playback control (hidden) -->
            <audio
                ref="audioEl"
                :src="message.content"
                @timeupdate="onTimeUpdate"
                @loadedmetadata="onLoadedMetadata"
                @play="onPlay"
                @pause="onPause"
                style="display: none"
            ></audio>
            <div
                v-if="message.meta && message.meta.text"
                class="mt-1 text-sm text-gray-700"
            >
                {{ message.meta.text }}
            </div>
        </template>

        <template v-else-if="isFile">
            <a
                :href="message.content"
                target="_blank"
                class="text-blue-600 underline"
                >Arquivo enviado</a
            >
            <div v-if="message.meta && message.meta.text" class="mt-1">
                {{ message.meta.text }}
            </div>
        </template>

        <template v-else>
            <div
                :class="{ 'emoji-large': isEmojiOnly, 'message-text': true }"
                v-html="formattedContentHtml"
            ></div>
        </template>

        <div v-if="message?._failed" class="text-xs text-red-500">(failed)</div>
    </div>
</template>

<script setup lang="ts">
import { computed, onBeforeUnmount, ref } from 'vue';

const props = defineProps<{ message: any }>();
const { message } = props as any;

function looksLikeAudioUrl(url: string | null | undefined) {
    if (!url) return false;
    return /\.(mp3|wav|ogg|webm|m4a|aac)(\?.*)?$/i.test(url);
}

const isImage = message?.type && message.type.startsWith('image/');
const isAudio =
    (message?.type && message.type.startsWith('audio/')) ||
    !!message?.meta?.audio_path ||
    looksLikeAudioUrl(message?.content);
const isFile =
    message?.type && !isImage && !isAudio && message.type !== undefined;

// Audio player state
const audioEl = ref<HTMLAudioElement | null>(null);
const isPlaying = ref(false);
const current = ref(0);
const duration = ref(0);

function onTimeUpdate() {
    if (!audioEl.value) return;
    current.value = audioEl.value.currentTime || 0;
}

function onLoadedMetadata() {
    if (!audioEl.value) return;
    duration.value = audioEl.value.duration || 0;
}

function onPlay() {
    isPlaying.value = true;
}
function onPause() {
    isPlaying.value = false;
}

function togglePlay() {
    if (!audioEl.value) return;
    if (audioEl.value.paused) audioEl.value.play().catch(() => {});
    else audioEl.value.pause();
}

function seek(ev: MouseEvent) {
    if (!audioEl.value) return;
    const target = ev.currentTarget as HTMLElement;
    const rect = target.getBoundingClientRect();
    const x = ev.clientX - rect.left;
    const pct = Math.max(0, Math.min(1, x / rect.width));
    audioEl.value.currentTime = (audioEl.value.duration || 0) * pct;
}

const progressPercent = computed(() => {
    if (!duration.value || duration.value === 0) return 0;
    return Math.min(100, Math.round((current.value / duration.value) * 100));
});

function humanTime(s: number) {
    if (!s || !isFinite(s)) return '0:00';
    const m = Math.floor(s / 60);
    const sec = Math.floor(s % 60)
        .toString()
        .padStart(2, '0');
    return `${m}:${sec}`;
}

const formattedCurrent = computed(() => humanTime(current.value));
const formattedDuration = computed(() => humanTime(duration.value));

const displayName = computed(() => {
    if (message?.meta?.original_name) return message.meta.original_name;
    try {
        const url = String(message.content || '');
        const parts = url.split('/');
        return parts[parts.length - 1].split('?')[0] || 'audio';
    } catch (e) {
        return 'audio';
    }
});

const downloadName = computed(() => displayName.value || 'audio');

// detect whether the message content is composed only of emoji (or emoji sequences)
const isEmojiOnly = (() => {
    try {
        const txt = String(message?.content || '').trim();
        if (!txt) return false;
        // If contains letters or numbers, consider it not emoji-only
        if (/\p{Letter}|\p{Number}/u.test(txt)) return false;
        // Remove emoji codepoints and zero-width joiners / variation selectors; if nothing remains it's emoji-only
        const withoutEmoji = txt
            .replace(/\p{Extended_Pictographic}/gu, '')
            .replace(/[\uFE0F\u200D\s]/g, '');
        return withoutEmoji.length === 0;
    } catch (e) {
        return false;
    }
})();

// Render message content as HTML while safely converting simple BBCode-like tags.
// Specifically: [u]...[/u] -> <u>...</u>
const formattedContentHtml = computed(() => {
    try {
        const raw = String(message?.content ?? '');
        if (!raw) return '';
        // basic HTML escape
        const esc = (s: string) =>
            s
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#39;');

        const escaped = esc(raw);
        // replace [u]...[/u] with <u>...</u> (non-greedy, dotall)
        const withUnderline = escaped.replace(
            /\[u\]([\s\S]*?)\[\/u\]/gi,
            '<u>$1</u>',
        );
        // preserve newlines
        return withUnderline.replace(/\n/g, '<br>');
    } catch (e) {
        return String(message?.content ?? '');
    }
});

// important flag (can be persistent (message.important) or optimistic local (message._important))
const isImportant = computed(
    () => !!(message?._important || message?.important),
);

// progressDash maps current progressPercent to stroke-dasharray (0..100)
const progressDash = computed(() => {
    return String(progressPercent.value);
});

onBeforeUnmount(() => {
    if (audioEl.value) {
        try {
            audioEl.value.pause();
        } catch (e) {}
        audioEl.value.src = '';
    }
});
</script>

<style scoped>
.audio-bubble {
    max-width: 420px;
}
.progress {
    height: 8px;
}
.progress-filled {
    transition: width 0.15s linear;
    background: #fb923c;
}
.play-btn {
    width: 48px;
    height: 48px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 9999px;
}
.play-ring {
    width: 48px;
    height: 48px;
    border-radius: 9999px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #fb923c;
}
.ring-bg {
    stroke: rgba(255, 255, 255, 0.12);
}
.ring-fill {
    stroke: #ffffff;
    transform-origin: 18px 18px;
    transform: rotate(-90deg);
}
.play-ring .absolute svg {
    fill: white;
}

/* Emoji-only messages: show larger emoji for better visibility */
.emoji-large {
    font-size: 32px;
    line-height: 1.1;
    display: inline-block;
}

.important-badge {
    background: rgba(250, 204, 21, 0.12);
    color: #b45309; /* amber-700 */
    border-radius: 9999px;
    padding: 4px;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.08);
    transform: translate(8px, -8px);
    transition:
        transform 120ms ease,
        background-color 120ms ease;
}

.message-bubble.relative .important-badge {
    top: 0;
    right: 0;
}

.message-text {
    word-break: break-word;
}

/* Dark background for message bubble and constrained width per request */
.message-bubble {
    background: #374151; /* gray-700 */
    color: #f3f4f6; /* gray-100 */
    padding: 0.5rem 0.75rem;
    border-radius: 0.625rem;
    display: inline-block;
    max-width: 30rem;
}

.message-bubble .message-text,
.message-bubble .emoji-large {
    color: inherit;
}
</style>
