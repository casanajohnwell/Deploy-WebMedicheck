<script setup lang="ts">
import { Head, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import Heading from '@/components/Heading.vue';
import { useActiveAddress } from '@/composables/useActiveAddress';
import type { Station } from '@/types';

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Address settings', href: '/settings/address' }],
    },
});

const page = usePage();
const { activeAddress } = useActiveAddress();
const station = computed(() => (page.props.station as Station | null) ?? null);
</script>

<template>
    <Head title="Address" />

    <div class="space-y-6">
        <Heading
            title="Address"
            description="The admin console will show this barangay only."
        />

        <div
            v-if="station || activeAddress"
            class="rounded-lg border border-red-200 bg-white p-4 text-black"
        >
            <p class="font-medium">
                {{ station?.barangay ?? activeAddress?.barangay }},
                {{ station?.city ?? activeAddress?.city }},
                {{ station?.province ?? activeAddress?.province }}
            </p>
            <p class="mt-1 text-xs font-medium">
                Mobile users who pick this barangay connect here.
            </p>
            <p class="mt-3 text-xs text-neutral-600">
                View only. To change the barangay, ask the MHO super admin.
            </p>
        </div>
        <p v-else class="text-sm text-black">No saved address yet.</p>
    </div>
</template>
