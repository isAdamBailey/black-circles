<script setup>
import VinylRecordLogo from '@/Components/VinylRecordLogo.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { onBeforeUnmount, onMounted, ref } from 'vue';

const props = defineProps({
    token: { type: String, required: true },
    pollTimeoutSeconds: { type: Number, default: 180 },
});

const errorMessage = ref('');

const maxPollAttempts = Math.max(1, Math.ceil(props.pollTimeoutSeconds));
const maxConsecutiveCacheMisses = 60;

let pollCount = 0;
let consecutiveCacheMisses = 0;
let timer = null;
let cancelled = false;

function clearTimer() {
    if (timer !== null) {
        window.clearTimeout(timer);
        timer = null;
    }
}

async function poll() {
    if (cancelled) {
        return;
    }

    if (pollCount >= maxPollAttempts) {
        errorMessage.value =
            'This is taking an unusually long time. Go back to Discover and try again — if it keeps happening, try again in a few minutes.';
        return;
    }

    pollCount += 1;

    try {
        const res = await fetch(route('vibe.poll', props.token), {
            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        });
        const data = await res.json();

        if (data.ready && data.redirect) {
            router.visit(data.redirect);
            return;
        }

        if (data.ready && data.error) {
            errorMessage.value = data.error;
            return;
        }

        if (data.cache_miss) {
            consecutiveCacheMisses += 1;
            if (consecutiveCacheMisses >= maxConsecutiveCacheMisses) {
                errorMessage.value =
                    'This search session expired or is no longer available. Go back to Discover and try again.';
                return;
            }
        } else {
            consecutiveCacheMisses = 0;
        }

        timer = window.setTimeout(poll, 1000);
    } catch {
        timer = window.setTimeout(poll, 1500);
    }
}

onMounted(() => {
    poll();
});

onBeforeUnmount(() => {
    cancelled = true;
    clearTimer();
});
</script>

<template>
    <AppLayout>
        <Head title="Finding a match…" />

        <div class="max-w-lg mx-auto px-4 py-24 text-center">
            <div v-if="!errorMessage" class="space-y-6">
                <div class="flex justify-center pt-2" aria-hidden="true">
                    <VinylRecordLogo class="h-28 w-28 sm:h-32 sm:w-32" :spinning="true" />
                </div>
                <div class="space-y-2">
                    <h1 class="text-xl font-semibold text-white">Digging through the crates</h1>
                    <p class="text-gray-500 text-sm leading-relaxed max-w-sm mx-auto">
                        This uses AI to match your request to the collection, so it can take a little while — especially
                        the first time.
                    </p>
                </div>
            </div>
            <div v-else class="space-y-6">
                <p class="text-gray-300">{{ errorMessage }}</p>
                <Link
                    :href="route('home')"
                    class="inline-flex items-center justify-center px-5 py-2.5 bg-gray-800 hover:bg-gray-700 border border-gray-700 rounded-lg text-white text-sm transition-colors"
                >
                    Back to Discover
                </Link>
            </div>
        </div>
    </AppLayout>
</template>
