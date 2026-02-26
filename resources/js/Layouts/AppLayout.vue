<script setup>
import { Link } from '@inertiajs/vue3';
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();
const flash = computed(() => page.props.flash ?? {});
</script>

<template>
    <div class="min-h-screen bg-gray-950 text-gray-100">
        <!-- Navigation -->
        <nav class="bg-gray-900 border-b border-gray-800 sticky top-0 z-50">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between h-16">
                    <div class="flex items-center gap-8">
                        <Link :href="route('home')" class="flex items-center gap-2">
                            <span class="text-2xl">⚫</span>
                            <span class="text-xl font-bold tracking-tight text-white">Black Circles</span>
                        </Link>
                        <div class="hidden sm:flex items-center gap-6">
                            <Link
                                :href="route('collection.index')"
                                class="text-sm font-medium transition-colors"
                                :class="route().current('collection.*') ? 'text-white' : 'text-gray-400 hover:text-white'"
                            >
                                Collection
                            </Link>
                        </div>
                    </div>
                    <!-- Mobile nav -->
                    <div class="sm:hidden flex gap-4">
                        <Link :href="route('collection.index')" class="text-gray-400 hover:text-white text-sm">Collection</Link>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Flash messages -->
        <div v-if="flash.success || flash.error" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-4">
            <div v-if="flash.success" class="bg-green-900/50 border border-green-700 text-green-300 px-4 py-3 rounded-lg text-sm">
                {{ flash.success }}
            </div>
            <div v-if="flash.error" class="bg-red-900/50 border border-red-700 text-red-300 px-4 py-3 rounded-lg text-sm">
                {{ flash.error }}
            </div>
        </div>

        <!-- Page Content -->
        <main>
            <slot />
        </main>
    </div>
</template>
