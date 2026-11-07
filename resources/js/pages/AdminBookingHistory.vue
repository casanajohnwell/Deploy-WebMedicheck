<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3';
import { Check, History, Search, X } from '@lucide/vue';
import AdminPageHeader from '@/components/AdminPageHeader.vue';
import { computed, ref } from 'vue';
import { toast } from 'vue-sonner';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { useSavedBookings } from '@/composables/useSavedBookings';
import { type BookingStatus } from '@/lib/medicheck-demo';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: '/admin/dashboard' },
            { title: 'Booking history', href: '/admin/bookings/history' },
        ],
    },
});

const { bookings } = useSavedBookings();
const query = ref('');

function statusFromUrl(url: string): 'all' | BookingStatus {
    const value = new URL(url, 'http://localhost').searchParams.get('status');
    const allowed: BookingStatus[] = [
        'pending',
        'approved',
        'declined',
        'claimed',
        'unclaimed',
    ];

    return allowed.includes(value as BookingStatus)
        ? (value as BookingStatus)
        : 'all';
}

const statusFilter = ref<'all' | BookingStatus>(statusFromUrl(usePage().url));

const filtered = computed(() =>
    bookings.value.filter((booking) => {
        const matchesQuery =
            `${booking.seeker} ${booking.medicine} ${booking.id}`
                .toLowerCase()
                .includes(query.value.toLowerCase());
        const matchesStatus =
            statusFilter.value === 'all' ||
            booking.status === statusFilter.value;

        return matchesQuery && matchesStatus;
    }),
);

function countFor(status: BookingStatus) {
    return bookings.value.filter((booking) => booking.status === status).length;
}

const statusTabs = computed<{ value: 'all' | BookingStatus; label: string; count: number }[]>(() => [
    { value: 'all', label: 'All', count: bookings.value.length },
    { value: 'pending', label: 'Pending', count: countFor('pending') },
    { value: 'approved', label: 'Approved', count: countFor('approved') },
    { value: 'claimed', label: 'Claimed', count: countFor('claimed') },
    { value: 'unclaimed', label: 'Unclaimed', count: countFor('unclaimed') },
    { value: 'declined', label: 'Declined', count: countFor('declined') },
]);

function setOutcome(id: number, status: 'claimed' | 'unclaimed') {
    router.put(
        `/admin/bookings/${id}`,
        { status },
        {
            preserveScroll: true,
            onSuccess: () => {
                router.visit(`/admin/sms?booking=${id}`);
                toast.success(
                    status === 'claimed'
                        ? `Request #${id} claimed. Stock was deducted.`
                        : `Request #${id} marked unclaimed.`,
                );
            },
            onError: (errors) => {
                toast.error(errors.status ?? 'Could not update this request.');
            },
        },
    );
}

const statusClass: Record<BookingStatus, string> = {
    pending: 'border-transparent bg-yellow-200 text-yellow-950',
    approved: 'border-transparent bg-green-600 text-white',
    declined: 'border-transparent bg-red-600 text-white',
    claimed: 'border-transparent bg-green-700 text-white',
    unclaimed: 'border-transparent bg-zinc-200 text-black',
};
</script>

<template>
    <Head title="Booking History" />

    <div class="flex min-w-0 flex-1 flex-col gap-6 p-3 sm:p-4 md:p-6">
        <AdminPageHeader
            title="Booking history"
            description="Review medicine requests and mark approved ones as claimed or unclaimed."
            :icon="History"
            :stats="[
                { label: 'total requests', value: bookings.length, tone: 'neutral' },
                { label: 'waiting for pickup', value: countFor('approved'), tone: 'green' },
                { label: 'claimed', value: countFor('claimed'), tone: 'blue' },
            ]"
        />

        <Card class="border-red-100">
            <CardHeader class="gap-4">
                <div class="flex flex-col gap-1">
                    <CardTitle>All requests</CardTitle>
                    <CardDescription>Requests from the mobile app</CardDescription>
                </div>
                <div class="flex gap-1.5 overflow-x-auto pb-1">
                    <button
                        v-for="tab in statusTabs"
                        :key="tab.value"
                        type="button"
                        class="flex shrink-0 items-center gap-1.5 rounded-full border px-3 py-1.5 text-xs font-medium transition-colors"
                        :class="
                            statusFilter === tab.value
                                ? 'border-red-600 bg-red-600 text-white'
                                : 'border-red-100 bg-white text-neutral-700 hover:bg-red-50'
                        "
                        @click="statusFilter = tab.value"
                    >
                        {{ tab.label }}
                        <span
                            class="rounded-full px-1.5 text-[11px]"
                            :class="statusFilter === tab.value ? 'bg-white/20' : 'bg-neutral-100'"
                        >
                            {{ tab.count }}
                        </span>
                    </button>
                </div>
            </CardHeader>
            <CardContent class="space-y-4">
                <div class="relative w-full sm:max-w-sm">
                    <Search class="text-muted-foreground absolute top-1/2 left-3 size-4 -translate-y-1/2" />
                    <Input
                        v-model="query"
                        placeholder="Search seeker, medicine, or request ID"
                        class="w-full pl-9"
                    />
                </div>
                <p
                    v-if="!filtered.length"
                    class="rounded-lg border border-dashed border-red-200 p-8 text-center text-sm text-neutral-600"
                >
                    No requests match this filter.
                </p>

                <div class="space-y-3 md:hidden">
                    <div
                        v-for="booking in filtered"
                        :key="booking.id"
                        class="space-y-2 rounded-lg border border-red-200/60 bg-white p-3 text-sm text-black"
                    >
                        <div class="flex items-start justify-between gap-2">
                            <div class="min-w-0">
                                <p class="font-medium">{{ booking.seeker }}</p>
                                <p class="text-xs font-medium">
                                    {{ booking.requestedAt }}
                                </p>
                            </div>
                            <Badge :class="statusClass[booking.status]">
                                {{ booking.status }}
                            </Badge>
                        </div>
                        <p class="text-muted-foreground">#{{ booking.id }}</p>
                        <p>
                            {{ booking.medicine }}
                            <span class="text-muted-foreground"
                                >× {{ booking.quantity }}</span
                            >
                        </p>
                        <p class="text-muted-foreground break-words">
                            {{ booking.healthCenter }}
                        </p>
                        <p
                            v-if="booking.claimDays"
                            class="text-xs font-medium text-black"
                        >
                            Claim period: {{ booking.claimDays }}
                            {{ booking.claimDays === 1 ? 'day' : 'days' }}
                            <span v-if="booking.claimBy"
                                >· until {{ booking.claimBy }}</span
                            >
                        </p>
                        <div
                            v-if="booking.status === 'approved'"
                            class="flex flex-col gap-2"
                        >
                            <Button
                                class="w-full bg-green-600 text-white hover:bg-green-700"
                                @click="setOutcome(booking.id, 'claimed')"
                            >
                                <Check class="size-4" />
                                Claimed
                            </Button>
                            <Button
                                class="w-full"
                                variant="destructive"
                                @click="setOutcome(booking.id, 'unclaimed')"
                            >
                                <X class="size-4" />
                                Unclaimed
                            </Button>
                        </div>
                    </div>
                </div>

                <div
                    v-if="filtered.length"
                    class="hidden overflow-x-auto rounded-lg border border-red-100 bg-white md:block"
                >
                    <table class="w-full min-w-[820px] text-left text-sm">
                        <thead class="bg-red-50 text-xs tracking-wide text-neutral-600 uppercase">
                            <tr>
                                <th class="px-4 py-3 font-medium">ID</th>
                                <th class="px-4 py-3 font-medium">Seeker</th>
                                <th class="px-4 py-3 font-medium">Medicine</th>
                                <th class="px-4 py-3 font-medium">Center</th>
                                <th class="px-4 py-3 font-medium">
                                    Date and time
                                </th>
                                <th class="px-4 py-3 font-medium">Status</th>
                                <th class="px-4 py-3 font-medium">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="booking in filtered"
                                :key="booking.id"
                                class="border-t border-red-50 hover:bg-red-50/40"
                            >
                                <td class="px-4 py-3">#{{ booking.id }}</td>
                                <td class="px-4 py-3">{{ booking.seeker }}</td>
                                <td class="px-4 py-3">
                                    {{ booking.medicine }}
                                    <span class="text-muted-foreground">
                                        × {{ booking.quantity }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    {{ booking.healthCenter }}
                                </td>
                                <td class="px-4 py-3">
                                    {{ booking.requestedAt }}
                                    <p
                                        v-if="booking.claimDays"
                                        class="text-muted-foreground text-xs"
                                    >
                                        Claim within {{ booking.claimDays }}
                                        {{
                                            booking.claimDays === 1
                                                ? 'day'
                                                : 'days'
                                        }}
                                        <span v-if="booking.claimBy"
                                            >(until {{ booking.claimBy }})</span
                                        >
                                    </p>
                                </td>
                                <td class="px-4 py-3">
                                    <Badge :class="statusClass[booking.status]">
                                        {{ booking.status }}
                                    </Badge>
                                </td>
                                <td class="px-4 py-3">
                                    <div
                                        v-if="booking.status === 'approved'"
                                        class="flex flex-wrap gap-2"
                                    >
                                        <Button
                                            size="sm"
                                            class="bg-green-600 text-white hover:bg-green-700"
                                            @click="
                                                setOutcome(booking.id, 'claimed')
                                            "
                                        >
                                            <Check class="size-4" />
                                            Claimed
                                        </Button>
                                        <Button
                                            size="sm"
                                            variant="destructive"
                                            @click="
                                                setOutcome(
                                                    booking.id,
                                                    'unclaimed',
                                                )
                                            "
                                        >
                                            <X class="size-4" />
                                            Unclaimed
                                        </Button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </CardContent>
        </Card>
    </div>
</template>
