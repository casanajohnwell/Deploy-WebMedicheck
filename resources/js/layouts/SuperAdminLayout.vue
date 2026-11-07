<script setup lang="ts">
import { Form, Link, usePage } from '@inertiajs/vue3';
import {
    LayoutDashboard,
    LogOut,
    ShieldCheck,
    Smartphone,
    UserCog,
    UserPlus,
    UserX,
    Users,
} from '@lucide/vue';
import { computed } from 'vue';

const page = usePage();

const counts = computed(
    () =>
        (page.props.navCounts as { pending: number; rejected: number } | undefined) ?? {
            pending: 0,
            rejected: 0,
        },
);

const notice = computed(
    () =>
        (page.props.adminNotice as { id: number; message: string } | null) ??
        null,
);

const status = computed(() => (page.props.status as string | undefined) ?? '');

const links = computed(() => [
    {
        label: 'Dashboard',
        href: '/superadmin/dashboard',
        icon: LayoutDashboard,
        active: page.url.startsWith('/superadmin/dashboard'),
        badge: 0,
    },
    {
        label: 'NURSE accounts',
        href: '/superadmin/accounts',
        icon: Users,
        active:
            page.url.startsWith('/superadmin/accounts') &&
            !page.url.startsWith('/superadmin/accounts/walk-in') &&
            !page.url.includes('tab=rejected'),
        badge: counts.value.pending,
    },
    {
        label: 'User accounts',
        href: '/superadmin/users',
        icon: Smartphone,
        active: page.url.startsWith('/superadmin/users'),
        badge: 0,
    },
    {
        label: 'Walk-in registration',
        href: '/superadmin/accounts/walk-in',
        icon: UserPlus,
        active: page.url.startsWith('/superadmin/accounts/walk-in'),
        badge: 0,
    },
    {
        label: 'Rejected accounts',
        href: '/superadmin/accounts?tab=rejected',
        icon: UserX,
        active: page.url.includes('tab=rejected'),
        badge: counts.value.rejected,
    },
    {
        label: 'My account',
        href: '/superadmin/profile',
        icon: UserCog,
        active: page.url.startsWith('/superadmin/profile'),
        badge: 0,
    },
]);
</script>

<template>
    <div class="theme-light min-h-svh bg-[#fdf6f6] text-black">
        <div class="flex min-h-svh">
            <aside
                class="sticky top-0 hidden h-svh w-64 shrink-0 flex-col border-r border-red-100 bg-white lg:flex"
            >
                <div class="flex items-center gap-3 border-b border-red-100 px-5 py-5">
                    <img
                        src="/images/medicheck-logo.svg"
                        alt="MediCheck"
                        class="size-9"
                    />
                    <div>
                        <p class="font-semibold leading-tight">MediCheck</p>
                        <p class="flex items-center gap-1 text-xs text-red-700">
                            <ShieldCheck class="size-3" />
                            Super admin
                        </p>
                    </div>
                </div>

                <nav class="flex flex-1 flex-col gap-1 p-3">
                    <Link
                        v-for="link in links"
                        :key="link.href"
                        :href="link.href"
                        class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm transition-colors"
                        :class="
                            link.active
                                ? 'bg-red-600 text-white'
                                : 'text-neutral-700 hover:bg-red-50'
                        "
                    >
                        <component :is="link.icon" class="size-4" />
                        <span class="flex-1">{{ link.label }}</span>
                        <span
                            v-if="link.badge > 0"
                            class="rounded-full px-2 py-0.5 text-xs font-semibold"
                            :class="
                                link.active
                                    ? 'bg-white text-red-700'
                                    : 'bg-red-100 text-red-700'
                            "
                        >
                            {{ link.badge }}
                        </span>
                    </Link>
                </nav>

                <Form action="/superadmin/logout" method="post" class="border-t border-red-100 p-3">
                    <button
                        type="submit"
                        class="flex w-full items-center gap-3 rounded-lg px-3 py-2 text-sm text-neutral-700 hover:bg-red-50"
                    >
                        <LogOut class="size-4" />
                        Log out
                    </button>
                </Form>
            </aside>

            <div class="flex min-w-0 flex-1 flex-col">
                <header
                    class="sticky top-0 z-10 flex flex-col gap-3 border-b border-red-100 bg-white px-4 py-3 lg:hidden"
                >
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <img
                                src="/images/medicheck-logo.svg"
                                alt="MediCheck"
                                class="size-7"
                            />
                            <span class="font-semibold">Super admin</span>
                        </div>
                        <Form action="/superadmin/logout" method="post">
                            <button
                                type="submit"
                                class="flex items-center gap-1 rounded-md border border-red-200 px-2 py-1 text-xs"
                            >
                                <LogOut class="size-3" />
                                Log out
                            </button>
                        </Form>
                    </div>
                    <nav class="flex gap-2 overflow-x-auto">
                        <Link
                            v-for="link in links"
                            :key="link.href"
                            :href="link.href"
                            class="flex shrink-0 items-center gap-2 rounded-md px-3 py-1.5 text-xs"
                            :class="
                                link.active
                                    ? 'bg-red-600 text-white'
                                    : 'border border-red-200'
                            "
                        >
                            <component :is="link.icon" class="size-3.5" />
                            {{ link.label }}
                            <span v-if="link.badge > 0" class="font-semibold">
                                ({{ link.badge }})
                            </span>
                        </Link>
                    </nav>
                </header>

                <main class="mx-auto flex w-full max-w-6xl flex-col gap-6 p-4 sm:p-6 lg:p-8">
                    <div
                        v-if="notice"
                        class="flex flex-wrap items-center justify-between gap-2 rounded-xl border border-red-200 bg-white px-4 py-3 text-sm"
                        role="status"
                    >
                        <p>{{ notice.message }}</p>
                        <Form
                            :action="`/superadmin/notices/${notice.id}/read`"
                            method="post"
                        >
                            <button type="submit" class="font-medium text-red-700">
                                Dismiss
                            </button>
                        </Form>
                    </div>

                    <p
                        v-if="status"
                        class="rounded-xl border border-red-200 bg-red-600/5 px-4 py-3 text-sm font-medium text-red-800"
                        role="status"
                    >
                        {{ status }}
                    </p>

                    <slot />
                </main>
            </div>
        </div>
    </div>
</template>
