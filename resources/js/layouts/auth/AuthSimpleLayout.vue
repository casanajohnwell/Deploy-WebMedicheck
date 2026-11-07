<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { home } from '@/routes';

const {
    title = '',
    description = '',
    wide = false,
} = defineProps<{
    title?: string;
    description?: string;
    wide?: boolean;
}>();
</script>

<template>
    <div
        class="theme-light flex min-h-svh flex-col items-center gap-6 bg-[radial-gradient(circle_at_top,rgba(220,38,38,0.16),white_42%),radial-gradient(circle_at_bottom_right,rgba(255,255,255,0.98),white_36%)] p-4 text-black sm:p-6 md:p-10"
        :class="wide ? 'justify-start' : 'justify-center'"
    >
        <div
            class="w-full rounded-xl border border-red-200 bg-white p-4 shadow-sm sm:p-6"
            :class="wide ? 'max-w-lg' : 'max-w-sm'"
        >
            <div class="flex flex-col gap-8">
                <div class="flex flex-col items-center gap-4">
                    <Link
                        :href="home()"
                        class="flex flex-col items-center gap-3 font-medium text-black"
                    >
                        <img
                            src="/images/medicheck-logo.svg"
                            alt="MediCheck"
                            class="h-16 w-16"
                        />
                        <span
                            class="text-2xl font-bold tracking-wide text-black"
                        >
                            MEDICHECK
                        </span>
                    </Link>
                    <p
                        class="text-center text-[11px] tracking-[0.18em] text-black uppercase"
                    >
                        Medical Diagnostics & Screening
                    </p>
                    <div class="space-y-2 text-center">
                        <h1 class="text-xl font-medium text-black">
                            {{ title }}
                        </h1>
                        <p class="text-center text-sm text-black">
                            {{ description }}
                        </p>
                    </div>
                </div>
                <div class="text-black">
                    <slot />
                </div>
            </div>
        </div>
    </div>
</template>
