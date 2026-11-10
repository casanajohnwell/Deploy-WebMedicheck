<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import {
    Building2,
    Check,
    Clock,
    MapPin,
    Pill,
    UserX,
    Users,
} from '@lucide/vue';
import {
    BarElement,
    CategoryScale,
    Chart as ChartJS,
    Legend,
    LinearScale,
    Tooltip,
} from 'chart.js';
import { computed } from 'vue';
import { Bar } from 'vue-chartjs';
import RejectAccountForm from '@/components/superadmin/RejectAccountForm.vue';

ChartJS.register(BarElement, CategoryScale, LinearScale, Tooltip, Legend);

type Center = {
    health_center: string;
    barangay: string | null;
    city: string | null;
    province: string | null;
    available: number;
    low_stock: number;
    out_of_stock: number;
};

type Detail = {
    health_center: string;
    medicines: {
        id: number;
        name: string;
        category: string;
        quantity: number;
        unit: string;
        status: string;
        expiration: string | null;
    }[];
    report: {
        summary: {
            most_taken: {
                medicine: string;
                quantity: number;
                times: number;
            } | null;
        };
    };
};

type PendingAdmin = {
    id: number;
    name: string;
    email: string;
    position: string | null;
    barangay: string | null;
    city: string | null;
    health_center: string | null;
};

type DeclinedAdmin = PendingAdmin & {
    province: string | null;
    previous_status: 'pending' | 'approved';
    reason: string | null;
    declined_at: string | null;
};

type Metrics = {
    activeBarangays: number;
    pendingApprovals: number;
    healthWorkers: number;
    available: number;
    lowStock: number;
    outOfStock: number;
};

type Charts = {
    inventory: { label: string; available: number; low: number; out: number }[];
};

const props = defineProps<{
    metrics: Metrics;
    pendingAdmins: PendingAdmin[];
    declinedAdmins: DeclinedAdmin[];
    charts: Charts;
    centers: Center[];
    center: Detail | null;
    year: number;
    month: number;
}>();

const recentRejected = computed(() => props.declinedAdmins.slice(0, 3));

const months = [
    'January',
    'February',
    'March',
    'April',
    'May',
    'June',
    'July',
    'August',
    'September',
    'October',
    'November',
    'December',
];

const mostTaken = computed(
    () => props.center?.report.summary.most_taken?.medicine ?? 'No medicine was taken this month.',
);

function centerHref(healthCenter: string, year = props.year, month = props.month) {
    const params = new URLSearchParams({
        center: healthCenter,
        year: String(year),
        month: String(month),
    });

    return `/superadmin/dashboard?${params.toString()}`;
}

function stockLabel(status: string) {
    if (status === 'expired') {
        return 'Expired';
    }

    if (status === 'low') {
        return 'Low stock';
    }

    if (status === 'out_of_stock') {
        return 'Out of stock';
    }

    return 'Available';
}

function stockClass(status: string) {
    if (status === 'expired') {
        return 'bg-zinc-200 text-zinc-700';
    }

    if (status === 'low') {
        return 'bg-amber-100 text-amber-800';
    }

    if (status === 'out_of_stock') {
        return 'bg-red-100 text-red-800';
    }

    return 'bg-green-100 text-green-800';
}

const chartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: { labels: { color: '#111' } },
    },
    scales: {
        x: { ticks: { color: '#111', maxRotation: 40, minRotation: 0 } },
        y: { beginAtZero: true, ticks: { color: '#111', precision: 0 } },
    },
};

const inventoryChart = computed(() => ({
    labels: props.charts.inventory.map((row) => row.label),
    datasets: [
        {
            label: 'Available',
            data: props.charts.inventory.map((row) => row.available),
            backgroundColor: 'rgba(220, 38, 38, 0.85)',
            borderRadius: 4,
        },
        {
            label: 'Low stock',
            data: props.charts.inventory.map((row) => row.low),
            backgroundColor: 'rgba(252, 165, 165, 0.95)',
            borderRadius: 4,
        },
        {
            label: 'Out of stock',
            data: props.charts.inventory.map((row) => row.out),
            backgroundColor: 'rgba(127, 29, 29, 0.85)',
            borderRadius: 4,
        },
    ],
}));
</script>

<template>
    <Head title="Super admin" />

    <div class="flex flex-col gap-1">
        <h1 class="text-2xl font-semibold">Dashboard</h1>
        <p class="text-sm text-neutral-600">
            Approve or reject barangay administrators and view medicine stock
            across health centers. Use Walk-in registration when a nurse
            registers at the MHO office.
        </p>
    </div>

    <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        <a
            href="#barangays"
            class="flex items-center gap-3 rounded-xl border border-red-100 bg-white p-4 shadow-sm hover:border-red-300"
        >
            <span class="flex size-11 items-center justify-center rounded-lg bg-red-100 text-red-700">
                <Building2 class="size-5" />
            </span>
            <div>
                <p class="text-xs text-neutral-600">Active barangays</p>
                <p class="text-2xl font-semibold">{{ metrics.activeBarangays }}</p>
            </div>
        </a>
        <a
            href="#approvals"
            class="flex items-center gap-3 rounded-xl border border-red-100 bg-white p-4 shadow-sm hover:border-red-300"
        >
            <span class="flex size-11 items-center justify-center rounded-lg bg-amber-100 text-amber-800">
                <Clock class="size-5" />
            </span>
            <div>
                <p class="text-xs text-neutral-600">Pending approvals</p>
                <p class="text-2xl font-semibold">{{ metrics.pendingApprovals }}</p>
            </div>
        </a>
        <Link
            href="/superadmin/accounts"
            class="flex items-center gap-3 rounded-xl border border-red-100 bg-white p-4 shadow-sm hover:border-red-300"
        >
            <span class="flex size-11 items-center justify-center rounded-lg bg-red-100 text-red-700">
                <Users class="size-5" />
            </span>
            <div>
                <p class="text-xs text-neutral-600">Health workers</p>
                <p class="text-2xl font-semibold">{{ metrics.healthWorkers }}</p>
            </div>
        </Link>
        <a
            href="#inventory"
            class="flex items-center gap-3 rounded-xl border border-red-100 bg-white p-4 shadow-sm hover:border-red-300"
        >
            <span class="flex size-11 items-center justify-center rounded-lg bg-red-100 text-red-700">
                <Pill class="size-5" />
            </span>
            <div>
                <p class="text-xs text-neutral-600">Municipality medicines</p>
                <p class="text-sm">
                    <span class="font-semibold">{{ metrics.available }}</span> available ·
                    <span class="font-semibold">{{ metrics.lowStock }}</span> low ·
                    <span class="font-semibold">{{ metrics.outOfStock }}</span> out
                </p>
            </div>
        </a>
    </div>

    <div class="grid gap-6 xl:grid-cols-[3fr_2fr]">
        <section id="approvals" class="flex flex-col gap-3 rounded-xl border border-red-100 bg-white p-4 shadow-sm">
            <div class="flex items-center justify-between">
                <h2 class="font-semibold">Waiting for MHO approval</h2>
                <span class="rounded-full bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-800">
                    {{ pendingAdmins.length }}
                </span>
            </div>
            <p
                v-if="pendingAdmins.length === 0"
                class="rounded-lg border border-dashed border-red-200 p-6 text-center text-sm text-neutral-600"
            >
                No barangay administrator is waiting.
            </p>
            <article
                v-for="admin in pendingAdmins"
                :key="admin.id"
                class="grid gap-3 rounded-lg border border-red-100 p-3 text-sm"
            >
                <div>
                    <p class="font-medium">{{ admin.name }}</p>
                    <p class="text-xs text-neutral-600">{{ admin.email }} · {{ admin.position }}</p>
                    <p class="flex items-center gap-1 text-xs text-neutral-600">
                        <MapPin class="size-3" />
                        {{ admin.health_center || admin.barangay }}
                        <span v-if="admin.city">· {{ admin.city }}</span>
                    </p>
                </div>
                <div class="flex flex-wrap items-start gap-2">
                    <Form :action="`/superadmin/accounts/${admin.id}/approve`" method="post">
                        <button
                            type="submit"
                            class="flex items-center gap-1.5 rounded-md bg-red-600 px-3 py-2 text-white hover:bg-red-700"
                        >
                            <Check class="size-4" />
                            Approve
                        </button>
                    </Form>
                    <RejectAccountForm :user-id="admin.id" :name="admin.name" />
                </div>
            </article>
        </section>

        <section class="flex flex-col gap-3 rounded-xl border border-red-100 bg-white p-4 shadow-sm">
            <div class="flex items-center justify-between">
                <h2 class="flex items-center gap-2 font-semibold">
                    <UserX class="size-4 text-red-700" />
                    Recently rejected
                </h2>
                <Link href="/superadmin/accounts?tab=rejected" class="text-xs font-medium text-red-700">
                    View all ({{ declinedAdmins.length }})
                </Link>
            </div>
            <p
                v-if="recentRejected.length === 0"
                class="rounded-lg border border-dashed border-red-200 p-6 text-center text-sm text-neutral-600"
            >
                No rejected account yet.
            </p>
            <article
                v-for="admin in recentRejected"
                :key="admin.id"
                class="rounded-lg border border-red-100 p-3 text-sm"
            >
                <div class="flex items-start justify-between gap-2">
                    <p class="font-medium">{{ admin.name }}</p>
                    <span class="text-xs text-neutral-500">{{ admin.declined_at }}</span>
                </div>
                <p class="text-xs text-neutral-600">
                    {{ admin.health_center || admin.barangay }}
                </p>
                <p class="mt-1 text-xs">
                    <span class="font-medium">Reason:</span>
                    {{ admin.reason || 'No reason given.' }}
                </p>
            </article>
        </section>
    </div>

    <section id="inventory" class="rounded-xl border border-red-100 bg-white p-4 shadow-sm">
        <h2 class="mb-3 font-semibold">Inventory across barangays</h2>
        <p v-if="charts.inventory.length === 0" class="text-sm text-neutral-600">
            No medicine stock yet.
        </p>
        <div v-else class="h-72">
            <Bar :data="inventoryChart" :options="chartOptions" />
        </div>
    </section>

    <section id="barangays" class="flex flex-col gap-3">
        <h2 class="font-semibold">Approved barangay directory</h2>
        <p
            v-if="centers.length === 0"
            class="rounded-xl border border-dashed border-red-200 bg-white p-6 text-center text-sm text-neutral-600"
        >
            No health center is open yet.
        </p>
        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
            <Link
                v-for="item in centers"
                :key="item.health_center"
                :href="centerHref(item.health_center)"
                preserve-scroll
                class="rounded-xl border bg-white p-4 shadow-sm hover:border-red-300"
                :class="center?.health_center === item.health_center ? 'border-red-500 ring-1 ring-red-500' : 'border-red-100'"
            >
                <p class="font-medium">{{ item.health_center }}</p>
                <p v-if="item.city" class="text-xs text-neutral-600">
                    {{ item.city }}<span v-if="item.province">, {{ item.province }}</span>
                </p>
                <div class="mt-3 flex flex-wrap gap-1.5 text-xs">
                    <span class="rounded-full bg-green-100 px-2 py-0.5 text-green-800">
                        {{ item.available }} available
                    </span>
                    <span class="rounded-full bg-amber-100 px-2 py-0.5 text-amber-800">
                        {{ item.low_stock }} low
                    </span>
                    <span class="rounded-full bg-red-100 px-2 py-0.5 text-red-800">
                        {{ item.out_of_stock }} out
                    </span>
                </div>
            </Link>
        </div>
    </section>

    <section v-if="center" class="flex flex-col gap-4 rounded-xl border border-red-100 bg-white p-4 shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <h2 class="text-xl font-semibold">{{ center.health_center }}</h2>
            <span class="text-xs text-neutral-600">{{ year }}</span>
        </div>
        <div class="flex flex-wrap gap-1.5">
            <Link
                v-for="(label, index) in months"
                :key="label"
                :href="centerHref(center.health_center, year, index + 1)"
                preserve-scroll
                class="rounded-md px-2.5 py-1 text-xs"
                :class="month === index + 1 ? 'bg-red-600 text-white' : 'border border-red-200 hover:bg-red-50'"
            >
                {{ label }}
            </Link>
        </div>
        <p class="rounded-lg bg-red-50/70 px-3 py-2 text-sm">
            Most taken this month: <strong>{{ mostTaken }}</strong>
        </p>

        <div>
            <div class="mb-2 flex items-center justify-between">
                <h3 class="font-semibold">Medicines</h3>
                <p class="text-xs text-neutral-600">View only. The barangay nurse updates this stock.</p>
            </div>
            <p
                v-if="center.medicines.length === 0"
                class="rounded-lg border border-dashed border-red-200 p-6 text-center text-sm text-neutral-600"
            >
                No medicine recorded.
            </p>
            <div v-else class="overflow-x-auto rounded-lg border border-red-100">
                <table class="w-full text-left text-sm">
                    <thead class="bg-red-50 text-xs text-neutral-700">
                        <tr>
                            <th class="px-3 py-2 font-medium">Medicine</th>
                            <th class="px-3 py-2 font-medium">Quantity</th>
                            <th class="px-3 py-2 font-medium">Status</th>
                            <th class="px-3 py-2 font-medium">Expiration</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="item in center.medicines"
                            :key="item.id"
                            class="border-t border-red-50"
                        >
                            <td class="px-3 py-2 font-medium">{{ item.name }}</td>
                            <td class="px-3 py-2">{{ item.quantity }} {{ item.unit }}</td>
                            <td class="px-3 py-2">
                                <span class="rounded-full px-2 py-0.5 text-xs" :class="stockClass(item.status)">
                                    {{ stockLabel(item.status) }}
                                </span>
                            </td>
                            <td class="px-3 py-2">{{ item.expiration || 'No expiration' }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </section>
</template>
