<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { ChevronRight, UserPlus } from '@lucide/vue';
import { computed, reactive, ref } from 'vue';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import ShopeeLocationPicker from '@/components/ShopeeLocationPicker.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { postalCodeFor, type FlatLocation } from '@/lib/ph-locations';

defineProps<{
    passwordRules: string;
    positions: string[];
}>();

const pickerOpen = ref(false);
const fullName = ref('');
const position = ref('');
const phone = ref('');
const identityChecked = ref(false);

const address = reactive({
    region: '',
    province: '',
    city: '',
    barangay: '',
    postalCode: '',
    street: '',
});

const locationText = computed(() => {
    if (!address.barangay) {
        return '';
    }

    return `${address.region}, ${address.province}, ${address.city}, ${address.barangay}`;
});

const canSubmit = computed(
    () =>
        Boolean(fullName.value.trim()) &&
        Boolean(position.value.trim()) &&
        Boolean(phone.value.trim()) &&
        Boolean(address.barangay) &&
        Boolean(address.street.trim()) &&
        identityChecked.value,
);

function applyLocation(value: FlatLocation) {
    address.region = value.region;
    address.province = value.province;
    address.city = value.city;
    address.barangay = value.barangay;
    address.postalCode = value.postalCode || postalCodeFor(value);
    pickerOpen.value = false;
}
</script>

<template>
    <Head title="Walk-in registration" />

    <div class="flex flex-wrap items-start justify-between gap-3">
        <div class="flex flex-col gap-1">
            <h1 class="flex items-center gap-2 text-2xl font-semibold">
                <UserPlus class="size-6 text-red-700" />
                Walk-in registration
            </h1>
            <p class="max-w-2xl text-sm text-neutral-600">
                Use this when the barangay nurse comes to the MHO office in
                person. Fill in the nurse's details. The account is approved
                right away and the nurse can sign in with this email and
                password.
            </p>
        </div>
        <Link
            href="/superadmin/accounts"
            class="rounded-md border border-red-200 bg-white px-3 py-2 text-sm hover:bg-red-50"
        >
            Back to accounts
        </Link>
    </div>

    <Form
        action="/superadmin/accounts/walk-in"
        method="post"
        :reset-on-success="['password', 'password_confirmation']"
        v-slot="{ errors, processing }"
        class="grid gap-6 rounded-xl border border-red-100 bg-white p-4 shadow-sm sm:p-6 lg:grid-cols-2"
        autocomplete="off"
    >
        <div class="flex flex-col gap-4">
            <h2 class="font-semibold">Nurse details</h2>
            <div class="grid gap-2">
                <Label for="name">Full name</Label>
                <Input id="name" v-model="fullName" name="name" required placeholder="Full name" />
                <InputError :message="errors.name" />
            </div>
            <div class="grid gap-2">
                <Label for="position">Position</Label>
                <select
                    id="position"
                    v-model="position"
                    name="position"
                    required
                    class="border-input h-9 w-full rounded-md border bg-transparent px-3 text-sm"
                >
                    <option value="" disabled>Select position</option>
                    <option v-for="item in positions" :key="item" :value="item">
                        {{ item }}
                    </option>
                </select>
                <InputError :message="errors.position" />
            </div>
            <div class="grid gap-2">
                <Label for="phone">Contact number</Label>
                <Input id="phone" v-model="phone" name="phone" type="tel" required placeholder="09xxxxxxxxx" />
                <InputError :message="errors.phone" />
            </div>

            <input type="hidden" name="region" :value="address.region" />
            <input type="hidden" name="province" :value="address.province" />
            <input type="hidden" name="city" :value="address.city" />
            <input type="hidden" name="barangay" :value="address.barangay" />
            <input type="hidden" name="postal_code" :value="address.postalCode" />
            <input type="hidden" name="street" :value="address.street" />

            <div class="overflow-hidden rounded-lg border border-neutral-200 bg-white">
                <p class="px-3 pt-3 text-sm font-semibold">Barangay health center address</p>
                <button
                    type="button"
                    class="flex h-11 w-full items-center justify-between border-b border-neutral-100 px-3 text-left text-sm"
                    @click="pickerOpen = true"
                >
                    <span class="min-w-0 truncate" :class="locationText ? 'text-black' : 'text-neutral-400'">
                        {{ locationText || 'Region, Province, City, Barangay' }}
                    </span>
                    <ChevronRight class="size-4 text-neutral-400" />
                </button>
                <input
                    v-model="address.postalCode"
                    readonly
                    class="h-11 w-full border-b border-neutral-100 bg-neutral-50 px-3 text-sm outline-none"
                    placeholder="Postal Code"
                />
                <input
                    v-model="address.street"
                    required
                    class="h-11 w-full px-3 text-sm outline-none"
                    placeholder="Street Name, Building, House No."
                />
            </div>
            <InputError :message="errors.barangay" />
        </div>

        <div class="flex flex-col gap-4">
            <h2 class="font-semibold">Sign-in details</h2>
            <div class="grid gap-2">
                <Label for="email">Email address</Label>
                <Input id="email" type="email" name="email" required placeholder="email@example.com" />
                <InputError :message="errors.email" />
            </div>
            <div class="grid gap-2">
                <Label for="password">Password</Label>
                <PasswordInput
                    id="password"
                    name="password"
                    required
                    autocomplete="new-password"
                    placeholder="Password"
                    :passwordrules="passwordRules"
                />
                <InputError :message="errors.password" />
            </div>
            <div class="grid gap-2">
                <Label for="password_confirmation">Confirm password</Label>
                <PasswordInput
                    id="password_confirmation"
                    name="password_confirmation"
                    required
                    autocomplete="new-password"
                    placeholder="Confirm password"
                    :passwordrules="passwordRules"
                />
                <InputError :message="errors.password_confirmation" />
            </div>

            <p class="rounded-lg bg-red-600/10 px-3 py-2 text-xs">
                Give the email and password to the nurse. The nurse can change
                the password after signing in. Only one active administrator is
                allowed per barangay.
            </p>

            <label class="flex items-start gap-2 text-sm">
                <input
                    v-model="identityChecked"
                    type="checkbox"
                    name="identity_checked"
                    value="1"
                    class="mt-1 size-4 accent-red-600"
                    required
                />
                <span>
                    I checked the nurse in person and confirm they are the
                    authorized administrator of this barangay health center.
                </span>
            </label>
            <InputError :message="errors.identity_checked" />

            <Button
                type="submit"
                class="mt-auto w-full"
                :class="canSubmit ? 'bg-red-600 text-white hover:bg-red-700' : 'bg-neutral-300 text-white'"
                :disabled="processing || !canSubmit"
            >
                <Spinner v-if="processing" />
                Create and approve account
            </Button>
        </div>
    </Form>

    <ShopeeLocationPicker :open="pickerOpen" @close="pickerOpen = false" @select="applyLocation" />
</template>
