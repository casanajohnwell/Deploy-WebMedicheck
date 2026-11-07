<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { computed, onMounted, ref, watch } from 'vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import type { Medicine } from '@/lib/medicheck-demo';

const page = usePage();
const open = ref(false);

const alerts = computed(() =>
    ((page.props.medicines as Medicine[] | undefined) ?? []).filter(
        (item) => item.status === 'low' || item.status === 'out_of_stock',
    ),
);

function maybeOpen() {
    if (!alerts.value.length || typeof window === 'undefined') {
        return;
    }

    const key = alerts.value.map((item) => `${item.id}:${item.status}`).join('|');

    if (window.sessionStorage.getItem('medicheck.stockAlert') === key) {
        return;
    }

    window.sessionStorage.setItem('medicheck.stockAlert', key);
    open.value = true;
}

onMounted(maybeOpen);
watch(alerts, () => maybeOpen());
</script>

<template>
    <Dialog v-model:open="open">
        <DialogContent class="text-black">
            <DialogHeader>
                <DialogTitle>Low stock alert</DialogTitle>
                <DialogDescription class="text-black">
                    These medicines are low or out of stock at your health center.
                </DialogDescription>
            </DialogHeader>
            <div class="max-h-64 space-y-2 overflow-y-auto">
                <div
                    v-for="item in alerts"
                    :key="item.id"
                    class="flex items-center justify-between gap-3 rounded-lg border border-red-200 px-3 py-2 text-sm"
                >
                    <span class="font-medium">{{ item.name }}</span>
                    <span>
                        {{ item.status === 'out_of_stock' ? 'Out of stock' : 'Low stock' }}
                        · {{ item.quantity }}
                    </span>
                </div>
            </div>
            <DialogFooter>
                <Button class="bg-red-600 text-white hover:bg-red-600" @click="open = false">
                    OK
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
