<script setup lang="ts">
import { Head, usePage } from '@inertiajs/vue3';
import { Lock } from '@lucide/vue';
import { computed } from 'vue';
import Heading from '@/components/Heading.vue';
import { edit } from '@/routes/profile';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Profile settings',
                href: edit(),
            },
        ],
    },
});

const props = defineProps<{
    adminProfile?: {
        phone: string | null;
        position: string | null;
        region: string | null;
        province: string | null;
        city: string | null;
        barangay: string | null;
        street: string | null;
        postal_code: string | null;
    };
}>();

const page = usePage();
const user = computed(() => page.props.auth.user);

const details = computed(() => [
    { label: 'Administrator name', value: user.value.name },
    { label: 'Position', value: props.adminProfile?.position },
    { label: 'Email address', value: user.value.email },
    { label: 'Contact number', value: props.adminProfile?.phone },
    { label: 'Street address', value: props.adminProfile?.street },
    { label: 'Barangay', value: props.adminProfile?.barangay },
    { label: 'City / Municipality', value: props.adminProfile?.city },
    { label: 'Province', value: props.adminProfile?.province },
    { label: 'Region', value: props.adminProfile?.region },
    { label: 'Postal code', value: props.adminProfile?.postal_code },
]);
</script>

<template>
    <Head title="Profile settings" />

    <h1 class="sr-only">Profile settings</h1>

    <div class="flex flex-col space-y-6">
        <Heading
            variant="small"
            title="Profile"
            description="Your administrator details are encrypted and only visible here"
        />

        <p
            class="flex items-start gap-2 rounded-lg border border-red-100 bg-red-50 px-3 py-2 text-sm text-black"
        >
            <Lock class="mt-0.5 size-4 shrink-0 text-red-600" />
            View only. To change these details, ask the MHO super admin.
        </p>

        <dl class="grid gap-x-6 gap-y-4 sm:grid-cols-2">
            <div
                v-for="item in details"
                :key="item.label"
                class="grid gap-1 border-b border-red-50 pb-3"
            >
                <dt class="text-xs font-medium text-neutral-500">
                    {{ item.label }}
                </dt>
                <dd class="text-sm break-words text-black">
                    {{ item.value || '—' }}
                </dd>
            </div>
        </dl>
    </div>
</template>
