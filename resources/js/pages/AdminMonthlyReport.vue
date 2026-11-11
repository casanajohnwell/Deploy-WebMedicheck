<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3';
import {
    CheckCircle2,
    ChevronDown,
    ChevronLeft,
    ChevronRight,
    ClipboardList,
    Clock,
    Download,
    FileText,
    PackageCheck,
    FileDown,
    PackageX,
    ShieldAlert,
    Trophy,
    UserX,
    XCircle,
} from '@lucide/vue';
import AdminPageHeader from '@/components/AdminPageHeader.vue';
import { computed, ref } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { toast } from 'vue-sonner';

type ReportLine = {
    id: number;
    date: string;
    seeker: string;
    medicine: string;
    quantity: number;
    status: string;
};

type MostTaken = {
    medicine: string;
    quantity: number;
    times: number;
};

type ReportSummary = {
    bookings: number;
    pending: number;
    approved: number;
    declined: number;
    released?: number;
    cancelled?: number;
    claimed?: number;
    unclaimed?: number;
    low_stock: number;
    out_of_stock: number;
    most_taken?: MostTaken | null;
    lines: ReportLine[];
};

type MonthlyReport = {
    id: number;
    healthCenter: string;
    label: string;
    year: number;
    month: number;
    summary: ReportSummary;
};

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

const props = defineProps<{
    year: number;
    month: number;
    report: MonthlyReport;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: '/admin/dashboard' },
            { title: 'Monthly report', href: '/admin/reports' },
        ],
    },
});

const report = computed(() => props.report);
const summary = computed(() => report.value?.summary);
const monthTitle = computed(() => `${months[props.month - 1]} ${props.year}`);

const monthOpen = ref(false);

const statCards = computed(() => {
    const current = summary.value;

    if (!current) {
        return [];
    }

    return [
        { label: 'Bookings', value: current.bookings, icon: ClipboardList, tone: 'bg-slate-100 text-slate-700', border: 'border-l-slate-400', status: 'all' },
        { label: 'Approved', value: current.approved, icon: CheckCircle2, tone: STATUS_STYLES.approved.icon, border: STATUS_STYLES.approved.border, status: 'approved' },
        { label: 'Claimed', value: current.claimed ?? current.released ?? 0, icon: PackageCheck, tone: STATUS_STYLES.claimed.icon, border: STATUS_STYLES.claimed.border, status: 'claimed' },
        { label: 'Declined', value: current.declined, icon: XCircle, tone: STATUS_STYLES.declined.icon, border: STATUS_STYLES.declined.border, status: 'declined' },
        { label: 'Pending', value: current.pending, icon: Clock, tone: STATUS_STYLES.pending.icon, border: STATUS_STYLES.pending.border, status: 'pending' },
        { label: 'Unclaimed', value: current.unclaimed ?? current.cancelled ?? 0, icon: UserX, tone: STATUS_STYLES.unclaimed.icon, border: STATUS_STYLES.unclaimed.border, status: 'unclaimed' },
        { label: 'Low stock', value: current.low_stock, icon: ShieldAlert, tone: 'bg-orange-100 text-orange-700', border: 'border-l-orange-500', status: null },
        { label: 'Out of stock', value: current.out_of_stock, icon: PackageX, tone: 'bg-rose-100 text-rose-700', border: 'border-l-rose-600', status: null },
    ];
});

const STATUS_STYLES = {
    pending: {
        badge: 'border-amber-200 bg-amber-100 text-amber-800',
        row: 'bg-amber-50/70 border-l-amber-400',
        icon: 'bg-amber-100 text-amber-800',
        border: 'border-l-amber-400',
    },
    approved: {
        badge: 'border-emerald-200 bg-emerald-100 text-emerald-800',
        row: 'bg-emerald-50/70 border-l-emerald-500',
        icon: 'bg-emerald-100 text-emerald-700',
        border: 'border-l-emerald-500',
    },
    claimed: {
        badge: 'border-blue-200 bg-blue-100 text-blue-800',
        row: 'bg-blue-50/70 border-l-blue-500',
        icon: 'bg-blue-100 text-blue-700',
        border: 'border-l-blue-500',
    },
    declined: {
        badge: 'border-red-200 bg-red-100 text-red-800',
        row: 'bg-red-50/70 border-l-red-500',
        icon: 'bg-red-100 text-red-700',
        border: 'border-l-red-500',
    },
    unclaimed: {
        badge: 'border-violet-200 bg-violet-100 text-violet-800',
        row: 'bg-violet-50/70 border-l-violet-500',
        icon: 'bg-violet-100 text-violet-700',
        border: 'border-l-violet-500',
    },
} as const;

type StatusKey = keyof typeof STATUS_STYLES;

function statusKey(status: string): StatusKey {
    if (status === 'released') return 'claimed';
    if (status === 'cancelled') return 'unclaimed';

    return status in STATUS_STYLES ? (status as StatusKey) : 'pending';
}

const statusFilter = ref<'all' | StatusKey>('all');

const visibleLines = computed(() =>
    (summary.value?.lines ?? []).filter(
        (line) => statusFilter.value === 'all' || statusKey(line.status) === statusFilter.value,
    ),
);

function selectCard(status: string | null): void {
    if (status === null) return;
    statusFilter.value = statusFilter.value === status ? 'all' : (status as 'all' | StatusKey);
}

function openMonth(year: number, month: number): void {
    monthOpen.value = false;
    router.get(
        '/admin/reports',
        { year, month },
        { preserveScroll: true, replace: true },
    );
}
const mostTaken = computed((): MostTaken | null => {
    const current = summary.value;

    if (!current) {
        return null;
    }

    if ('most_taken' in current) {
        return current.most_taken ?? null;
    }

    const totals = new Map<string, MostTaken>();

    for (const line of current.lines) {
        if (
            line.status !== 'approved' &&
            line.status !== 'claimed' &&
            line.status !== 'released'
        ) {
            continue;
        }

        const existing = totals.get(line.medicine) ?? {
            medicine: line.medicine,
            quantity: 0,
            times: 0,
        };

        existing.quantity += Number(line.quantity);
        existing.times += 1;
        totals.set(line.medicine, existing);
    }

    return [...totals.values()].sort(
        (left, right) =>
            right.quantity - left.quantity ||
            right.times - left.times ||
            left.medicine.localeCompare(right.medicine),
    )[0] ?? null;
});

const page = usePage();
const downloadingPdf = ref(false);

async function downloadPdf(): Promise<void> {
    const current = summary.value;

    if (!current) {
        return;
    }

    downloadingPdf.value = true;

    try {
        const { downloadMonthlyReportPdf } = await import('@/lib/monthlyReportPdf');

        await downloadMonthlyReportPdf({
            healthCenter: report.value.healthCenter,
            monthTitle: monthTitle.value,
            fileMonth: `${props.year}-${String(props.month).padStart(2, '0')}`,
            preparedBy: String(page.props.auth?.user?.name ?? ''),
            mostTaken: mostTaken.value,
            summaryRows: statCards.value.map((card) => [card.label, Number(card.value)]),
            lines: current.lines,
        });
    } catch {
        toast.error('The PDF could not be created. Please try again.');
    } finally {
        downloadingPdf.value = false;
    }
}
</script>

<template>
    <Head title="Monthly report" />

    <div class="flex min-w-0 flex-1 flex-col gap-6 p-3 sm:p-4 md:p-6">
        <AdminPageHeader
            title="Monthly report"
            :icon="FileText"
        >
            <template #actions>
        <div class="flex flex-wrap items-center gap-2">
            <button
                type="button"
                aria-label="Previous year"
                class="inline-flex size-9 items-center justify-center rounded-md border border-red-200/60 bg-card text-black"
                @click="openMonth(year - 1, month)"
            >
                <ChevronLeft class="size-4" />
            </button>
            <span class="min-w-14 text-center text-sm font-semibold text-black">
                {{ year }}
            </span>
            <button
                type="button"
                aria-label="Next year"
                class="inline-flex size-9 items-center justify-center rounded-md border border-red-200/60 bg-card text-black"
                @click="openMonth(year + 1, month)"
            >
                <ChevronRight class="size-4" />
            </button>
            <div class="relative">
                <button
                    type="button"
                    class="inline-flex h-9 min-w-40 items-center justify-between gap-2 rounded-md border border-red-200/60 bg-card px-3 text-sm font-medium text-black"
                    aria-label="Month"
                    aria-haspopup="listbox"
                    :aria-expanded="monthOpen"
                    @click="monthOpen = !monthOpen"
                >
                    {{ months[month - 1] }}
                    <ChevronDown class="size-4" />
                </button>
                <button
                    v-if="monthOpen"
                    type="button"
                    class="fixed inset-0 z-10 cursor-default"
                    aria-label="Close month list"
                    tabindex="-1"
                    @click="monthOpen = false"
                />
                <ul
                    v-if="monthOpen"
                    class="absolute z-20 mt-1 w-full rounded-md border border-red-200/60 bg-white py-1 shadow-md"
                    role="listbox"
                    aria-label="Month"
                >
                    <li v-for="(name, index) in months" :key="name">
                        <button
                            type="button"
                            class="w-full px-3 py-1.5 text-left text-sm"
                            role="option"
                            :aria-selected="index + 1 === month"
                            :class="
                                index + 1 === month
                                    ? 'bg-red-600 font-semibold text-white'
                                    : 'text-black hover:bg-red-50'
                            "
                            @click="openMonth(year, index + 1)"
                        >
                            {{ name }}
                        </button>
                    </li>
                </ul>
            </div>
            <button
                type="button"
                class="inline-flex h-9 items-center gap-2 rounded-md bg-red-600 px-3 text-sm font-medium text-white hover:bg-red-700 disabled:opacity-60"
                :disabled="!summary || downloadingPdf"
                @click="downloadPdf"
            >
                <FileDown class="size-4" />
                {{ downloadingPdf ? 'Preparing…' : 'Download PDF' }}
            </button>
            <a
                :href="`/admin/reports/export?year=${year}&month=${month}`"
                class="inline-flex h-9 items-center gap-2 rounded-md border border-red-200/60 bg-card px-3 text-sm font-medium text-black hover:bg-red-50"
            >
                <Download class="size-4" />
                Excel
            </a>
        </div>
            </template>
        </AdminPageHeader>

        <Card
            v-if="summary"
            class="overflow-hidden border-0 bg-gradient-to-br from-red-600 to-red-800 text-white"
        >
            <CardContent class="flex items-center gap-4 p-5">
                <span class="flex size-12 shrink-0 items-center justify-center rounded-xl bg-white/15">
                    <Trophy class="size-6" />
                </span>
                <div class="min-w-0">
                    <p class="text-xs tracking-wide text-red-100 uppercase">Most taken medicine</p>
                <p v-if="mostTaken" class="text-2xl font-semibold">
                    {{ mostTaken.medicine }}
                </p>
                <p v-if="mostTaken" class="text-sm text-red-50">
                    {{ mostTaken.quantity }}
                    {{ mostTaken.quantity === 1 ? 'piece' : 'pieces' }}
                    taken
                    {{ mostTaken.times }}
                    {{ mostTaken.times === 1 ? 'time' : 'times' }}
                </p>
                <p v-else class="text-sm text-red-50">
                    No medicine was taken this month.
                </p>
                </div>
            </CardContent>
        </Card>

        <div v-if="summary" class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            <Card
                v-for="card in statCards"
                :key="card.label"
                class="border-l-4 border-red-100 transition"
                :class="[
                    card.border,
                    card.status ? 'cursor-pointer hover:shadow-md' : '',
                    card.status && statusFilter === card.status && card.status !== 'all'
                        ? 'ring-2 ring-red-500/60'
                        : '',
                ]"
                @click="selectCard(card.status)"
            >
                <CardContent class="flex items-center gap-3 p-4">
                    <span
                        class="flex size-10 shrink-0 items-center justify-center rounded-lg"
                        :class="card.tone"
                    >
                        <component :is="card.icon" class="size-5" />
                    </span>
                    <div>
                        <p class="text-muted-foreground text-xs">{{ card.label }}</p>
                        <p class="text-2xl font-semibold text-black">{{ card.value }}</p>
                    </div>
                </CardContent>
            </Card>
        </div>

        <Card v-if="summary" class="border-red-100">
            <CardHeader class="gap-3">
                <CardTitle>Records for {{ monthTitle }}</CardTitle>
                <div class="flex flex-wrap gap-2">
                    <button
                        type="button"
                        class="rounded-full border px-3 py-1 text-xs font-medium transition"
                        :class="statusFilter === 'all' ? 'border-slate-700 bg-slate-800 text-white' : 'border-slate-200 bg-white text-slate-700 hover:bg-slate-50'"
                        @click="statusFilter = 'all'"
                    >
                        All · {{ summary.lines.length }}
                    </button>
                    <button
                        v-for="(style, key) in STATUS_STYLES"
                        :key="key"
                        type="button"
                        class="rounded-full border px-3 py-1 text-xs font-medium capitalize transition"
                        :class="[style.badge, statusFilter === key ? 'ring-2 ring-offset-1 ring-current' : 'opacity-80 hover:opacity-100']"
                        @click="statusFilter = statusFilter === key ? 'all' : key"
                    >
                        {{ key }} ·
                        {{ summary.lines.filter((line) => statusKey(line.status) === key).length }}
                    </button>
                </div>
            </CardHeader>
            <CardContent class="space-y-2">
                <p
                    v-if="!summary.lines.length"
                    class="rounded-lg border border-dashed border-red-200 p-8 text-center text-sm text-neutral-600"
                >
                    No bookings were recorded for this month.
                </p>
                <div
                    v-else
                    class="overflow-x-auto rounded-lg border border-red-100"
                >
                    <table class="w-full min-w-[560px] text-left text-sm">
                        <thead class="bg-red-50 text-xs tracking-wide text-neutral-600 uppercase">
                            <tr>
                                <th class="px-4 py-2.5 font-medium">Date</th>
                                <th class="px-4 py-2.5 font-medium">Seeker</th>
                                <th class="px-4 py-2.5 font-medium">Medicine</th>
                                <th class="px-4 py-2.5 font-medium">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="!visibleLines.length">
                                <td colspan="4" class="px-4 py-6 text-center text-neutral-500">
                                    No {{ statusFilter }} records this month.
                                </td>
                            </tr>
                            <tr
                                v-for="line in visibleLines"
                                :key="line.id"
                                class="border-t border-l-4 border-t-white"
                                :class="STATUS_STYLES[statusKey(line.status)].row"
                            >
                                <td class="px-4 py-2.5">{{ line.date }}</td>
                                <td class="px-4 py-2.5">{{ line.seeker }}</td>
                                <td class="px-4 py-2.5 font-medium">
                                    {{ line.medicine }}
                                    <span class="text-muted-foreground font-normal">× {{ line.quantity }}</span>
                                </td>
                                <td class="px-4 py-2.5">
                                    <Badge
                                        variant="outline"
                                        class="capitalize"
                                        :class="STATUS_STYLES[statusKey(line.status)].badge"
                                    >
                                        {{ statusKey(line.status) }}
                                    </Badge>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </CardContent>
        </Card>
    </div>
</template>
