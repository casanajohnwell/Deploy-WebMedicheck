<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { ChevronRight } from '@lucide/vue';
import { computed, reactive, ref } from 'vue';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import ShopeeLocationPicker from '@/components/ShopeeLocationPicker.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { useActiveAddress } from '@/composables/useActiveAddress';
import { postalCodeFor, type FlatLocation } from '@/lib/ph-locations';
import { login } from '@/routes';
import { store } from '@/routes/register';

defineProps<{
    passwordRules: string;
    positions: string[];
}>();

defineOptions({
    layout: {
        title: 'Create an account',
        description:
            'Administrator details are encrypted and only visible to this account',
        wide: true,
    },
});

const { saveAddress } = useActiveAddress();
const pickerOpen = ref(false);
const fullName = ref('');
const position = ref('');
const phone = ref('');
const isAuthorizedAdmin = ref(false);

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
        isAuthorizedAdmin.value,
);

function applyLocation(value: FlatLocation) {
    address.region = value.region;
    address.province = value.province;
    address.city = value.city;
    address.barangay = value.barangay;
    address.postalCode = value.postalCode || postalCodeFor(value);
    pickerOpen.value = false;
}

function persistAddress() {
    if (!address.barangay) {
        return;
    }

    saveAddress({
        label: 'home',
        region: address.region,
        province: address.province,
        city: address.city,
        barangay: address.barangay,
        street: address.street,
        postalCode: address.postalCode,
    });
}
</script>

<template>
    <Head title="Register" />

    <Form
        v-bind="store.form()"
        :reset-on-success="['password', 'password_confirmation']"
        v-slot="{ errors, processing }"
        class="flex flex-col gap-4"
        autocomplete="off"
        @success="persistAddress"
    >
        <p class="rounded-lg bg-red-600/10 px-3 py-2 text-xs text-black">
            This form is for authorized barangay health administrators only.
            Your name, contact, and address are encrypted and stay confidential
            to this account. The account stays pending until the MHO super
            admin approves it. After you create it, the next page is sign in.
        </p>

        <label class="flex items-start gap-2 text-sm text-black">
            <input
                v-model="isAuthorizedAdmin"
                type="checkbox"
                name="is_authorized_admin"
                value="1"
                class="mt-1 size-4"
                required
            />
            <span>
                I confirm I am an authorized MediCheck administrator at a
                barangay health center, not a visitor or random account.
            </span>
        </label>
        <InputError :message="errors.is_authorized_admin" />

        <div class="grid gap-3">
            <div class="grid gap-2">
                <Label for="name">Administrator name</Label>
                <Input
                    id="name"
                    v-model="fullName"
                    name="name"
                    required
                    autocomplete="name"
                    placeholder="Full name"
                />
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
                <Input
                    id="phone"
                    v-model="phone"
                    name="phone"
                    type="tel"
                    required
                    autocomplete="tel"
                    placeholder="09xxxxxxxxx"
                />
                <InputError :message="errors.phone" />
            </div>
        </div>

        <input type="hidden" name="region" :value="address.region" />
        <input type="hidden" name="province" :value="address.province" />
        <input type="hidden" name="city" :value="address.city" />
        <input type="hidden" name="barangay" :value="address.barangay" />
        <input type="hidden" name="postal_code" :value="address.postalCode" />
        <input type="hidden" name="street" :value="address.street" />

        <div
            class="overflow-hidden rounded-lg border border-neutral-200 bg-white"
        >
            <p class="px-3 pt-3 text-sm font-semibold text-black">Address</p>
            <button
                type="button"
                class="flex h-11 w-full items-center justify-between border-b border-neutral-100 px-3 text-left text-sm"
                @click="pickerOpen = true"
            >
                <span
                    class="min-w-0 truncate"
                    :class="locationText ? 'text-black' : 'text-neutral-400'"
                >
                    {{ locationText || 'Region, Province, City, Barangay' }}
                </span>
                <ChevronRight class="size-4 text-neutral-400" />
            </button>
            <input
                v-model="address.postalCode"
                readonly
                class="h-11 w-full border-b border-neutral-100 bg-neutral-50 px-3 text-sm text-black outline-none"
                placeholder="Postal Code"
            />
            <input
                v-model="address.street"
                required
                class="h-11 w-full px-3 text-sm text-black outline-none"
                placeholder="Street Name, Building, House No."
            />
        </div>
        <InputError :message="errors.barangay" />

        <div class="grid gap-3">
            <div class="grid gap-2">
                <Label for="email">Email address</Label>
                <Input
                    id="email"
                    type="email"
                    name="email"
                    required
                    autocomplete="email"
                    placeholder="email@example.com"
                />
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
        </div>

        <Button
            type="submit"
            class="mt-1 w-full"
            :class="
                canSubmit
                    ? 'bg-red-600 text-white hover:bg-red-600'
                    : 'bg-neutral-300 text-white'
            "
            :disabled="processing || !canSubmit"
            data-test="register-user-button"
        >
            <Spinner v-if="processing" />
            Create account
        </Button>

        <div class="text-center text-sm text-black">
            Already have an account?
            <TextLink :href="login()" class="underline underline-offset-4"
                >Log in</TextLink
            >
        </div>
    </Form>

    <ShopeeLocationPicker
        :open="pickerOpen"
        @close="pickerOpen = false"
        @select="applyLocation"
    />
</template>
