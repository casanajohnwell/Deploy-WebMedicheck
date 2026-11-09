<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { Package, Pencil, Plus, Search, Trash2 } from '@lucide/vue';
import AdminPageHeader from '@/components/AdminPageHeader.vue';
import { toast } from 'vue-sonner';
import { computed, reactive, ref } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { useActiveAddress } from '@/composables/useActiveAddress';
import { useSavedMedicines } from '@/composables/useSavedMedicines';
import {
    expiresInLabel,
    isExpiringSoon,
    medicineStatusLabel,
    medicineStatusOptions,
    type Medicine,
    type MedicineStatus,
} from '@/lib/medicheck-demo';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: '/admin/dashboard' },
            { title: 'Inventory', href: '/admin/inventory' },
        ],
    },
});

const medicineCategories = [
    'Vitamins',
    'Antibiotic',
    'Maintenance',
    'Pain & Fever',
    'Cough & Colds',
    'First Aid',
];

const { healthCenterFilter } = useActiveAddress();
const { medicines } = useSavedMedicines();
const query = ref('');
const categoryFilter = ref('');
const dialogOpen = ref(false);
const editingId = ref<number | null>(null);
const saving = ref(false);

const form = reactive({
    name: '',
    category: medicineCategories[0],
    healthCenter: healthCenterFilter.value ?? '',
    quantity: 0,
    unit: 'tablets',
    expiration: '',
    status: 'available' as Exclude<MedicineStatus, 'expired'>,
});

const filtered = computed(() =>
    medicines.value.filter((item) => {
        const matchesQuery =
            `${item.name} ${item.category} ${item.healthCenter}`
                .toLowerCase()
                .includes(query.value.toLowerCase());
        const matchesPlace = item.healthCenter === healthCenterFilter.value;
        const matchesCategory =
            !categoryFilter.value || item.category === categoryFilter.value;

        return matchesQuery && matchesPlace && matchesCategory;
    }),
);

const categoryChips = computed(() =>
    Array.from(
        new Set([
            'All',
            ...medicineCategories,
            ...medicines.value.map((item) => item.category).filter(Boolean),
        ]),
    ),
);

function openCreate() {
    editingId.value = null;
    form.name = '';
    form.category = medicineCategories[0];
    form.healthCenter = healthCenterFilter.value ?? '';
    form.quantity = 0;
    form.unit = 'tablets';
    form.expiration = '';
    form.status = 'available';
    dialogOpen.value = true;
}

function openEdit(item: Medicine) {
    editingId.value = item.id;
    form.name = item.name;
    form.category = item.category;
    form.healthCenter = item.healthCenter;
    form.quantity = item.quantity;
    form.unit = item.unit;
    form.expiration = item.expiration;
    form.status =
        item.status === 'out_of_stock' || item.status === 'low'
            ? item.status
            : 'available';
    dialogOpen.value = true;
}

function saveItem() {
    const payload = {
        name: form.name,
        category: form.category,
        healthCenter: form.healthCenter,
        quantity: Number(form.quantity),
        unit: form.unit,
        expiration: form.expiration,
        status: form.status,
    };

    saving.value = true;

    const options = {
        preserveScroll: true,
        onSuccess: () => {
            toast.success(
                editingId.value ? 'Medicine updated' : 'Medicine added',
            );
            dialogOpen.value = false;
        },
        onError: () => {
            toast.error(
                'Medicine was not saved. Check the form and try again.',
            );
        },
        onFinish: () => {
            saving.value = false;
        },
    };

    if (editingId.value) {
        router.put(`/admin/inventory/${editingId.value}`, payload, options);
        return;
    }

    router.post('/admin/inventory', payload, options);
}

function countFor(status: Exclude<MedicineStatus, 'expired'>) {
    return medicines.value.filter((item) => displayStatus(item) === status).length;
}

function removeItem(id: number) {
    const item = medicines.value.find((medicine) => medicine.id === id);

    if (!window.confirm(`Delete ${item?.name ?? 'this medicine'}? This cannot be undone.`)) {
        return;
    }

    router.delete(`/admin/inventory/${id}`, {
        preserveScroll: true,
        onSuccess: () => toast.success('Medicine removed'),
        onError: () => toast.error('Medicine was not removed.'),
    });
}

function setStatus(item: Medicine, status: Exclude<MedicineStatus, 'expired'>) {
    router.put(
        `/admin/inventory/${item.id}`,
        {
            name: item.name,
            category: item.category,
            healthCenter: item.healthCenter,
            quantity: item.quantity,
            unit: item.unit,
            expiration: item.expiration,
            status,
        },
        {
            preserveScroll: true,
            onSuccess: () => toast.success('Status updated'),
            onError: () => toast.error('Status was not updated.'),
        },
    );
}

const statusClass: Record<MedicineStatus, string> = {
    available: 'border-transparent bg-emerald-100 text-emerald-800',
    low: 'border-transparent bg-amber-100 text-amber-800',
    out_of_stock: 'border-transparent bg-red-100 text-red-700',
    expired: 'border-transparent bg-red-100 text-red-700',
};

function displayStatus(item: Medicine): Exclude<MedicineStatus, 'expired'> {
    if (item.status !== 'expired') {
        return item.status;
    }

    return item.stockStatus ?? 'out_of_stock';
}
</script>

<template>
    <Head title="Inventory" />

    <div class="flex min-w-0 flex-1 flex-col gap-6 p-3 sm:p-4 md:p-6">
        <AdminPageHeader
            title="Inventory"
            description="Monitor stock, quantities, and expiration dates."
            :icon="Package"
            :stats="[
                { label: 'medicines', value: medicines.length, tone: 'neutral' },
                { label: 'available', value: countFor('available'), tone: 'green' },
                { label: 'low stock', value: countFor('low'), tone: 'amber' },
                { label: 'out of stock', value: countFor('out_of_stock'), tone: 'red' },
            ]"
        >
            <template #actions>
                <Button
                    class="w-full bg-red-600 text-white hover:bg-red-700 sm:w-auto"
                    @click="openCreate"
                >
                    <Plus class="size-4" />
                    Add medicine
                </Button>
            </template>
        </AdminPageHeader>

        <Card class="border-red-100">
            <CardHeader class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <CardTitle>Medicine stock</CardTitle>
                <div class="relative w-full sm:max-w-sm">
                    <Search class="text-muted-foreground absolute top-1/2 left-3 size-4 -translate-y-1/2" />
                    <Input
                        v-model="query"
                        placeholder="Search medicine or category"
                        class="w-full pl-9"
                    />
                </div>
            </CardHeader>
            <CardContent class="space-y-4">
                <div class="flex flex-wrap gap-2" role="group" aria-label="Filter by category">
                    <button
                        v-for="category in categoryChips"
                        :key="category"
                        type="button"
                        class="rounded-full border px-3.5 py-1.5 text-xs font-bold transition-colors"
                        :class="
                            (categoryFilter || 'All') === category
                                ? 'border-red-600 bg-red-600 text-white'
                                : 'border-red-200 bg-white text-black hover:bg-red-50'
                        "
                        :aria-pressed="(categoryFilter || 'All') === category"
                        @click="categoryFilter = category === 'All' ? '' : category"
                    >
                        {{ category }}
                    </button>
                </div>
                <div
                    v-if="!filtered.length"
                    class="flex flex-col items-center gap-2 rounded-lg border border-dashed border-red-200 p-10 text-center"
                >
                    <Package class="size-10 text-red-300" />
                    <p class="font-medium text-black">
                        {{ medicines.length ? 'No medicine matches your search.' : 'No medicine yet.' }}
                    </p>
                    <Button
                        v-if="!medicines.length"
                        size="sm"
                        class="bg-red-600 text-white hover:bg-red-700"
                        @click="openCreate"
                    >
                        <Plus class="size-4" />
                        Add the first medicine
                    </Button>
                </div>

                <div class="space-y-3 md:hidden">
                    <div
                        v-for="item in filtered"
                        :key="item.id"
                        class="space-y-3 rounded-lg border border-red-200/60 bg-white p-3 text-black"
                    >
                        <div class="flex items-center gap-3">
                            <img
                                :src="item.image ?? '/images/medicines/default.svg'"
                                :alt="item.name"
                                class="size-12 shrink-0 rounded-lg"
                                loading="lazy"
                            />
                            <div>
                                <p class="font-medium">{{ item.name }}</p>
                                <p class="text-muted-foreground text-xs">
                                    {{ item.category }}
                                </p>
                            </div>
                        </div>
                        <p class="text-sm break-words">
                            {{ item.healthCenter }}
                        </p>
                        <p class="text-sm">
                            {{ item.quantity }} {{ item.unit }} ·
                            {{ item.expiration }}
                        </p>
                        <p v-if="isExpiringSoon(item)" class="text-xs text-amber-800">
                            {{ expiresInLabel(item.expiresInDays ?? 0) }}
                        </p>
                        <div class="flex flex-col gap-2">
                            <Badge :class="statusClass[item.status]" class="w-fit">
                                {{ medicineStatusLabel(item.status) }}
                            </Badge>
                            <select
                                class="h-9 w-full rounded-md border px-2 text-sm"
                                :class="statusClass[displayStatus(item)]"
                                :value="displayStatus(item)"
                                @change="
                                    setStatus(
                                        item,
                                        ($event.target as HTMLSelectElement)
                                            .value as Exclude<
                                            MedicineStatus,
                                            'expired'
                                        >,
                                    )
                                "
                            >
                                <option
                                    v-for="option in medicineStatusOptions"
                                    :key="option.value"
                                    :value="option.value"
                                >
                                    {{ option.label }}
                                </option>
                            </select>
                        </div>
                        <div class="flex gap-2">
                            <Button
                                class="flex-1"
                                variant="outline"
                                size="sm"
                                @click="openEdit(item)"
                            >
                                <Pencil class="size-3.5" />
                                Edit
                            </Button>
                            <Button
                                class="flex-1"
                                variant="destructive"
                                size="sm"
                                @click="removeItem(item.id)"
                            >
                                <Trash2 class="size-3.5" />
                                Delete
                            </Button>
                        </div>
                    </div>
                </div>

                <div
                    v-if="filtered.length"
                    class="hidden overflow-x-auto rounded-lg border border-red-100 bg-white md:block"
                >
                    <table class="w-full min-w-[860px] text-left text-sm">
                        <thead class="bg-red-50 text-xs tracking-wide text-neutral-600 uppercase">
                            <tr>
                                <th class="px-4 py-3 font-medium">Medicine</th>
                                <th class="px-4 py-3 font-medium">Center</th>
                                <th class="px-4 py-3 font-medium">Qty</th>
                                <th class="px-4 py-3 font-medium">
                                    Expiration
                                </th>
                                <th class="px-4 py-3 font-medium">Status</th>
                                <th class="px-4 py-3 font-medium">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="item in filtered"
                                :key="item.id"
                                class="border-t border-red-50 hover:bg-red-50/40"
                            >
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-3">
                                        <img
                                            :src="item.image ?? '/images/medicines/default.svg'"
                                            :alt="item.name"
                                            class="size-10 shrink-0 rounded-lg"
                                            loading="lazy"
                                        />
                                        <div>
                                            <p class="font-medium">{{ item.name }}</p>
                                            <p class="text-muted-foreground text-xs">
                                                {{ item.category }}
                                            </p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    {{ item.healthCenter }}
                                </td>
                                <td class="px-4 py-3">
                                    {{ item.quantity }} {{ item.unit }}
                                </td>
                                <td class="px-4 py-3">
                                    {{ item.expiration }}
                                    <p
                                        v-if="isExpiringSoon(item)"
                                        class="text-xs text-amber-800"
                                    >
                                        {{ expiresInLabel(item.expiresInDays ?? 0) }}
                                    </p>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-2">
                                        <Badge :class="statusClass[item.status]">
                                            {{ medicineStatusLabel(item.status) }}
                                        </Badge>
                                        <select
                                            class="h-9 rounded-md border px-2 text-sm"
                                            :class="
                                                statusClass[
                                                    displayStatus(item)
                                                ]
                                            "
                                            :value="displayStatus(item)"
                                            @change="
                                                setStatus(
                                                    item,
                                                    (
                                                        $event.target as HTMLSelectElement
                                                    ).value as Exclude<
                                                        MedicineStatus,
                                                        'expired'
                                                    >,
                                                )
                                            "
                                        >
                                            <option
                                                v-for="option in medicineStatusOptions"
                                                :key="option.value"
                                                :value="option.value"
                                            >
                                                {{ option.label }}
                                            </option>
                                        </select>
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex gap-2">
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            @click="openEdit(item)"
                                        >
                                            <Pencil class="size-3.5" />
                                            Edit
                                        </Button>
                                        <Button
                                            variant="destructive"
                                            size="sm"
                                            @click="removeItem(item.id)"
                                        >
                                            <Trash2 class="size-3.5" />
                                            Delete
                                        </Button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </CardContent>
        </Card>

        <Dialog v-model:open="dialogOpen">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>
                        {{ editingId ? 'Edit medicine' : 'Add medicine' }}
                    </DialogTitle>
                    <DialogDescription>
                        Saved medicines stay after refresh and show on the
                        dashboard.
                    </DialogDescription>
                </DialogHeader>
                <form class="grid gap-3" @submit.prevent="saveItem">
                    <div class="grid gap-2">
                        <Label for="name">Medicine name</Label>
                        <Input id="name" v-model="form.name" required />
                    </div>
                    <div class="grid gap-2">
                        <Label for="category">Category</Label>
                        <select
                            id="category"
                            v-model="form.category"
                            required
                            class="border-input h-9 rounded-md border bg-transparent px-3 text-sm"
                        >
                            <option
                                v-for="category in medicineCategories"
                                :key="category"
                                :value="category"
                            >
                                {{ category }}
                            </option>
                            <option
                                v-if="
                                    form.category &&
                                    !medicineCategories.includes(form.category)
                                "
                                :value="form.category"
                            >
                                {{ form.category }}
                            </option>
                        </select>
                    </div>
                    <div class="grid gap-2">
                        <Label for="center">Health center</Label>
                        <Input
                            id="center"
                            :model-value="
                                healthCenterFilter || form.healthCenter
                            "
                            readonly
                        />
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div class="grid gap-2">
                            <Label for="qty">Quantity</Label>
                            <Input
                                id="qty"
                                v-model="form.quantity"
                                type="number"
                                min="0"
                                required
                            />
                        </div>
                        <div class="grid gap-2">
                            <Label for="unit">Unit</Label>
                            <Input id="unit" v-model="form.unit" required />
                        </div>
                    </div>
                    <div class="grid gap-2">
                        <Label for="expiration">Expiration date</Label>
                        <Input
                            id="expiration"
                            v-model="form.expiration"
                            type="date"
                            required
                        />
                    </div>
                    <div class="grid gap-2">
                        <Label for="status">Stock status</Label>
                        <select
                            id="status"
                            v-model="form.status"
                            required
                            class="h-9 rounded-md border px-3 text-sm"
                            :class="statusClass[form.status]"
                        >
                            <option
                                v-for="option in medicineStatusOptions"
                                :key="option.value"
                                :value="option.value"
                            >
                                {{ option.label }}
                            </option>
                        </select>
                    </div>
                    <DialogFooter class="gap-2 sm:justify-end">
                        <Button
                            type="submit"
                            class="w-full bg-red-600/80 text-white hover:bg-red-600 sm:w-auto"
                            :disabled="saving"
                        >
                            Save
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    </div>
</template>
