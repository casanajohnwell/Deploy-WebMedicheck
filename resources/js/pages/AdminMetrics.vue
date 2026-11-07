<script setup lang="ts">
import { computed, onMounted, ref } from 'vue';
import { Head } from '@inertiajs/vue3';
import { LineChart } from '@lucide/vue';
import AdminPageHeader from '@/components/AdminPageHeader.vue';
import {
    ArcElement,
    BarElement,
    CategoryScale,
    Chart as ChartJS,
    Filler,
    Legend,
    LinearScale,
    LineElement,
    PointElement,
    Tooltip,
} from 'chart.js';
import { Bar, Line, Pie } from 'vue-chartjs';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { useActiveAddress } from '@/composables/useActiveAddress';
import { useSavedBookings } from '@/composables/useSavedBookings';
import { useSavedMedicines } from '@/composables/useSavedMedicines';
import { demoMetrics } from '@/lib/medicheck-demo';

ChartJS.register(
    ArcElement,
    BarElement,
    CategoryScale,
    Filler,
    Legend,
    LinearScale,
    LineElement,
    PointElement,
    Tooltip,
);

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: '/admin/dashboard' },
            { title: 'Metrics', href: '/admin/metrics' },
        ],
    },
});

const { healthCenterFilter, locationLabel } = useActiveAddress();
const { medicines: scopedMedicines } = useSavedMedicines();
const { bookings: scopedBookings } = useSavedBookings();

const ready = ref(false);
const chartInk = ref('#000000');

onMounted(() => {
    ready.value = true;
    chartInk.value = document.documentElement.classList.contains('dark')
        ? '#f5f5f5'
        : '#000000';
});

function toPercent(value: number, total: number): number {
    return total ? Math.round((value / total) * 100) : 0;
}

const requestMix = computed(() => [
    {
        label: 'Pending',
        value: scopedBookings.value.filter(
            (booking) => booking.status === 'pending',
        ).length,
    },
    {
        label: 'Approved',
        value: scopedBookings.value.filter(
            (booking) => booking.status === 'approved',
        ).length,
    },
    {
        label: 'Declined',
        value: scopedBookings.value.filter(
            (booking) => booking.status === 'declined',
        ).length,
    },
    {
        label: 'Claimed',
        value: scopedBookings.value.filter(
            (booking) => booking.status === 'claimed',
        ).length,
    },
]);

const totalRequests = computed(() =>
    requestMix.value.reduce((sum, item) => sum + item.value, 0),
);
const requestMixWithPercent = computed(() =>
    requestMix.value.map((item) => ({
        ...item,
        percent: toPercent(item.value, totalRequests.value),
    })),
);

const stockByCenterWithPercent = computed(() => {
    const quantity = scopedMedicines.value.reduce(
        (sum, item) => sum + item.quantity,
        0,
    );

    return [
        {
            center: locationLabel.value?.split(',')[0] ?? 'This lugar',
            quantity,
            percent: quantity ? 100 : 0,
        },
    ];
});

const stockStatusMix = computed(() =>
    [
        {
            label: 'Available',
            value: scopedMedicines.value.filter(
                (item) => item.status === 'available',
            ).length,
            color: 'text-emerald-800 dark:text-emerald-300',
        },
        {
            label: 'Low',
            value: scopedMedicines.value.filter((item) => item.status === 'low')
                .length,
            color: 'text-amber-800 dark:text-amber-300',
        },
        {
            label: 'Out of stock',
            value: scopedMedicines.value.filter(
                (item) =>
                    item.status === 'out_of_stock' || item.status === 'expired',
            ).length,
            color: 'text-red-700 dark:text-red-300',
        },
    ].map((item) => ({
        ...item,
        percent: toPercent(item.value, scopedMedicines.value.length),
    })),
);

const weeklyBookings = [4, 6, 3, 8, 5, 2, 3];
const weeklySms = [2, 3, 4, 6, 3, 1, 2];
const weeklyBookingTotal = weeklyBookings.reduce(
    (sum, value) => sum + value,
    0,
);
const weeklySmsTotal = weeklySms.reduce((sum, value) => sum + value, 0);

const percentLabelPlugin = {
    id: 'percentLabel',
    afterDatasetsDraw(chart: ChartJS) {
        const { ctx } = chart;
        const dataset = chart.data.datasets[0];
        const values = dataset.data as number[];
        const total = values.reduce((sum, value) => sum + Number(value), 0);
        const meta = chart.getDatasetMeta(0);

        ctx.save();
        ctx.fillStyle = chartInk.value;
        ctx.font = '12px sans-serif';
        ctx.textAlign = 'center';
        ctx.textBaseline = 'middle';

        meta.data.forEach((element, index) => {
            const value = Number(values[index] ?? 0);

            if (!value) {
                return;
            }

            const position = element.tooltipPosition(true);
            ctx.fillText(`${toPercent(value, total)}%`, position.x, position.y);
        });

        ctx.restore();
    },
};

const requestChart = computed(() => ({
    labels: requestMixWithPercent.value.map(
        (item) => `${item.label} ${item.percent}%`,
    ),
    datasets: [
        {
            data: requestMixWithPercent.value.map((item) => item.value),
            backgroundColor: [
                'rgba(220, 38, 38, 0.35)',
                'rgba(220, 38, 38, 0.75)',
                'rgba(127, 29, 29, 0.85)',
                'rgba(254, 202, 202, 0.95)',
            ],
            borderColor: 'rgba(255, 255, 255, 0.9)',
            borderWidth: 2,
        },
    ],
}));

const stockChart = computed(() => ({
    labels: stockByCenterWithPercent.value.map(
        (row) => `${row.center} ${row.percent}%`,
    ),
    datasets: [
        {
            label: 'Units in stock',
            data: stockByCenterWithPercent.value.map((row) => row.quantity),
            backgroundColor: 'rgba(220, 38, 38, 0.55)',
            borderColor: 'rgba(220, 38, 38, 0.9)',
            borderWidth: 1,
            borderRadius: 6,
        },
    ],
}));

const weeklyChart = {
    labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
    datasets: [
        {
            label: 'Bookings',
            data: weeklyBookings,
            borderColor: 'rgba(220, 38, 38, 0.95)',
            backgroundColor: 'rgba(220, 38, 38, 0.15)',
            fill: true,
            tension: 0.35,
        },
        {
            label: 'SMS sent',
            data: weeklySms,
            borderColor: 'rgba(127, 29, 29, 0.85)',
            backgroundColor: 'rgba(254, 202, 202, 0.45)',
            fill: true,
            tension: 0.35,
        },
    ],
};

const chartOptions = computed(() => ({
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: {
            labels: {
                color: chartInk.value,
                font: { size: 12 },
            },
        },
        tooltip: {
            callbacks: {
                label(context: {
                    label?: string;
                    raw?: unknown;
                    dataset: { data: unknown[] };
                }) {
                    const value = Number(context.raw ?? 0);
                    const total = context.dataset.data.reduce(
                        (sum, item) => sum + Number(item ?? 0),
                        0,
                    );

                    return `${context.label}: ${value} (${toPercent(value, total)}%)`;
                },
            },
        },
    },
}));

const pieOptions = computed(() => chartOptions.value);

const barOptions = computed(() => ({
    ...chartOptions.value,
    plugins: {
        ...chartOptions.value.plugins,
        legend: { display: false },
    },
    scales: {
        x: {
            ticks: { color: chartInk.value },
            grid: { color: 'rgba(220, 38, 38, 0.08)' },
        },
        y: {
            beginAtZero: true,
            ticks: { color: chartInk.value, stepSize: 50 },
            grid: { color: 'rgba(220, 38, 38, 0.08)' },
        },
    },
}));

const lineOptions = computed(() => ({
    ...chartOptions.value,
    plugins: {
        ...chartOptions.value.plugins,
        tooltip: {
            callbacks: {
                label(context: {
                    dataset: { label?: string; data: unknown[] };
                    raw?: unknown;
                }) {
                    const value = Number(context.raw ?? 0);
                    const total = context.dataset.data.reduce(
                        (sum, item) => sum + Number(item ?? 0),
                        0,
                    );

                    return `${context.dataset.label}: ${value} (${toPercent(value, total)}%)`;
                },
            },
        },
    },
    scales: {
        x: {
            ticks: { color: chartInk.value },
            grid: { color: 'rgba(220, 38, 38, 0.08)' },
        },
        y: {
            beginAtZero: true,
            ticks: { color: chartInk.value, stepSize: 2 },
            grid: { color: 'rgba(220, 38, 38, 0.08)' },
        },
    },
}));
</script>

<template>
    <Head title="Admin Metrics" />

    <div class="flex min-w-0 flex-1 flex-col gap-6 p-3 sm:p-4 md:p-6">
        <AdminPageHeader
            title="Admin metrics"
            :description="`Snapshot of bookings and medicine stock for ${locationLabel || 'your health center'}.`"
            :icon="LineChart"
        />

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <Card class="border-l-4 border-l-red-500">
                <CardHeader>
                    <CardDescription class="text-black"
                        >Health centers</CardDescription
                    >
                    <CardTitle class="text-3xl text-black">1</CardTitle>
                </CardHeader>
            </Card>
            <Card class="border-l-4 border-l-emerald-500">
                <CardHeader>
                    <CardDescription class="text-black"
                        >Tracked medicines</CardDescription
                    >
                    <CardTitle class="text-3xl text-black">{{
                        scopedMedicines.length
                    }}</CardTitle>
                </CardHeader>
            </Card>
            <Card class="border-l-4 border-l-amber-500">
                <CardHeader>
                    <CardDescription class="text-black"
                        >Out of stock</CardDescription
                    >
                    <CardTitle class="text-3xl text-black">{{
                        scopedMedicines.filter(
                            (item) =>
                                item.status === 'out_of_stock' ||
                                item.status === 'expired',
                        ).length
                    }}</CardTitle>
                    <p class="text-sm text-black">
                        {{
                            toPercent(
                                scopedMedicines.filter(
                                    (item) =>
                                        item.status === 'out_of_stock' ||
                                        item.status === 'expired',
                                ).length,
                                scopedMedicines.length,
                            )
                        }}% of medicines
                    </p>
                </CardHeader>
            </Card>
            <Card class="border-l-4 border-l-blue-500">
                <CardHeader>
                    <CardDescription class="text-black"
                        >SMS this week</CardDescription
                    >
                    <CardTitle class="text-3xl text-black">{{
                        demoMetrics.smsSentThisWeek
                    }}</CardTitle>
                </CardHeader>
            </Card>
        </div>

        <div class="grid min-w-0 gap-4 lg:grid-cols-2">
            <Card>
                <CardHeader>
                    <CardTitle class="text-black">Request workflow</CardTitle>
                    <CardDescription class="text-black">
                        Approval and decline mix from demo records
                    </CardDescription>
                </CardHeader>
                <CardContent class="space-y-4">
                    <div v-if="ready" class="h-56 min-w-0 sm:h-72">
                        <Pie
                            :data="requestChart"
                            :options="pieOptions"
                            :plugins="[percentLabelPlugin]"
                            />
                        </div>
                    <div class="grid gap-2 sm:grid-cols-2">
                        <p
                            v-for="item in requestMixWithPercent"
                            :key="item.label"
                            class="text-sm text-black"
                        >
                            {{ item.label }}: {{ item.value }} ({{
                                item.percent
                            }}%)
                        </p>
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle class="text-black"
                        >Stock by health center</CardTitle
                    >
                    <CardDescription class="text-black">
                        Real-time availability preview
                    </CardDescription>
                </CardHeader>
                <CardContent class="space-y-4">
                    <div v-if="ready" class="h-56 min-w-0 sm:h-72">
                        <Bar
                            :data="stockChart"
                            :options="barOptions"
                            :plugins="[percentLabelPlugin]"
                            />
                        </div>
                    <div class="grid gap-2 sm:grid-cols-2">
                        <p
                            v-for="row in stockByCenterWithPercent"
                            :key="row.center"
                            class="text-sm text-black"
                        >
                            {{ row.center }}: {{ row.quantity }} units ({{
                                row.percent
                            }}%)
                        </p>
                    </div>
                </CardContent>
            </Card>
        </div>

        <div class="grid gap-4 sm:grid-cols-3">
            <Card v-for="item in stockStatusMix" :key="item.label">
                <CardHeader>
                    <CardDescription :class="item.color"
                        >{{ item.label }} stock</CardDescription
                    >
                    <CardTitle class="text-3xl text-black"
                        >{{ item.percent }}%</CardTitle
                    >
                    <p class="text-sm text-black">
                        {{ item.value }} of
                        {{ scopedMedicines.length }} medicines
                    </p>
                </CardHeader>
            </Card>
        </div>

        <Card>
            <CardHeader>
                <CardTitle class="text-black">Weekly activity</CardTitle>
                <CardDescription class="text-black">
                    Bookings {{ weeklyBookingTotal }} · SMS {{ weeklySmsTotal }}
                    (hover a point to see the daily percent)
                </CardDescription>
            </CardHeader>
            <CardContent>
                <div v-if="ready" class="h-56 min-w-0 sm:h-80">
                    <Line :data="weeklyChart" :options="lineOptions" />
                </div>
            </CardContent>
        </Card>
    </div>
</template>
