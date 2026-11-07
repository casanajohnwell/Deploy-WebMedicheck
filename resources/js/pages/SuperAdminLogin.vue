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

defineOptions({
    layout: {
        title: 'Super admin sign in',
        description: 'Open the MediCheck overview for every health center',
    },
});

defineProps<{
    status?: string;
}>();
</script>

<template>
    <Head title="Super admin" />

    <div v-if="status" class="mb-4 text-center text-sm font-medium text-black">
        {{ status }}
    </div>

    <Form
        action="/superadmin"
        method="post"
        :reset-on-success="['password']"
        v-slot="{ errors, processing }"
        class="flex flex-col gap-6"
        novalidate
    >
        <div
            v-if="errors.email || errors.password"
            class="rounded-lg bg-red-100 p-3 text-sm font-medium text-red-700"
            role="alert"
        >
            {{ errors.email || errors.password }}
        </div>

        <div class="grid gap-2">
            <Label for="email">Super admin email</Label>
            <Input
                id="email"
                type="email"
                name="email"
                required
                autofocus
                autocomplete="username"
                placeholder="superadmin@gmail.com"
            />
            <InputError :message="errors.email" />
        </div>

        <div class="grid gap-2">
            <div class="flex items-center justify-between">
                <Label for="password">Password</Label>
                <TextLink href="/forgot-password?for=superadmin" class="text-sm">
                    Forgot password?
                </TextLink>
            </div>
            <PasswordInput
                id="password"
                name="password"
                required
                autocomplete="current-password"
                placeholder="Password"
            />
            <InputError :message="errors.password" />
        </div>

        <Label for="remember" class="flex items-center space-x-3">
            <Checkbox id="remember" name="remember" />
            <span>Remember this device</span>
        </Label>

        <Button
            type="submit"
            class="w-full bg-red-600/80 text-white hover:bg-red-600"
            :disabled="processing"
        >
            <Spinner v-if="processing" />
            Log in
        </Button>
    </Form>
</template>
