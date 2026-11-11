<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { Bot, MessageSquare, Send, Smartphone, Trash2 } from '@lucide/vue';
import AdminPageHeader from '@/components/AdminPageHeader.vue';
import { onMounted, reactive, ref } from 'vue';
import { toast } from 'vue-sonner';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';

type SmsLog = {
    id: number;
    bookingId: number | null;
    recipient: string;
    to: string;
    message: string;
    mode: 'manual' | 'automatic';
    type: string;
    status: string;
    sentAt: string;
};

type SmsPrefill = {
    bookingId: number;
    status: string;
    recipient: string;
    to: string;
    type: string;
    message: string;
    alreadySent: boolean;
};

type SmsPhone = {
    configured: boolean;
    deviceId: string | null;
    hasDefault: boolean;
};

const props = defineProps<{
    messages: SmsLog[];
    prefill?: SmsPrefill | null;
    smsPhone: SmsPhone;
}>();

const deviceForm = useForm({ device_id: props.smsPhone.deviceId ?? '' });

function saveDevice() {
    deviceForm.put('/admin/sms/device', { preserveScroll: true });
}

const deletingId = ref<number | null>(null);

function deleteLog(log: SmsLog) {
    if (!window.confirm(`Remove the SMS to ${log.recipient} from the log? The patient keeps it in their app inbox.`)) {
        return;
    }

    deletingId.value = log.id;
    router.delete(`/admin/sms/${log.id}`, {
        preserveScroll: true,
        onFinish: () => {
            deletingId.value = null;
        },
    });
}

const messageInput = ref<HTMLTextAreaElement | null>(null);

onMounted(() => {
    if (props.prefill) {
        messageInput.value?.focus();
        messageInput.value?.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
});

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: '/admin/dashboard' },
            { title: 'SMS notifications', href: '/admin/sms' },
        ],
    },
});

const saving = ref(false);
const form = reactive({
    recipient: props.prefill?.recipient ?? '',
    to: props.prefill?.to ?? '',
    message: props.prefill?.message ?? '',
    type: props.prefill?.type ?? 'approval',
    booking_id: props.prefill?.bookingId ?? null,
});

function sendSms() {
    saving.value = true;
    router.post('/admin/sms', { ...form }, {
        preserveScroll: true,
        onFinish: () => {
            saving.value = false;
        },
        onError: (errors) => {
            toast.error(Object.values(errors)[0] ?? 'The SMS could not be sent.');
        },
        onSuccess: () => {
            form.recipient = '';
            form.to = '';
            form.message = '';
            form.type = 'approval';
            form.booking_id = null;
        },
    });
}
</script>

<template>
    <Head title="SMS Notifications" />

    <div class="flex min-w-0 flex-1 flex-col gap-6 p-3 sm:p-4 md:p-6">
        <AdminPageHeader
            title="SMS notifications"
            description="All SMS are sent manually by the nurse. After a booking update, the message is prepared here for you to review and send."
            :icon="MessageSquare"
            :stats="[
                { label: 'messages', value: messages.length, tone: 'neutral' },
                { label: 'automatic', value: messages.filter((log) => log.mode === 'automatic').length, tone: 'blue' },
                { label: 'manual', value: messages.filter((log) => log.mode === 'manual').length, tone: 'red' },
            ]"
        />

        <div
            v-if="prefill"
            class="flex flex-wrap items-center justify-between gap-2 rounded-xl border px-4 py-3 text-sm"
            :class="prefill.alreadySent ? 'border-amber-200 bg-amber-50 text-amber-900' : 'border-emerald-200 bg-emerald-50 text-emerald-900'"
            role="status"
        >
            <p v-if="prefill.alreadySent">
                An SMS for this update was already sent for request #{{ prefill.bookingId }}. Send again only if needed.
            </p>
            <p v-else-if="!prefill.to">
                Request #{{ prefill.bookingId }} is now {{ prefill.status }}. The patient has no mobile number on file, so type one before sending.
            </p>
            <p v-else>
                Request #{{ prefill.bookingId }} is now {{ prefill.status }}. The message is ready below. Review it, then click Send SMS.
            </p>
            <Link
                v-if="prefill.status === 'claimed' || prefill.status === 'unclaimed'"
                href="/admin/bookings/history"
                class="font-medium underline"
            >
                Back to booking history
            </Link>
            <Link v-else href="/admin/bookings/approval" class="font-medium underline">Back to booking approval</Link>
        </div>

        <div class="grid min-w-0 items-start gap-4 lg:grid-cols-5">
            <div class="grid min-w-0 gap-4 lg:sticky lg:top-4 lg:col-span-2 lg:max-h-[calc(100svh-2rem)] lg:overflow-y-auto">
            <Card
                class="h-fit border-red-100"
                :class="form.booking_id ? 'ring-2 ring-emerald-400' : ''"
            >
                <CardHeader>
                    <CardTitle>Manual SMS</CardTitle>
                    <CardDescription>Texted to the patient through TextBee and shown in the mobile inbox.</CardDescription>
                </CardHeader>
                <CardContent>
                    <form class="grid gap-3" @submit.prevent="sendSms">
                        <div class="grid gap-2">
                            <Label for="recipient">Seeker name</Label>
                            <Input id="recipient" v-model="form.recipient" required />
                        </div>
                        <div class="grid gap-2">
                            <Label for="to">Mobile number</Label>
                            <Input id="to" v-model="form.to" placeholder="09xx xxx xxxx" required />
                        </div>
                        <div class="grid gap-2">
                            <Label for="type">Type</Label>
                            <select
                                id="type"
                                v-model="form.type"
                                class="border-input h-9 rounded-md border bg-transparent px-3 text-sm"
                            >
                                <option value="approval">Approved</option>
                                <option value="decline">Declined</option>
                                <option value="claimed">Claimed</option>
                                <option value="unclaimed">Unclaimed</option>
                            </select>
                        </div>
                        <div class="grid gap-2">
                            <Label for="message">Message</Label>
                            <textarea
                                id="message"
                                ref="messageInput"
                                v-model="form.message"
                                required
                                rows="4"
                                class="border-input rounded-md border bg-transparent px-3 py-2 text-sm"
                                placeholder="MediCheck: Your request was approved..."
                            />
                        </div>
                        <Button
                            type="submit"
                            class="w-full bg-red-600/80 text-white hover:bg-red-600"
                            :disabled="saving"
                        >
                            <Send class="size-4" />
                            Send SMS
                        </Button>
                    </form>
                </CardContent>
            </Card>
            <Card class="border-red-100">
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <Smartphone class="size-4 text-red-600" />
                        SMS phone
                    </CardTitle>
                    <CardDescription>
                        The Android phone of this health center that sends the texts through TextBee.
                    </CardDescription>
                </CardHeader>
                <CardContent class="grid gap-3">
                    <p
                        v-if="!smsPhone.configured"
                        class="rounded-lg bg-amber-50 px-3 py-2 text-sm text-amber-900"
                    >
                        TextBee is not set up yet. SMS are saved to the mobile inbox only.
                    </p>
                    <p
                        v-else-if="smsPhone.deviceId"
                        class="rounded-lg bg-emerald-50 px-3 py-2 text-sm text-emerald-900"
                    >
                        Texts are sent from this health center's own phone.
                    </p>
                    <p
                        v-else
                        class="rounded-lg bg-amber-50 px-3 py-2 text-sm text-amber-900"
                    >
                        No phone set for this health center yet. Texts are sent from the MHO default phone.
                    </p>
                    <form class="grid gap-2" @submit.prevent="saveDevice">
                        <Label for="device_id">TextBee Device ID</Label>
                        <div class="flex gap-2">
                            <Input
                                id="device_id"
                                v-model="deviceForm.device_id"
                                placeholder="24-character code from TextBee"
                                class="min-w-0 font-mono text-xs"
                            />
                            <Button type="submit" variant="outline" :disabled="deviceForm.processing">
                                Save
                            </Button>
                        </div>
                        <p v-if="deviceForm.errors.device_id" class="text-sm text-red-600">
                            {{ deviceForm.errors.device_id }}
                        </p>
                        <p class="text-muted-foreground text-xs">
                            Register your phone in the TextBee app, then copy the code shown under it in the TextBee dashboard.
                        </p>
                    </form>
                </CardContent>
            </Card>
            </div>

            <Card class="border-red-100 lg:col-span-3">
                <CardHeader>
                    <CardTitle>SMS log</CardTitle>
                    <CardDescription>Manual and automatic messages for this health center.</CardDescription>
                </CardHeader>
                <CardContent class="space-y-3">
                    <div
                        v-if="!messages.length"
                        class="flex flex-col items-center gap-2 rounded-lg border border-dashed border-red-200 p-10 text-center"
                    >
                        <MessageSquare class="size-10 text-red-300" />
                        <p class="text-sm text-neutral-600">No SMS yet.</p>
                    </div>
                    <div
                        v-for="log in messages"
                        :key="log.id"
                        class="flex gap-3 rounded-lg border border-red-100 bg-white p-3"
                    >
                        <span
                            class="flex size-9 shrink-0 items-center justify-center rounded-full"
                            :class="log.mode === 'automatic' ? 'bg-blue-100 text-blue-700' : 'bg-red-100 text-red-700'"
                        >
                            <component :is="log.mode === 'automatic' ? Bot : Send" class="size-4" />
                        </span>
                        <div class="min-w-0 flex-1 space-y-1">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <p class="min-w-0 font-medium break-words">{{ log.recipient }}</p>
                                <div class="flex items-center gap-1">
                                    <span class="text-muted-foreground text-xs">{{ log.sentAt }}</span>
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        size="icon"
                                        class="size-7 text-neutral-400 hover:bg-red-50 hover:text-red-600"
                                        :disabled="deletingId === log.id"
                                        :aria-label="`Delete SMS to ${log.recipient}`"
                                        @click="deleteLog(log)"
                                    >
                                        <Trash2 class="size-4" />
                                    </Button>
                                </div>
                            </div>
                            <div class="flex flex-wrap items-center gap-1.5">
                                <span class="text-muted-foreground text-xs">{{ log.to }}</span>
                                <Badge
                                    class="border-transparent capitalize"
                                    :class="log.mode === 'automatic' ? 'bg-blue-100 text-blue-800' : 'bg-red-100 text-red-800'"
                                >
                                    {{ log.mode }}
                                </Badge>
                                <Badge variant="outline" class="capitalize">
                                    {{ log.type.replace(/_/g, ' ') }}
                                </Badge>
                                <Badge
                                    class="border-transparent"
                                    :class="log.status === 'saved' ? 'bg-amber-100 text-amber-800' : 'bg-emerald-100 text-emerald-800'"
                                >
                                    {{ log.status === 'saved' ? 'Inbox only' : 'Texted' }}
                                </Badge>
                            </div>
                            <p class="rounded-lg bg-neutral-50 px-3 py-2 text-sm break-words">
                                {{ log.message }}
                            </p>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </div>
</template>
