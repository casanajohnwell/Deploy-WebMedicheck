<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { CalendarDays, ClipboardCheck } from '@lucide/vue';
import { computed } from 'vue';
import Breadcrumbs from '@/components/Breadcrumbs.vue';
import { SidebarTrigger } from '@/components/ui/sidebar';
import { useSavedBookings } from '@/composables/useSavedBookings';
import type { BreadcrumbItem } from '@/types';

withDefaults(
    defineProps<{
        breadcrumbs?: BreadcrumbItem[];
    }>(),
    {
        breadcrumbs: () => [],
    },
);

const { bookings } = useSavedBookings();

const pendingCount = computed(
    () => bookings.value.filter((item) => item.status === 'pending').length,
);

const today = new Intl.DateTimeFormat('en-PH', {
    timeZone: 'Asia/Manila',
    weekday: 'short',
    month: 'short',
    day: 'numeric',
    year: 'numeric',
}).format(new Date());
</script>

<template>
    <header
        class="border-sidebar-border/70 sticky top-0 z-20 flex h-14 shrink-0 items-center justify-between gap-2 overflow-hidden border-b bg-background/90 px-3 backdrop-blur transition-[width,height] ease-linear group-has-data-[collapsible=icon]/sidebar-wrapper:h-12 sm:h-16 sm:px-4 md:rounded-t-xl"
    >
        <div class="flex min-w-0 items-center gap-2">
            <SidebarTrigger class="-ml-1" />
            <template v-if="breadcrumbs && breadcrumbs.length > 0">
                <Breadcrumbs :breadcrumbs="breadcrumbs" />
            </template>
        </div>

        <div class="flex shrink-0 items-center gap-2">
            <span
                class="hidden items-center gap-1.5 rounded-md border border-red-100 bg-red-50 px-2.5 py-1 text-xs text-neutral-700 md:flex"
            >
                <CalendarDays class="size-3.5 text-red-600" />
                {{ today }}
            </span>
            <Link
                href="/admin/bookings/approval"
                class="relative flex items-center gap-1.5 rounded-md border border-red-200 bg-white px-2.5 py-1 text-xs font-medium text-black hover:bg-red-50"
                :title="`${pendingCount} pending booking(s)`"
            >
                <ClipboardCheck class="size-3.5 text-red-600" />
                <span class="hidden sm:inline">Pending</span>
                <span
                    class="rounded-full px-1.5 text-[11px] font-semibold"
                    :class="pendingCount ? 'bg-red-600 text-white' : 'bg-neutral-100 text-neutral-600'"
                >
                    {{ pendingCount }}
                </span>
            </Link>
        </div>
    </header>
</template>
