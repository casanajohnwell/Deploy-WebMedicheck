<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { register } from '@/routes';
import { store } from '@/routes/login';
import { request } from '@/routes/password';

defineOptions({
    layout: {
        title: 'Admin sign in',
        description: 'Access the MediCheck health center console',
    },
});

defineProps<{
    status?: string;
    canResetPassword: boolean;
    approvalWarning?: string;
}>();
</script>

<template>
    <Head title="Admin Login" />

    <div
        v-if="approvalWarning"
        class="mb-4 rounded-lg bg-red-100 p-3 text-center text-sm font-medium text-red-700"
        role="alert"
    >
        {{ approvalWarning }}
    </div>

    <div v-if="status" class="mb-4 text-center text-sm font-medium text-black">
        {{ status }}
    </div>

    <Form
        v-bind="store.form()"
        :reset-on-success="['password']"
        v-slot="{ errors, processing }"
        class="flex flex-col gap-6"
        novalidate
    >
        <div
            v-if="errors.approved"
            class="mb-4 rounded-lg bg-red-50 p-3 text-sm font-medium text-black"
            role="status"
        >
            {{ errors.approved }}
        </div>

        <div
            v-if="errors.email || errors.password"
            class="rounded-lg bg-red-100 p-3 text-sm font-medium text-red-700"
            role="alert"
        >
            {{ errors.email || errors.password }}
        </div>

        <div class="grid gap-6">
            <div class="grid gap-2">
                <Label for="email">Admin email</Label>
                <Input
                    id="email"
                    type="email"
                    name="email"
                    required
                    autofocus
                    :tabindex="1"
                    autocomplete="email"
                    placeholder="admin@medicheck.local"
                    :aria-invalid="Boolean(errors.email)"
                    :class="errors.email ? 'border-red-500' : ''"
                />
                <InputError :message="errors.email" />
            </div>

            <div class="grid gap-2">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <Label for="password">Password</Label>
                    <TextLink
                        v-if="canResetPassword"
                        :href="request()"
                        class="text-sm"
                        :tabindex="5"
                    >
                        Forgot password?
                    </TextLink>
                </div>
                <PasswordInput
                    id="password"
                    name="password"
                    required
                    minlength="8"
                    :tabindex="2"
                    autocomplete="current-password"
                    placeholder="Password"
                    :aria-invalid="Boolean(errors.password)"
                    :class="errors.password ? 'border-red-500' : ''"
                />
                <InputError :message="errors.password" />
            </div>

            <Label for="remember" class="flex items-center space-x-3">
                <Checkbox id="remember" name="remember" :tabindex="3" />
                <span>Remember this device</span>
            </Label>

            <Button
                type="submit"
                class="mt-2 w-full bg-red-600/80 text-white hover:bg-red-600"
                :tabindex="4"
                :disabled="processing"
                data-test="login-button"
            >
                <Spinner v-if="processing" />
                Log in
            </Button>
        </div>

        <p class="text-center text-sm text-black">
            Don't have an account?
            <TextLink :href="register()" :tabindex="5">Create account</TextLink>
        </p>
    </Form>
</template>
