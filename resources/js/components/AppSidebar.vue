<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    Bell,
    ClipboardCheck,
    FileText,
    History,
    LayoutDashboard,
    LineChart,
    MapPin,
    Package,
} from '@lucide/vue';
import { computed } from 'vue';
import AppLogo from '@/components/AppLogo.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { useActiveAddress } from '@/composables/useActiveAddress';
import { useSavedBookings } from '@/composables/useSavedBookings';
import { useSavedMedicines } from '@/composables/useSavedMedicines';
import type { NavItem } from '@/types';

const { healthCenterFilter } = useActiveAddress();
const { bookings } = useSavedBookings();
const { medicines } = useSavedMedicines();

const pendingCount = computed(
    () => bookings.value.filter((item) => item.status === 'pending').length,
);

const stockAlertCount = computed(
    () =>
        medicines.value.filter((item) =>
            ['low', 'out_of_stock', 'expired'].includes(item.status),
        ).length,
);

const overviewItems: NavItem[] = [
    { title: 'Dashboard', href: '/admin/dashboard', icon: LayoutDashboard },
    { title: 'Metrics', href: '/admin/metrics', icon: LineChart },
];

const bookingItems = computed<NavItem[]>(() => [
    {
        title: 'Booking approval',
        href: '/admin/bookings/approval',
        icon: ClipboardCheck,
        badge: pendingCount.value,
    },
    {
        title: 'Booking history',
        href: '/admin/bookings/history',
        icon: History,
    },
]);

const stockItems = computed<NavItem[]>(() => [
    {
        title: 'Inventory',
        href: '/admin/inventory',
        icon: Package,
        badge: stockAlertCount.value,
    },
]);

const reportItems: NavItem[] = [
    { title: 'SMS notifications', href: '/admin/sms', icon: Bell },
    { title: 'Monthly report', href: '/admin/reports', icon: FileText },
];
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link href="/admin/dashboard">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
            <div
                v-if="healthCenterFilter"
                class="mx-1 mt-1 rounded-lg border border-red-100 bg-red-50 px-3 py-2 group-data-[collapsible=icon]:hidden"
            >
                <p class="text-[10px] font-semibold tracking-wider text-red-700 uppercase">
                    Your health center
                </p>
                <p class="mt-0.5 flex items-start gap-1 text-xs font-medium text-black">
                    <MapPin class="mt-0.5 size-3 shrink-0 text-red-600" />
                    <span class="break-words">{{ healthCenterFilter }}</span>
                </p>
            </div>
        </SidebarHeader>

        <SidebarContent class="gap-1">
            <NavMain :items="overviewItems" label="Overview" />
            <NavMain :items="bookingItems" label="Bookings" />
            <NavMain :items="stockItems" label="Medicine stock" />
            <NavMain :items="reportItems" label="Reports" />
        </SidebarContent>

        <SidebarFooter>
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
