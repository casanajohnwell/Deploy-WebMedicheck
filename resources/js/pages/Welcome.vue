<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { login, logout, register } from '@/routes';

function goToRegister() {
    if (usePage().props.auth.user) {
        router.flushAll();
        router.post(
            logout.url(),
            {},
            {
                onFinish: () => router.visit(register.url()),
            },
        );
        return;
    }

    router.visit(register.url());
}
</script>

<template>
    <Head title="MediCheck" />

    <div
        class="theme-light min-h-screen bg-[radial-gradient(circle_at_top,rgba(220,38,38,0.16),white_42%),radial-gradient(circle_at_bottom_right,rgba(255,255,255,0.95),white_36%)] text-black"
    >
        <header
            class="mx-auto flex w-full max-w-5xl flex-col gap-4 px-4 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6 sm:py-5"
        >
            <div class="flex items-center gap-3">
                <img
                    src="/images/medicheck-logo.svg"
                    alt="MediCheck"
                    class="size-10 shrink-0"
                />
                <div class="min-w-0">
                    <p class="text-lg font-bold tracking-wide text-black">
                        MEDICHECK
                    </p>
                    <p
                        class="text-[10px] tracking-[0.18em] text-black uppercase"
                    >
                        Medical Diagnostics & Screening
                    </p>
                </div>
            </div>
            <div
                class="flex w-full flex-col gap-2 sm:w-auto sm:flex-row sm:flex-wrap sm:items-center sm:gap-3"
            >
                <Link
                    :href="login()"
                    class="rounded-md border border-red-200 bg-white px-4 py-2 text-center text-sm font-medium text-black"
                >
                    Log in
                </Link>
                <button
                    type="button"
                    class="rounded-md bg-red-600/80 px-4 py-2 text-center text-sm font-medium text-white"
                    @click="goToRegister"
                >
                    Create account
                </button>
            </div>
        </header>

        <main class="mx-auto w-full max-w-5xl px-4 py-8 sm:px-6 sm:py-12">
            <div class="space-y-5">
                <p
                    class="text-sm font-medium tracking-wide text-black uppercase"
                >
                    Barangay health center console
                </p>
                <h1
                    class="text-2xl font-semibold tracking-tight text-black sm:text-4xl"
                >
                    Medicine availability, bookings, and inventory in one place.
                </h1>
                <p class="max-w-xl text-black">
                    Admins can review medicine requests, monitor stock and
                    expiration dates, and send SMS updates to medicine seekers.
                </p>
                </div>
            </main>
    </div>
</template>
