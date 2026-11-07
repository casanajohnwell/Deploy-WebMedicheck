<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import { UserX } from '@lucide/vue';
import { ref } from 'vue';

const props = withDefaults(
    defineProps<{
        userId: number;
        name: string;
        label?: string;
    }>(),
    { label: 'Reject' },
);

const open = ref(false);

function confirmReject(event: Event) {
    if (!window.confirm(`Reject ${props.name}? The account will be removed and listed under Rejected accounts.`)) {
        event.preventDefault();
    }
}
</script>

<template>
    <div class="grid gap-2">
        <button
            v-if="!open"
            type="button"
            class="flex w-fit items-center gap-1.5 rounded-md border border-red-200 bg-white px-3 py-2 text-sm text-red-700 hover:bg-red-50"
            @click="open = true"
        >
            <UserX class="size-4" />
            {{ label }}
        </button>

        <Form
            v-else
            :action="`/superadmin/accounts/${userId}/decline`"
            method="post"
            class="grid w-full gap-2 rounded-lg border border-red-200 bg-red-50/60 p-3"
            v-slot="{ processing, errors }"
        >
            <label class="text-xs font-medium text-red-800" :for="`reason-${userId}`">
                Reason for rejecting (optional)
            </label>
            <textarea
                :id="`reason-${userId}`"
                name="reason"
                rows="2"
                maxlength="500"
                placeholder="Example: Not a registered nurse of this barangay."
                class="w-full rounded-md border border-red-200 bg-white px-3 py-2 text-sm outline-none focus:border-red-400"
            />
            <p v-if="errors.reason" class="text-xs text-red-700">{{ errors.reason }}</p>
            <div class="flex gap-2">
                <button
                    type="submit"
                    :disabled="processing"
                    @click="confirmReject"
                    class="rounded-md bg-red-600 px-3 py-2 text-sm text-white hover:bg-red-700 disabled:opacity-60"
                >
                    Confirm reject
                </button>
                <button
                    type="button"
                    class="rounded-md border border-red-200 bg-white px-3 py-2 text-sm"
                    @click="open = false"
                >
                    Cancel
                </button>
            </div>
        </Form>
    </div>
</template>
