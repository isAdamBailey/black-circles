<template>
    <button
        :disabled="disabled"
        class="inline-flex items-center rounded-md border border-transparent bg-white px-4 py-2.5 text-sm font-semibold text-gray-900 transition duration-150 ease-in-out hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 focus:ring-offset-gray-950 active:bg-gray-300 disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:bg-white"
    >
        <slot />
    </button>
</template>

<script setup>
defineProps({
    disabled: { type: Boolean, default: false },
});
</script>
