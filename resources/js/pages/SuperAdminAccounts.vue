<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import {
    Check,
    Clock,
    MapPin,
    Trash2,
    UserCheck,
    UserPlus,
    UserX,
} from '@lucide/vue';
import { computed } from 'vue';
import RejectAccountForm from '@/components/superadmin/RejectAccountForm.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';

type Account = {
    id: number;
    name: string;
    email: string;
    position: string | null;
    barangay: string | null;
    city: string | null;
    province: string | null;
    health_center: string | null;
    is_active: boolean;
    walk_in: boolean;
};

type PendingAdmin = Omit<Account, 'is_active' | 'walk_in'>;

type RejectedAdmin = PendingAdmin & {
    previous_status: 'pending' | 'approved';
    reason: string | null;
    declined_at: string | null;
};

const props = defineProps<{
    accounts: Account[];
    pendingAdmins: PendingAdmin[];
    declinedAdmins: RejectedAdmin[];
    tab: 'approved' | 'pending' | 'rejected';
}>();

const tabs = computed(() => [
    { key: 'approved', label: 'Approved', count: props.accounts.length, icon: UserCheck },
    { key: 'pending', label: 'Pending', count: props.pendingAdmins.length, icon: Clock },
    { key: 'rejected', label: 'Rejected', count: props.declinedAdmins.length, icon: UserX },
]);

function initials(name: string) {
    return name
        .split(/\s+/)
        .filter(Boolean)
        .slice(0, 2)
        .map((part) => part[0]?.toUpperCase())
        .join('');
}

function place(account: PendingAdmin) {
    return [account.health_center || account.barangay, account.city]
        .filter(Boolean)
        .join(' · ');
}

function confirmRemove(event: Event, name: string) {
    if (!window.confirm(`Remove the rejected record of ${name}? This cannot be undone.`)) {
        event.preventDefault();
    }
}

function confirmDelete(event: Event, name: string) {
    if (!window.confirm(`Delete the account of ${name}? This cannot be undone.`)) {
        event.preventDefault();
    }
}
</script>

<template>
    <Head title="NURSE accounts" />

    <div class="flex flex-wrap items-start justify-between gap-3">
        <div class="flex flex-col gap-1">
            <h1 class="text-2xl font-semibold">NURSE accounts</h1>
            <p class="text-sm text-neutral-600">
                The nurse creates the account online, or the super admin fills
                it in for a walk-in nurse. The super admin approves, rejects,
                turns off, or deletes it.
            </p>
        </div>
        <Link
            href="/superadmin/accounts/walk-in"
            class="flex items-center gap-1.5 rounded-md bg-red-600 px-3 py-2 text-sm text-white hover:bg-red-700"
        >
            <UserPlus class="size-4" />
            Walk-in registration
        </Link>
    </div>

    <div class="flex gap-2 overflow-x-auto rounded-xl border border-red-100 bg-white p-1.5">
        <Link
            v-for="item in tabs"
            :key="item.key"
            :href="`/superadmin/accounts?tab=${item.key}`"
            preserve-scroll
            class="flex flex-1 shrink-0 items-center justify-center gap-2 rounded-lg px-3 py-2 text-sm font-medium"
            :class="tab === item.key ? 'bg-red-600 text-white' : 'text-neutral-700 hover:bg-red-50'"
        >
            <component :is="item.icon" class="size-4" />
            {{ item.label }}
            <span
                class="rounded-full px-2 py-0.5 text-xs"
                :class="tab === item.key ? 'bg-white text-red-700' : 'bg-red-100 text-red-700'"
            >
                {{ item.count }}
            </span>
        </Link>
    </div>

    <section v-if="tab === 'approved'" class="grid gap-3">
        <p
            v-if="accounts.length === 0"
            class="rounded-xl border border-dashed border-red-200 bg-white p-8 text-center text-sm text-neutral-600"
        >
            No approved NURSE account yet.
        </p>
        <article
            v-for="account in accounts"
            :key="account.id"
            class="rounded-xl border border-red-100 bg-white p-4 shadow-sm"
        >
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div class="flex items-center gap-3">
                    <span
                        class="flex size-10 items-center justify-center rounded-full bg-red-100 text-sm font-semibold text-red-700"
                    >
                        {{ initials(account.name) }}
                    </span>
                    <div>
                        <p class="font-semibold">{{ account.name }}</p>
                        <p class="flex items-center gap-1 text-xs text-neutral-600">
                            <MapPin class="size-3" />
                            {{ place(account) || 'No health center' }} ·
                            {{ account.position }}
                        </p>
                    </div>
                </div>
                <div class="flex flex-wrap gap-1.5">
                    <span
                        v-if="account.walk_in"
                        class="rounded-full bg-blue-100 px-2.5 py-1 text-xs font-medium text-blue-800"
                    >
                        Walk-in
                    </span>
                    <span
                        class="rounded-full px-2.5 py-1 text-xs font-medium"
                        :class="account.is_active ? 'bg-green-100 text-green-800' : 'bg-neutral-200 text-neutral-700'"
                    >
                        {{ account.is_active ? 'Active' : 'Turned off' }}
                    </span>
                </div>
            </div>

            <Form
                :action="`/superadmin/accounts/${account.id}`"
                method="put"
                class="mt-4 grid gap-3 sm:grid-cols-[1fr_1fr_auto_auto] sm:items-center"
            >
                <Input name="name" :default-value="account.name" required />
                <Input name="email" type="email" :default-value="account.email" required />
                <label class="flex items-center gap-2 text-sm">
                    <input type="hidden" name="is_active" value="0" />
                    <input
                        type="checkbox"
                        name="is_active"
                        value="1"
                        :checked="account.is_active"
                        class="accent-red-600"
                    />
                    Account is on
                </label>
                <Button type="submit" class="w-fit bg-red-600 text-white hover:bg-red-700">
                    Save
                </Button>
            </Form>

            <div class="mt-3 flex flex-wrap items-start gap-2 border-t border-red-50 pt-3">
                <RejectAccountForm
                    :user-id="account.id"
                    :name="account.name"
                    label="Reject account"
                />
                <Form :action="`/superadmin/accounts/${account.id}`" method="delete">
                    <button
                        type="submit"
                        class="flex items-center gap-1.5 rounded-md px-3 py-2 text-sm text-neutral-600 hover:bg-neutral-100"
                        @click="confirmDelete($event, account.name)"
                    >
                        <Trash2 class="size-4" />
                        Delete account
                    </button>
                </Form>
            </div>
        </article>
    </section>

    <section v-else-if="tab === 'pending'" class="grid gap-3">
        <p
            v-if="pendingAdmins.length === 0"
            class="rounded-xl border border-dashed border-red-200 bg-white p-8 text-center text-sm text-neutral-600"
        >
            No barangay administrator is waiting.
        </p>
        <article
            v-for="admin in pendingAdmins"
            :key="admin.id"
            class="rounded-xl border border-red-100 bg-white p-4 shadow-sm"
        >
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div class="flex items-center gap-3">
                    <span
                        class="flex size-10 items-center justify-center rounded-full bg-amber-100 text-sm font-semibold text-amber-800"
                    >
                        {{ initials(admin.name) }}
                    </span>
                    <div>
                        <p class="font-semibold">{{ admin.name }}</p>
                        <p class="text-xs text-neutral-600">
                            {{ admin.email }} · {{ admin.position }}
                        </p>
                        <p class="flex items-center gap-1 text-xs text-neutral-600">
                            <MapPin class="size-3" />
                            {{ place(admin) }}
                        </p>
                    </div>
                </div>
                <span class="rounded-full bg-amber-100 px-2.5 py-1 text-xs font-medium text-amber-800">
                    Waiting for MHO approval
                </span>
            </div>
            <div class="mt-3 flex flex-wrap items-start gap-2 border-t border-red-50 pt-3">
                <Form :action="`/superadmin/accounts/${admin.id}/approve`" method="post">
                    <button
                        type="submit"
                        class="flex items-center gap-1.5 rounded-md bg-red-600 px-3 py-2 text-sm text-white hover:bg-red-700"
                    >
                        <Check class="size-4" />
                        Approve
                    </button>
                </Form>
                <RejectAccountForm :user-id="admin.id" :name="admin.name" />
            </div>
        </article>
    </section>

    <section v-else class="grid gap-3">
        <p class="text-sm text-neutral-600">
            A rejected nurse can register again for that barangay. Removing a
            record only clears it from this list.
        </p>
        <p
            v-if="declinedAdmins.length === 0"
            class="rounded-xl border border-dashed border-red-200 bg-white p-8 text-center text-sm text-neutral-600"
        >
            No rejected account yet.
        </p>
        <article
            v-for="admin in declinedAdmins"
            :key="admin.id"
            class="rounded-xl border border-red-100 bg-white p-4 shadow-sm"
        >
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div class="flex items-center gap-3">
                    <span
                        class="flex size-10 items-center justify-center rounded-full bg-neutral-200 text-sm font-semibold text-neutral-700"
                    >
                        {{ initials(admin.name) }}
                    </span>
                    <div>
                        <p class="font-semibold">{{ admin.name }}</p>
                        <p class="text-xs text-neutral-600">
                            {{ admin.email }} · {{ admin.position }}
                        </p>
                        <p class="flex items-center gap-1 text-xs text-neutral-600">
                            <MapPin class="size-3" />
                            {{ place(admin) }}
                        </p>
                    </div>
                </div>
                <div class="flex flex-col items-end gap-1">
                    <span class="rounded-full bg-red-100 px-2.5 py-1 text-xs font-medium text-red-800">
                        {{ admin.previous_status === 'approved' ? 'Rejected after approval' : 'Rejected at sign-up' }}
                    </span>
                    <span v-if="admin.declined_at" class="text-xs text-neutral-500">
                        {{ admin.declined_at }}
                    </span>
                </div>
            </div>
            <p class="mt-3 rounded-lg bg-red-50/70 px-3 py-2 text-sm">
                <span class="font-medium">Reason:</span>
                {{ admin.reason || 'No reason given.' }}
            </p>
            <Form
                :action="`/superadmin/rejected/${admin.id}`"
                method="delete"
                class="mt-3"
            >
                <button
                    type="submit"
                    class="flex items-center gap-1.5 rounded-md px-3 py-2 text-sm text-neutral-600 hover:bg-neutral-100"
                    @click="confirmRemove($event, admin.name)"
                >
                    <Trash2 class="size-4" />
                    Remove record
                </button>
            </Form>
        </article>
    </section>
</template>
