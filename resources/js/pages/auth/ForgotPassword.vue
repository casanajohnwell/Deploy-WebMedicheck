<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';

defineOptions({
    layout: {
        title: 'Forgot password',
        description: 'Get a 6-digit code in your Gmail',
    },
});

defineProps<{
    status?: string;
    step: 'email' | 'code' | 'password';
    email?: string | null;
    codeMinutes: number;
    passwordRules: string;
    forSuperAdmin: boolean;
    loginUrl: string;
}>();
</script>

<template>
    <Head title="Forgot password" />

    <div
        v-if="status"
        class="mb-4 rounded-lg bg-green-50 px-3 py-2 text-center text-sm font-medium text-green-700"
    >
        {{ status }}
    </div>

    <div v-if="step === 'email'" class="space-y-6">
        <p class="rounded-lg bg-red-600/10 px-3 py-2 text-sm text-black">
            Enter the Gmail address of your account. We will send a 6-digit code
            to your inbox.
        </p>

        <Form action="/forgot-password" method="post" v-slot="{ errors, processing }">
            <div class="grid gap-2">
                <Label for="email">Email address</Label>
                <Input
                    id="email"
                    type="email"
                    name="email"
                    autocomplete="email"
                    autofocus
                    placeholder="yourname@gmail.com"
                />
                <InputError :message="errors.email" />
            </div>

            <div class="my-6 flex items-center justify-start">
                <Button class="w-full" :disabled="processing">
                    <Spinner v-if="processing" />
                    Send code
                </Button>
            </div>
        </Form>
    </div>

    <div v-else-if="step === 'code'" class="space-y-6">
        <p class="rounded-lg bg-red-600/10 px-3 py-2 text-sm text-black">
            Enter the 6-digit code sent to <strong>{{ email }}</strong>. The code
            expires in {{ codeMinutes }} minutes.
        </p>

        <Form
            action="/forgot-password/verify"
            method="post"
            :reset-on-error="['code']"
            v-slot="{ errors, processing }"
        >
            <div class="grid gap-2">
                <Label for="code">Code</Label>
                <Input
                    id="code"
                    name="code"
                    inputmode="numeric"
                    autocomplete="one-time-code"
                    maxlength="6"
                    autofocus
                    placeholder="123456"
                    class="text-center text-lg tracking-[0.5em]"
                />
                <InputError :message="errors.code" />
            </div>

            <div class="my-6">
                <Button class="w-full" :disabled="processing">
                    <Spinner v-if="processing" />
                    Confirm code
                </Button>
            </div>
        </Form>

        <div class="flex items-center justify-between text-sm">
            <Form action="/forgot-password" method="post" v-slot="{ errors, processing }">
                <input type="hidden" name="email" :value="email" />
                <button
                    type="submit"
                    class="font-medium text-red-700 underline disabled:opacity-50"
                    :disabled="processing"
                >
                    Send a new code
                </button>
                <InputError :message="errors.email" />
            </Form>
            <Form action="/forgot-password/restart" method="post">
                <button type="submit" class="text-neutral-600 underline">
                    Use a different email
                </button>
            </Form>
        </div>
    </div>

    <div v-else class="space-y-6">
        <p class="rounded-lg bg-red-600/10 px-3 py-2 text-sm text-black">
            Create a new password for <strong>{{ email }}</strong>.
        </p>

        <Form
            action="/forgot-password/new-password"
            method="post"
            :reset-on-error="['password', 'password_confirmation']"
            v-slot="{ errors, processing }"
        >
            <div class="grid gap-6">
                <div class="grid gap-2">
                    <Label for="password">New password</Label>
                    <PasswordInput
                        id="password"
                        name="password"
                        autocomplete="new-password"
                        autofocus
                        placeholder="New password"
                        :passwordrules="passwordRules"
                    />
                    <InputError :message="errors.password" />
                </div>

                <div class="grid gap-2">
                    <Label for="password_confirmation">Confirm new password</Label>
                    <PasswordInput
                        id="password_confirmation"
                        name="password_confirmation"
                        autocomplete="new-password"
                        placeholder="Confirm new password"
                        :passwordrules="passwordRules"
                    />
                    <InputError :message="errors.password_confirmation" />
                </div>

                <Button class="w-full" :disabled="processing">
                    <Spinner v-if="processing" />
                    Save new password
                </Button>
            </div>
        </Form>
    </div>

    <div class="text-muted-foreground mt-6 space-x-1 text-center text-sm">
        <span>Or, return to</span>
        <TextLink :href="loginUrl">log in</TextLink>
    </div>
</template>
