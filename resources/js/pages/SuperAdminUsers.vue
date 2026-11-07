<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { MapPin } from '@lucide/vue';
import { computed, ref } from 'vue';
import { Input } from '@/components/ui/input';

type Seeker = {
    id: number;
    name: string;
    email: string;
    phone: string | null;
    barangay: string | null;
    city: string | null;
    province: string | null;
    is_active: boolean;
    bookings: number;
    joined: string | null;
};

const props = defineProps<{
    seekers: Seeker[];
}>();

const search = ref('');

const filtered = computed(() => {
    const term = search.value.trim().toLowerCase();

    if (!term) {
        return props.seekers;
    }

    return props.seekers.filter((seeker) =>
        [seeker.name, seeker.email, seeker.phone, seeker.barangay, seeker.city]
            .filter(Boolean)
            .some((value) => String(value).toLowerCase().includes(term)),
    );
});

function initials(name: string) {
    return name
        .split(/\s+/)
        .filter(Boolean)
        .slice(0, 2)
        .map((part) => part[0]?.toUpperCase())
        .join('');
}
</script>

<template>
    <Head title="User accounts" />

    <div class="flex flex-col gap-1">
        <h1 class="text-2xl font-semibold">User accounts</h1>
        <p class="text-sm text-neutral-600">
            Mobile app accounts of people who request medicine from the
            barangay health centers.
        </p>
    </div>

    <section class="grid gap-3">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <p class="text-sm text-neutral-600">
                {{ seekers.length }} registered {{ seekers.length === 1 ? 'user' : 'users' }}
            </p>
            <Input
                v-model="search"
                type="search"
                placeholder="Search name, email, phone, or barangay"
                class="w-full bg-white sm:w-80"
            />
        </div>
        <p
            v-if="filtered.length === 0"
            class="rounded-xl border border-dashed border-red-200 bg-white p-8 text-center text-sm text-neutral-600"
        >
            {{ seekers.length === 0 ? 'No user account yet.' : 'No user matches your search.' }}
        </p>
        <article
            v-for="seeker in filtered"
            :key="seeker.id"
            class="rounded-xl border border-red-100 bg-white p-4 shadow-sm"
        >
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div class="flex min-w-0 items-center gap-3">
                    <span
                        class="flex size-10 shrink-0 items-center justify-center rounded-full bg-blue-100 text-sm font-semibold text-blue-800"
                    >
                        {{ initials(seeker.name) }}
                    </span>
                    <div class="min-w-0">
                        <p class="font-semibold break-words">{{ seeker.name }}</p>
                        <p class="text-xs break-all text-neutral-600">
                            {{ seeker.email }}<template v-if="seeker.phone"> · {{ seeker.phone }}</template>
                        </p>
                        <p class="flex items-center gap-1 text-xs text-neutral-600">
                            <MapPin class="size-3 shrink-0" />
                            {{ [seeker.barangay, seeker.city, seeker.province].filter(Boolean).join(', ') || 'No address' }}
                        </p>
                    </div>
                </div>
                <div class="flex flex-col items-end gap-1">
                    <div class="flex flex-wrap justify-end gap-1.5">
                        <span class="rounded-full bg-red-50 px-2.5 py-1 text-xs font-medium text-red-700">
                            {{ seeker.bookings }} {{ seeker.bookings === 1 ? 'request' : 'requests' }}
                        </span>
                        <span
                            class="rounded-full px-2.5 py-1 text-xs font-medium"
                            :class="seeker.is_active ? 'bg-green-100 text-green-800' : 'bg-neutral-200 text-neutral-700'"
                        >
                            {{ seeker.is_active ? 'Active' : 'Turned off' }}
                        </span>
                    </div>
                    <span v-if="seeker.joined" class="text-xs text-neutral-500">Joined {{ seeker.joined }}</span>
                </div>
            </div>
        </article>
    </section>
</template>
