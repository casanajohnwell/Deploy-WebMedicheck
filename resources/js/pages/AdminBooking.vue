<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { Check, ClipboardCheck, Inbox, Phone, Pill, X } from '@lucide/vue';
import AdminPageHeader from '@/components/AdminPageHeader.vue';
import { toast } from 'vue-sonner';
import { computed, reactive } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { useSavedBookings } from '@/composables/useSavedBookings';
import type { Booking } from '@/lib/medicheck-demo';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: '/admin/dashboard' },
            { title: 'Booking approval', href: '/admin/bookings/approval' },
        ],
    },
});

const { bookings } = useSavedBookings();
const claimDaysById = reactive<Record<number, number>>({});
const dayOptions = [1, 2, 3, 5, 7];

const pending = computed(() =>
    bookings.value.filter((booking) => booking.status === 'pending'),
);

function initials(name: string) {
    return name
        .split(/\s+/)
        .filter(Boolean)
        .slice(0, 2)
        .map((part) => part[0]?.toUpperCase())
        .join('');
}

function getClaimDays(booking: Booking): number {
    return claimDaysById[booking.id] ?? booking.claimDays ?? 3;
}

function setClaimDays(booking: Booking, value: number) {
    claimDaysById[booking.id] = value;
    router.put(
        `/admin/bookings/${booking.id}`,
        { claim_days: value },
        {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => {
                toast.success(
                    `Claim period set to ${value} ${value === 1 ? 'day' : 'days'}.`,
                );
            },
            onError: (errors) => {
                toast.error(
                    errors.claim_days ?? 'Could not save claim period.',
                );
            },
        },
    );
}

function updateStatus(booking: Booking, status: Booking['status']) {
    const claimDays = getClaimDays(booking);
    router.put(
        `/admin/bookings/${booking.id}`,
        status === 'approved' ? { status, claim_days: claimDays } : { status },
        {
            preserveScroll: true,
            onSuccess: () => {
                router.visit(`/admin/sms?booking=${booking.id}`);

                toast.success(
                    status === 'approved'
                        ? `Request #${booking.id} approved. Claim period: ${claimDays} ${claimDays === 1 ? 'day' : 'days'}.`
                        : `Request #${booking.id} declined.`,
                );
            },
            onError: (errors) => {
                toast.error(
                    errors.status ?? 'Could not update this request.',
                );
            },
        },
    );
}
</script>

<template>
    <Head title="Booking Approval" />

    <div class="flex min-w-0 flex-1 flex-col gap-6 p-3 sm:p-4 md:p-6">
        <AdminPageHeader
            title="Booking approval"
            description="Approve or decline medicine requests from seekers. Stock is deducted only when the medicine is claimed."
            :icon="ClipboardCheck"
            :stats="[{ label: 'waiting for approval', value: pending.length, tone: pending.length ? 'red' : 'neutral' }]"
        />

        <Card v-if="!pending.length" class="border-dashed border-red-200">
            <CardContent class="flex flex-col items-center gap-2 p-10 text-center">
                <Inbox class="size-10 text-red-300" />
                <p class="font-medium text-black">No pending bookings right now.</p>
                <p class="text-muted-foreground text-sm">
                    New requests from the mobile app will show here.
                </p>
            </CardContent>
        </Card>

        <div class="grid gap-4 xl:grid-cols-2">
            <Card
                v-for="booking in pending"
                :key="booking.id"
                class="overflow-hidden border-red-100 border-l-4 border-l-red-500"
            >
                <CardHeader
                    class="flex flex-row items-start justify-between gap-3"
                >
                    <div class="flex min-w-0 items-center gap-3">
                        <span
                            class="flex size-10 shrink-0 items-center justify-center rounded-full bg-red-100 text-sm font-semibold text-red-700"
                        >
                            {{ initials(booking.seeker) }}
                        </span>
                        <div class="min-w-0">
                            <CardTitle class="truncate">{{ booking.seeker }}</CardTitle>
                            <CardDescription>
                                Request #{{ booking.id }} ·
                                {{ booking.requestedAt }}
                            </CardDescription>
                        </div>
                    </div>
                    <Badge class="shrink-0 border-transparent bg-amber-100 text-amber-800">
                        Pending
                    </Badge>
                </CardHeader>
                <CardContent class="space-y-4">
                    <div class="grid gap-2 text-sm sm:grid-cols-2">
                        <div class="rounded-lg bg-red-50/70 px-3 py-2">
                            <p class="text-muted-foreground flex items-center gap-1 text-xs">
                                <Pill class="size-3" /> Medicine
                            </p>
                            <p class="font-medium">
                                {{ booking.medicine }}
                                <span class="text-muted-foreground">× {{ booking.quantity }}</span>
                            </p>
                        </div>
                        <div class="rounded-lg bg-red-50/70 px-3 py-2">
                            <p class="text-muted-foreground flex items-center gap-1 text-xs">
                                <Phone class="size-3" /> Contact
                            </p>
                            <p class="font-medium">{{ booking.phone || '—' }}</p>
                        </div>
                    </div>
                    <p
                        v-if="booking.notes"
                        class="rounded-lg border border-dashed border-red-200 px-3 py-2 text-sm"
                    >
                        <span class="text-muted-foreground">Note:</span>
                        {{ booking.notes }}
                    </p>
                    <div
                        class="flex flex-col gap-2 border-t border-red-50 pt-4 sm:flex-row sm:flex-wrap sm:items-center"
                    >
                        <label
                            class="flex items-center gap-2 text-sm text-black"
                        >
                            <span class="text-muted-foreground"
                                >Claim period</span
                            >
                            <select
                                :value="getClaimDays(booking)"
                                class="border-input h-9 rounded-md border bg-transparent px-3 text-sm"
                                @change="
                                    setClaimDays(
                                        booking,
                                        Number(
                                            (
                                                $event.target as HTMLSelectElement
                                            ).value,
                                        ),
                                    )
                                "
                            >
                                <option
                                    v-for="days in dayOptions"
                                    :key="days"
                                    :value="days"
                                >
                                    {{ days }}
                                    {{ days === 1 ? 'day' : 'days' }}
                                </option>
                            </select>
                        </label>
                        <span class="hidden flex-1 sm:block" />
                        <Button
                            class="w-full bg-red-600 text-white hover:bg-red-700 sm:w-auto"
                            @click="updateStatus(booking, 'approved')"
                        >
                            <Check class="size-4" />
                            Approve
                        </Button>
                        <Button
                            class="w-full border-red-200 text-red-700 hover:bg-red-50 sm:w-auto"
                            variant="outline"
                            @click="updateStatus(booking, 'declined')"
                        >
                            <X class="size-4" />
                            Decline
                        </Button>
                    </div>
                </CardContent>
            </Card>
        </div>
    </div>
</template>
