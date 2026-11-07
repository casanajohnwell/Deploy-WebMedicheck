<script setup lang="ts">
import type { LucideIcon } from '@lucide/vue';

type Stat = {
    label: string;
    value: number | string;
    tone?: 'red' | 'green' | 'amber' | 'blue' | 'neutral';
};

withDefaults(
    defineProps<{
        title: string;
        description?: string;
        icon?: LucideIcon;
        stats?: Stat[];
    }>(),
    { stats: () => [] },
);

const toneClass: Record<NonNullable<Stat['tone']>, string> = {
    red: 'bg-red-100 text-red-800',
    green: 'bg-emerald-100 text-emerald-800',
    amber: 'bg-amber-100 text-amber-800',
    blue: 'bg-blue-100 text-blue-800',
    neutral: 'bg-neutral-100 text-neutral-700',
};
</script>

<template>
    <div class="flex flex-col gap-4 rounded-2xl border border-red-100 bg-white p-4 shadow-sm sm:p-5">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
            <div class="flex min-w-0 items-start gap-3">
                <span
                    v-if="icon"
                    class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-red-600 text-white shadow-sm"
                >
                    <component :is="icon" class="size-5" />
                </span>
                <div class="min-w-0">
                    <h1 class="text-xl font-semibold tracking-tight text-black sm:text-2xl">
                        {{ title }}
                    </h1>
                    <p v-if="description" class="text-muted-foreground mt-0.5 text-sm">
                        {{ description }}
                    </p>
                    <slot name="meta" />
                </div>
            </div>
            <div v-if="$slots.actions" class="flex shrink-0 flex-wrap items-center gap-2">
                <slot name="actions" />
            </div>
        </div>
        <div v-if="stats.length" class="flex flex-wrap gap-2">
            <span
                v-for="stat in stats"
                :key="stat.label"
                class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-medium"
                :class="toneClass[stat.tone ?? 'neutral']"
            >
                <span class="font-semibold">{{ stat.value }}</span>
                {{ stat.label }}
            </span>
        </div>
    </div>
</template>
