<script setup>
defineProps({
    spinning: { type: Boolean, default: false },
});

const uid = Math.random().toString(36).slice(2, 11);
const labelId = `vinyl-label-${uid}`;
const shineId = `vinyl-shine-${uid}`;
</script>

<template>
    <span
        class="inline-flex items-center justify-center"
        :class="spinning ? 'vinyl-record--spin vinyl-record--glow' : ''"
        aria-hidden="true"
    >
        <svg
            xmlns="http://www.w3.org/2000/svg"
            viewBox="0 0 200 200"
            class="block h-full w-full"
        >
            <defs>
                <linearGradient :id="shineId" x1="0%" y1="0%" x2="100%" y2="100%">
                    <stop offset="0%" stop-color="#ffffff" stop-opacity="0.28" />
                    <stop offset="40%" stop-color="#ffffff" stop-opacity="0" />
                    <stop offset="100%" stop-color="#ffffff" stop-opacity="0" />
                </linearGradient>
                <radialGradient :id="labelId" cx="35%" cy="35%" r="65%">
                    <stop offset="0%" stop-color="#ef4444" />
                    <stop offset="55%" stop-color="#991b1b" />
                    <stop offset="100%" stop-color="#450a0a" />
                </radialGradient>
            </defs>
            <circle cx="100" cy="100" r="97" fill="#18181b" stroke="#a1a1aa" stroke-width="3" />
            <circle cx="100" cy="100" r="88" fill="none" stroke="#52525b" stroke-width="2" />
            <circle cx="100" cy="100" r="72" fill="none" stroke="#71717a" stroke-width="1.5" />
            <circle cx="100" cy="100" r="56" fill="none" stroke="#52525b" stroke-width="1.5" />
            <circle cx="100" cy="100" r="40" fill="none" stroke="#71717a" stroke-width="1.25" />
            <path
                d="M 100 28 A 72 72 0 0 1 154 100"
                fill="none"
                :stroke="`url(#${shineId})`"
                stroke-width="16"
                stroke-linecap="round"
                opacity="0.95"
            />
            <circle
                cx="100"
                cy="100"
                r="34"
                :fill="`url(#${labelId})`"
                stroke="#27272a"
                stroke-width="1.5"
            />
            <circle cx="100" cy="100" r="11" fill="#0a0a0a" stroke="#a1a1aa" stroke-width="2" />
            <line
                x1="100"
                y1="100"
                x2="100"
                y2="22"
                stroke="#f4f4f5"
                stroke-width="6"
                stroke-linecap="round"
            />
            <line
                x1="100"
                y1="100"
                x2="178"
                y2="100"
                stroke="#d6d3d1"
                stroke-width="4"
                stroke-linecap="round"
                opacity="0.95"
            />
        </svg>
    </span>
</template>

<style scoped>
.vinyl-record--spin {
    animation: vinyl-spin 0.75s linear infinite;
}

.vinyl-record--glow svg {
    filter: drop-shadow(0 0 12px rgba(239, 68, 68, 0.4)) drop-shadow(0 6px 18px rgba(0, 0, 0, 0.7));
}

@keyframes vinyl-spin {
    from {
        transform: rotate(0deg);
    }
    to {
        transform: rotate(360deg);
    }
}
</style>
