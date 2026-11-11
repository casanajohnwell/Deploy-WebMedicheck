<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import {
    CheckCircle2,
    ClipboardList,
    FileText,
    LineChart,
    MapPin,
    Package,
    Plus,
    ShieldAlert,
    Users,
} from '@lucide/vue';
import { Badge } from '@/components/ui/badge';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { computed } from 'vue';
import { useActiveAddress } from '@/composables/useActiveAddress';
import { useSavedBookings } from '@/composables/useSavedBookings';
import { useSavedMedicines } from '@/composables/useSavedMedicines';
import type { Medicine } from '@/lib/medicheck-demo';
import {
    EXPIRING_SOON_DAYS,
    expiresInLabel,
    isExpiringSoon,
    medicineStatusLabel,
} from '@/lib/medicheck-demo';

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Dashboard', href: '/admin/dashboard' }],
    },
});

const page = usePage();
const { activeAddress, healthCenterFilter, locationLabel } = useActiveAddress();

const firstName = computed(
    () => String(page.props.auth?.user?.name ?? '').split(/\s+/)[0] || 'Nurse',
);

const greeting = computed(() => {
    const hour = Number(
        new Intl.DateTimeFormat('en-US', {
            timeZone: 'Asia/Manila',
            hour: 'numeric',
            hour12: false,
        }).format(new Date()),
    );

    if (hour < 12) {
        return 'Good morning';
    }

    return hour < 18 ? 'Good afternoon' : 'Good evening';
});

const quickActions = [
    { label: 'Add medicine', href: '/admin/inventory', icon: Plus },
    { label: 'Review bookings', href: '/admin/bookings/approval', icon: ClipboardList },
    { label: 'View metrics', href: '/admin/metrics', icon: LineChart },
    { label: 'Monthly report', href: '/admin/reports', icon: FileText },
];
const { medicines } = useSavedMedicines();
const { bookings } = useSavedBookings();

const pending = computed(() =>
    bookings.value.filter((booking) => booking.status === 'pending'),
);
const lowStock = computed(() =>
    medicines.value.filter(
        (item) =>
            item.status === 'low' ||
            item.status === 'out_of_stock' ||
            item.status === 'expired',
    ),
);
const expiringSoon = computed(() => medicines.value.filter(isExpiringSoon));
const stockAlerts = computed(() => [
    ...lowStock.value,
    ...expiringSoon.value.filter((item) => !lowStock.value.includes(item)),
]);

function alertLabel(item: Medicine): string {
    if (item.status === 'out_of_stock') {
        return 'Out of stock';
    }

    if (item.status === 'expired') {
        return 'Expired';
    }

    if (item.status !== 'low' && isExpiringSoon(item)) {
        return expiresInLabel(item.expiresInDays ?? 0);
    }

    return `${item.quantity} left`;
}

const cards = computed(() => [
    {
        label: 'Pending bookings',
        value: pending.value.length,
        hint: 'Need approval',
        href: '/admin/bookings/approval',
        icon: ClipboardList,
        color: 'text-red-700 bg-red-100',
    },
    {
        label: 'Approved',
        value: bookings.value.filter((item) => item.status === 'approved')
            .length,
        hint: 'Waiting for pickup',
        href: '/admin/bookings/history?status=approved',
        icon: CheckCircle2,
        color: 'text-emerald-700 bg-emerald-100',
    },
    {
        label: 'Low / out of stock',
        value: lowStock.value.length,
        hint: 'Needs restock',
        href: '/admin/inventory',
        icon: ShieldAlert,
        color: 'text-amber-700 bg-amber-100',
    },
    {
        label: 'Seekers served',
        value: bookings.value.filter((item) => item.status === 'claimed')
            .length,
        hint: locationLabel.value || 'This month',
        href: '/admin/bookings/history?status=claimed',
        icon: Users,
        color: 'text-blue-700 bg-blue-100',
    },
]);
</script>

<template>
    <Head title="Admin Dashboard" />

    <div class="flex min-w-0 flex-1 flex-col gap-6 p-3 sm:p-4 md:p-6">
        <div
            class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-red-600 to-red-800 p-5 text-white shadow-sm sm:p-6"
        >
            <div class="absolute -top-10 -right-10 size-40 rounded-full bg-white/10" />
            <div class="absolute -right-4 -bottom-16 size-32 rounded-full bg-white/10" />
            <div class="relative flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div class="min-w-0">
                    <p class="text-sm text-red-100">{{ greeting }},</p>
                    <h1 class="text-2xl font-semibold tracking-tight sm:text-3xl">
                        {{ firstName }}
                    </h1>
                    <p class="mt-1 text-sm text-red-50">
                        Medicine availability, bookings, and inventory at a glance.
                    </p>
                    <p
                        v-if="activeAddress"
                        class="mt-3 inline-flex max-w-full items-center gap-1.5 rounded-full bg-white/15 px-3 py-1 text-xs break-words"
                    >
                        <MapPin class="size-3.5 shrink-0" />
                        {{ healthCenterFilter || locationLabel }}
                    </p>
                </div>
                <div class="grid grid-cols-2 gap-2 sm:flex sm:flex-wrap">
                    <Link
                        v-for="action in quickActions"
                        :key="action.label"
                        :href="action.href"
                        class="flex items-center gap-1.5 rounded-lg bg-white/15 px-3 py-2 text-xs font-medium text-white hover:bg-white/25"
                    >
                        <component :is="action.icon" class="size-4" />
                        {{ action.label }}
                    </Link>
                </div>
            </div>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <Link
                v-for="card in cards"
                :key="card.label"
                :href="card.href"
                class="block rounded-xl outline-none focus-visible:ring-2 focus-visible:ring-red-600"
            >
                <Card
                    class="h-full cursor-pointer border-red-100 transition-all hover:-translate-y-0.5 hover:border-red-300 hover:shadow-md"
                >
                    <CardHeader
                        class="flex flex-row items-center justify-between pb-2"
                    >
                        <CardDescription>{{ card.label }}</CardDescription>
                        <div
                            class="flex size-9 items-center justify-center rounded-lg"
                            :class="card.color"
                        >
                            <component :is="card.icon" class="size-4" />
                        </div>
                    </CardHeader>
                    <CardContent>
                        <div class="text-3xl font-semibold">
                            {{ card.value }}
                        </div>
                        <p class="text-muted-foreground mt-1 text-xs">
                            {{ card.hint }}
                        </p>
                    </CardContent>
                </Card>
            </Link>
        </div>

        <div class="grid gap-4 lg:grid-cols-2">
            <Card>
                <CardHeader
                    class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
                >
                    <div>
                        <CardTitle>Booking approvals</CardTitle>
                        <CardDescription
                            >Requests waiting for review</CardDescription
                        >
                    </div>
                    <Link
                        href="/admin/bookings/approval"
                        class="shrink-0 text-sm font-medium text-black hover:underline"
                    >
                        Review all
                    </Link>
                </CardHeader>
                <CardContent class="space-y-3">
                    <div
                        v-for="booking in pending"
                        :key="booking.id"
                        class="flex min-w-0 items-start justify-between gap-3 rounded-lg border border-red-200/60 bg-red-600/10 p-3"
                    >
                        <div class="min-w-0">
                            <p class="font-medium">{{ booking.seeker }}</p>
                            <p class="text-muted-foreground text-sm">
                                {{ booking.medicine }} ·
                                {{ booking.quantity }} pcs
                            </p>
                        </div>
                        <Badge variant="secondary" class="shrink-0"
                            >#{{ booking.id }}</Badge
                        >
                    </div>
                    <p
                        v-if="!pending.length"
                        class="text-muted-foreground text-sm"
                    >
                        No pending requests.
                    </p>
                </CardContent>
            </Card>

            <Card>
                <CardHeader
                    class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
                >
                    <div>
                        <CardTitle>Stock alerts</CardTitle>
                        <CardDescription>
                            Low stock, out of stock, expired, or expiring
                            within {{ EXPIRING_SOON_DAYS }} days
                        </CardDescription>
                    </div>
                    <Link
                        href="/admin/inventory"
                        class="text-sm font-medium text-black hover:underline"
                    >
                        Manage
                    </Link>
                </CardHeader>
                <CardContent class="space-y-3">
                    <div
                        v-for="item in stockAlerts"
                        :key="item.id"
                        class="flex min-w-0 items-center justify-between gap-3 rounded-lg border border-red-200/60 bg-red-600/10 p-3"
                    >
                        <img
                            :src="item.image ?? '/images/medicines/default.svg'"
                            :alt="item.name"
                            class="size-10 shrink-0 rounded-lg"
                            loading="lazy"
                        />
                        <div class="min-w-0 flex-1">
                            <p class="font-medium">{{ item.name }}</p>
                            <p
                                class="text-muted-foreground text-sm break-words"
                            >
                                {{ item.healthCenter }}
                            </p>
                            <p
                                v-if="isExpiringSoon(item) && item.status === 'low'"
                                class="text-xs text-amber-800"
                            >
                                {{ expiresInLabel(item.expiresInDays ?? 0) }}
                            </p>
                        </div>
                        <Badge
                            class="shrink-0"
                            :class="
                                item.status === 'out_of_stock' ||
                                item.status === 'expired'
                                    ? 'border-transparent bg-red-100 text-red-700'
                                    : 'border-transparent bg-amber-100 text-amber-800'
                            "
                        >
                            {{ alertLabel(item) }}
                        </Badge>
                    </div>
                    <p v-if="!stockAlerts.length" class="text-muted-foreground text-sm">
                        No stock alerts. Everything is in stock and not
                        expiring soon.
                    </p>
                </CardContent>
            </Card>
        </div>

        <div class="grid gap-4 md:grid-cols-3">
            <Card class="md:col-span-2">
                <CardHeader>
                    <CardTitle class="flex items-center gap-2 text-base">
                        <Package class="size-4 text-red-600/80" />
                        Inventory
                    </CardTitle>
                </CardHeader>
                <CardContent class="space-y-3 text-sm">
                    <p class="text-muted-foreground">
                        {{ medicines.length }} medicines at
                        {{ healthCenterFilter }}.
                    </p>
                    <div class="grid max-h-80 gap-3 overflow-y-auto sm:grid-cols-2">
                        <div
                            v-for="item in medicines"
                            :key="item.id"
                            class="flex min-w-0 items-center justify-between gap-3 rounded-lg border border-red-200/60 bg-red-600/10 p-3"
                        >
                            <img
                                :src="item.image ?? '/images/medicines/default.svg'"
                                :alt="item.name"
                                class="size-11 shrink-0 rounded-lg"
                                loading="lazy"
                            />
                            <div class="min-w-0 flex-1">
                                <p class="font-medium break-words text-black">
                                    {{ item.name }}
                                </p>
                                <p class="text-muted-foreground text-xs">
                                    {{ item.quantity }} {{ item.unit }} ·
                                    {{ item.expiration }}
                                </p>
                            </div>
                            <Badge
                                class="shrink-0"
                                :class="
                                    item.status === 'out_of_stock' ||
                                    item.status === 'expired'
                                        ? 'border-transparent bg-red-100 text-red-700'
                                        : item.status === 'low'
                                          ? 'border-transparent bg-amber-100 text-amber-800'
                                          : 'border-transparent bg-emerald-100 text-emerald-800'
                                "
                            >
                                {{ medicineStatusLabel(item.status) }}
                            </Badge>
                        </div>
                    </div>
                    <p v-if="!medicines.length" class="text-muted-foreground">
                        No medicines yet. Add from Inventory.
                    </p>
                </CardContent>
            </Card>
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2 text-base">
                        <ClipboardList class="size-4 text-red-600/80" />
                        Today's tasks
                    </CardTitle>
                </CardHeader>
                <CardContent class="space-y-3 text-sm">
                    <Link
                        href="/admin/bookings/approval"
                        class="flex items-center justify-between rounded-lg border border-red-100 p-3 hover:bg-red-50"
                    >
                        <span>Review pending bookings</span>
                        <Badge class="border-transparent bg-red-600 text-white">
                            {{ pending.length }}
                        </Badge>
                    </Link>
                    <Link
                        href="/admin/inventory"
                        class="flex items-center justify-between rounded-lg border border-red-100 p-3 hover:bg-red-50"
                    >
                        <span>Restock low or out of stock</span>
                        <Badge class="border-transparent bg-amber-100 text-amber-800">
                            {{ lowStock.length }}
                        </Badge>
                    </Link>
                    <Link
                        href="/admin/inventory"
                        class="flex items-center justify-between rounded-lg border border-red-100 p-3 hover:bg-red-50"
                    >
                        <span>Check medicines expiring soon</span>
                        <Badge class="border-transparent bg-amber-100 text-amber-800">
                            {{ expiringSoon.length }}
                        </Badge>
                    </Link>
                    <Link
                        href="/admin/sms"
                        class="flex items-center justify-between rounded-lg border border-red-100 p-3 hover:bg-red-50"
                    >
                        <span>SMS sent to seekers</span>
                        <span class="text-xs text-neutral-500">Open</span>
                    </Link>
                    <p class="text-muted-foreground text-xs">
                        Approving a booking sends an SMS record to the seeker
                        for {{ locationLabel || 'this health center' }}.
                    </p>
                </CardContent>
            </Card>
        </div>
    </div>
</template>
