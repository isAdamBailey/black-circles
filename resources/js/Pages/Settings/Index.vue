<script setup>
import { ref } from 'vue';
import { useForm, Link, Head } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    settings: Object,
});

const form = useForm({
    discogs_username: props.settings.discogs_username ?? '',
});

const syncing = ref(false);

function save() {
    form.post(route('settings.update'));
}

function syncCollection() {
    syncing.value = true;
    useForm({}).post(route('settings.sync'), {
        onFinish: () => { syncing.value = false; },
    });
}

function formatDate(dateStr) {
    if (!dateStr) return 'Never';
    return new Date(dateStr).toLocaleString('en-US', {
        year: 'numeric', month: 'long', day: 'numeric',
        hour: '2-digit', minute: '2-digit',
    });
}
</script>

<template>
    <AppLayout>
        <Head title="Settings" />

        <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-white">Settings</h1>
                <p class="text-gray-500 text-sm mt-1">Configure your Discogs collection source</p>
            </div>

            <!-- Discogs Config Card -->
            <div class="bg-gray-900 rounded-xl border border-gray-800 p-6 mb-6">
                <h2 class="text-lg font-semibold text-white mb-1">Discogs Account</h2>
                <p class="text-gray-500 text-sm mb-6">Enter a Discogs username to sync their public collection. No API key required for public collections.</p>

                <form @submit.prevent="save" class="space-y-5">
                    <div>
                        <label for="username" class="block text-sm font-medium text-gray-300 mb-2">Discogs Username</label>
                        <input
                            id="username"
                            v-model="form.discogs_username"
                            type="text"
                            placeholder="e.g. crate_digger"
                            class="w-full bg-gray-800 border border-gray-700 rounded-lg px-4 py-2.5 text-white placeholder-gray-500 focus:outline-none focus:border-gray-500 text-sm"
                            :class="{ 'border-red-500': form.errors.discogs_username }"
                        />
                        <p v-if="form.errors.discogs_username" class="mt-1.5 text-xs text-red-400">{{ form.errors.discogs_username }}</p>
                        <p class="mt-1.5 text-xs text-gray-500">The username from your Discogs profile URL: discogs.com/user/<strong class="text-gray-400">username</strong></p>
                    </div>
                    <div class="flex gap-3">
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="px-5 py-2.5 bg-white text-black font-semibold text-sm rounded-lg hover:bg-gray-200 disabled:opacity-50 transition-colors"
                        >
                            {{ form.processing ? 'Saving…' : 'Save Username' }}
                        </button>
                    </div>
                </form>
            </div>

            <!-- Sync Card -->
            <div class="bg-gray-900 rounded-xl border border-gray-800 p-6 mb-6">
                <h2 class="text-lg font-semibold text-white mb-1">Sync Collection</h2>
                <p class="text-gray-500 text-sm mb-1">
                    Fetch all records from Discogs and cache them locally. This may take a few minutes for large collections.
                </p>
                <p class="text-xs text-gray-600 mb-6">
                    Last synced: <span class="text-gray-400">{{ formatDate(settings.collection_last_synced) }}</span>
                </p>

                <div class="flex items-center gap-4">
                    <button
                        @click="syncCollection"
                        :disabled="syncing || !settings.discogs_username"
                        class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-500 disabled:opacity-50 text-white font-semibold text-sm rounded-lg transition-colors flex items-center gap-2"
                    >
                        <span v-if="syncing" class="animate-spin inline-block">⟳</span>
                        {{ syncing ? 'Syncing…' : 'Sync Now' }}
                    </button>
                    <p v-if="!settings.discogs_username" class="text-xs text-gray-500">Save a username first</p>
                </div>
            </div>

            <!-- API Token Card -->
            <div class="bg-gray-900 rounded-xl border border-gray-800 p-6">
                <h2 class="text-lg font-semibold text-white mb-1">Discogs API Token <span class="text-gray-500 text-sm font-normal">(optional)</span></h2>
                <p class="text-gray-500 text-sm mb-4">
                    An API token increases rate limits. Add it to your <code class="text-gray-300 bg-gray-800 px-1.5 py-0.5 rounded text-xs">.env</code> file as <code class="text-gray-300 bg-gray-800 px-1.5 py-0.5 rounded text-xs">DISCOGS_TOKEN</code>.
                </p>
                <div class="flex items-center gap-2 bg-gray-800 rounded-lg px-4 py-3 text-sm">
                    <span class="text-gray-500 font-mono select-all">DISCOGS_TOKEN=your_token_here</span>
                </div>
                <p class="mt-3 text-xs text-gray-600">
                    Get a token at
                    <a href="https://www.discogs.com/settings/developers" target="_blank" rel="noopener noreferrer" class="text-gray-400 hover:text-white underline">discogs.com/settings/developers</a>
                </p>
            </div>

            <div class="mt-8">
                <Link :href="route('collection.index')" class="text-sm text-gray-500 hover:text-white transition-colors">
                    ← Back to collection
                </Link>
            </div>
        </div>
    </AppLayout>
</template>
