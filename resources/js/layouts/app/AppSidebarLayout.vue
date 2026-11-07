<script setup lang="ts">
import { Form, usePage } from '@inertiajs/vue3';
import { Bell } from '@lucide/vue';
import { computed, watch } from 'vue';
import AppContent from '@/components/AppContent.vue';
import AppShell from '@/components/AppShell.vue';
import AppSidebar from '@/components/AppSidebar.vue';
import AppSidebarHeader from '@/components/AppSidebarHeader.vue';
import LowStockAlert from '@/components/LowStockAlert.vue';
import { Toaster } from '@/components/ui/sonner';
import { useActiveAddress } from '@/composables/useActiveAddress';
import type { BreadcrumbItem, Station } from '@/types';

type Props = {
    breadcrumbs?: BreadcrumbItem[];
};

withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});

const page = usePage();
const { syncFromStation } = useActiveAddress();
const station = computed(() => (page.props.station as Station | null) ?? null);
const notice = computed(
    () =>
        (page.props.adminNotice as { id: number; message: string } | null) ??
        null,
);

watch(
    station,
    (nextStation) => {
        if (nextStation) {
            syncFromStation(nextStation);
        }
    },
    { immediate: true },
);
</script>

<template>
    <AppShell variant="sidebar">
        <AppSidebar />
        <AppContent variant="sidebar" class="min-w-0 overflow-x-clip bg-[#fdf6f6] dark:bg-background">
            <AppSidebarHeader :breadcrumbs="breadcrumbs" />
            <div
                v-if="notice"
                class="mx-3 mt-3 flex flex-wrap items-center justify-between gap-2 rounded-xl border border-red-200 bg-white px-4 py-3 text-sm text-black sm:mx-4 md:mx-6"
                role="status"
            >
                <p class="flex items-start gap-2">
                    <Bell class="mt-0.5 size-4 shrink-0 text-red-600" />
                    {{ notice.message }}
                </p>
                <Form :action="`/admin/notices/${notice.id}/read`" method="post">
                    <button type="submit" class="font-medium text-red-700">
                        Dismiss
                    </button>
                </Form>
            </div>
            <slot />
        </AppContent>
        <LowStockAlert />
        <Toaster />
    </AppShell>
</template>
