<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { KeyRound, Mail, UserCog } from '@lucide/vue';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';

defineProps<{
    name: string;
    email: string;
    pendingEmail: string | null;
    codeMinutes: number;
}>();
</script>

<template>
    <Head title="My account" />

    <div class="flex flex-col gap-1">
        <h1 class="flex items-center gap-2 text-2xl font-semibold">
            <UserCog class="size-6 text-red-700" />
            My account
        </h1>
        <p class="max-w-2xl text-sm text-neutral-600">
            Use a real Gmail address you can open. Password reset codes for
            the super admin are sent to this email.
        </p>
    </div>

    <section class="grid gap-6 lg:grid-cols-2">
        <div class="flex flex-col gap-4 rounded-xl border border-red-100 bg-white p-4 shadow-sm sm:p-6">
            <div>
                <h2 class="flex items-center gap-2 font-semibold">
                    <Mail class="size-4 text-red-700" />
                    Email address
                </h2>
                <p class="text-sm text-neutral-600">
                    {{ name }} · <span class="break-all">{{ email }}</span>
                </p>
            </div>

            <template v-if="pendingEmail">
                <p class="rounded-lg bg-red-50 px-3 py-2 text-sm text-red-800">
                    Enter the 6-digit code we sent to
                    <span class="font-medium break-all">{{ pendingEmail }}</span>.
                    The code expires in {{ codeMinutes }} minutes.
                </p>
                <Form
                    action="/superadmin/profile/email/verify"
                    method="post"
                    :reset-on-error="['code']"
                    v-slot="{ errors, processing }"
                    class="grid gap-4"
                >
                    <div class="grid gap-2">
                        <Label for="code">Code</Label>
                        <Input
                            id="code"
                            name="code"
                            inputmode="numeric"
                            autocomplete="one-time-code"
                            maxlength="6"
                            required
                            autofocus
                            placeholder="123456"
                            class="tracking-[0.4em]"
                        />
                        <InputError :message="errors.code" />
                    </div>
                    <Button type="submit" :disabled="processing" class="bg-red-600 hover:bg-red-700">
                        <Spinner v-if="processing" />
                        Confirm new email
                    </Button>
                </Form>
                <Form action="/superadmin/profile/email/cancel" method="post">
                    <button type="submit" class="text-sm font-medium text-red-700">
                        Use a different email
                    </button>
                </Form>
            </template>

            <Form
                v-else
                action="/superadmin/profile/email"
                method="post"
                :reset-on-success="['current_password']"
                v-slot="{ errors, processing }"
                class="grid gap-4"
            >
                <div class="grid gap-2">
                    <Label for="email">New Gmail address</Label>
                    <Input
                        id="email"
                        name="email"
                        type="email"
                        required
                        autocomplete="email"
                        placeholder="yourname@gmail.com"
                    />
                    <InputError :message="errors.email" />
                </div>
                <div class="grid gap-2">
                    <Label for="email_current_password">Current password</Label>
                    <PasswordInput
                        id="email_current_password"
                        name="current_password"
                        required
                        autocomplete="current-password"
                        placeholder="Current password"
                    />
                    <InputError :message="errors.current_password" />
                </div>
                <Button type="submit" :disabled="processing" class="bg-red-600 hover:bg-red-700">
                    <Spinner v-if="processing" />
                    Send code
                </Button>
            </Form>
        </div>

        <Form
            action="/superadmin/profile/password"
            method="put"
            error-bag="updatePassword"
            :reset-on-success="true"
            :reset-on-error="['password', 'password_confirmation', 'current_password']"
            v-slot="{ errors, processing }"
            class="flex flex-col gap-4 rounded-xl border border-red-100 bg-white p-4 shadow-sm sm:p-6"
        >
            <h2 class="flex items-center gap-2 font-semibold">
                <KeyRound class="size-4 text-red-700" />
                Change password
            </h2>
            <div class="grid gap-2">
                <Label for="current_password">Current password</Label>
                <PasswordInput
                    id="current_password"
                    name="current_password"
                    required
                    autocomplete="current-password"
                    placeholder="Current password"
                />
                <InputError :message="errors.current_password" />
            </div>
            <div class="grid gap-2">
                <Label for="password">New password</Label>
                <PasswordInput
                    id="password"
                    name="password"
                    required
                    autocomplete="new-password"
                    placeholder="New password"
                />
                <InputError :message="errors.password" />
            </div>
            <div class="grid gap-2">
                <Label for="password_confirmation">Confirm new password</Label>
                <PasswordInput
                    id="password_confirmation"
                    name="password_confirmation"
                    required
                    autocomplete="new-password"
                    placeholder="Confirm new password"
                />
                <InputError :message="errors.password_confirmation" />
            </div>
            <Button type="submit" :disabled="processing" class="bg-red-600 hover:bg-red-700">
                <Spinner v-if="processing" />
                Save password
            </Button>
        </Form>
    </section>
</template>
