<script setup lang="ts">
import { ArrowLeft, Search, X } from '@lucide/vue';
import { computed, onMounted, onBeforeUnmount, ref, watch } from 'vue';
import {
    childrenAt,
    flattenLocations,
    groupByLetter,
    postalCodeFor,
    type FlatLocation,
} from '@/lib/ph-locations';

const props = defineProps<{
    open: boolean;
}>();

const emit = defineEmits<{
    close: [];
    select: [value: FlatLocation];
}>();

type Step = 'region' | 'province' | 'city' | 'barangay';

const query = ref('');
const region = ref('');
const province = ref('');
const city = ref('');

const step = computed<Step>(() => {
    if (!region.value) {
        return 'region';
    }

    if (!province.value) {
        return 'province';
    }

    if (!city.value) {
        return 'city';
    }

    return 'barangay';
});

const stepTitle = computed(() => {
    if (step.value === 'region') {
        return 'Region';
    }

    if (step.value === 'province') {
        return 'Province';
    }

    if (step.value === 'city') {
        return 'City / Municipality';
    }

    return 'Barangay';
});

const path = computed(() => {
    if (step.value === 'region') {
        return [];
    }

    if (step.value === 'province') {
        return [region.value];
    }

    if (step.value === 'city') {
        return [region.value, province.value];
    }

    return [region.value, province.value, city.value];
});

const options = computed(() => childrenAt(path.value).map((node) => node.name));

const grouped = computed(() => {
    const needle = query.value.trim().toLowerCase();

    if (needle) {
        const hits = flattenLocations().filter((row) =>
            `${row.barangay} ${row.city} ${row.province} ${row.region} ${row.postalCode}`
                .toLowerCase()
                .includes(needle),
        );

        return [
            {
                letter: 'Results',
                items: hits.map(
                    (row) => `${row.barangay} · ${row.city}, ${row.province}`,
                ),
                rows: hits,
            },
        ];
    }

    return groupByLetter(options.value).map((group) => ({
        ...group,
        rows: null as FlatLocation[] | null,
    }));
});

watch(
    () => props.open,
    (open) => {
        if (open) {
            query.value = '';
        }
    },
);

function handleEscape(event: KeyboardEvent) {
    if (event.key === 'Escape' && props.open) {
        emit('close');
    }
}

onMounted(() => {
    window.addEventListener('keydown', handleEscape);
});

onBeforeUnmount(() => {
    window.removeEventListener('keydown', handleEscape);
});

function reset() {
    region.value = '';
    province.value = '';
    city.value = '';
    query.value = '';
}

function goBack() {
    if (query.value) {
        query.value = '';
        return;
    }

    if (step.value === 'barangay') {
        city.value = '';
        return;
    }

    if (step.value === 'city') {
        province.value = '';
        return;
    }

    if (step.value === 'province') {
        region.value = '';
        return;
    }

    emit('close');
}

function pickName(name: string) {
    if (step.value === 'region') {
        region.value = name;
        return;
    }

    if (step.value === 'province') {
        province.value = name;
        return;
    }

    if (step.value === 'city') {
        city.value = name;
        return;
    }

    emit('select', {
        region: region.value,
        province: province.value,
        city: city.value,
        barangay: name,
        postalCode: postalCodeFor({
            region: region.value,
            province: province.value,
            city: city.value,
        }),
    });
    reset();
}

function pickSearchRow(row: FlatLocation) {
    emit('select', row);
    reset();
}
</script>

<template>
    <div
        v-if="open"
        class="theme-light fixed inset-0 z-50 overflow-y-auto bg-[#f5f5f5] text-black"
        @click.self="emit('close')"
    >
        <div class="mx-auto flex min-h-full max-w-lg flex-col bg-white">
            <div
                class="sticky top-0 z-10 border-b border-neutral-200 bg-white px-3 py-3"
            >
                <div class="flex items-center gap-2">
                    <button
                        type="button"
                        class="flex size-9 items-center justify-center text-red-600"
                        @click="goBack"
                        aria-label="Go back"
                    >
                        <ArrowLeft class="size-5" />
                    </button>
                    <div class="relative flex-1">
                        <Search
                            class="absolute top-1/2 left-3 size-4 -translate-y-1/2 text-neutral-400"
                        />
                        <input
                            v-model="query"
                            class="h-10 w-full rounded-full border border-neutral-200 bg-neutral-100 pr-3 pl-9 text-sm text-black outline-none"
                            placeholder="Search City, District or Postal Code"
                        />
                    </div>
                    <button
                        type="button"
                        class="flex size-9 items-center justify-center text-neutral-500 hover:text-red-600"
                        @click="emit('close')"
                        aria-label="Close location picker"
                    >
                        <X class="size-4" />
                    </button>
                </div>
            </div>

            <div class="px-4 pt-4">
                <div class="mb-2 flex items-center justify-between">
                    <p class="text-xs text-neutral-500">
                        {{ step === 'region' ? 'Region' : 'Region Selected' }}
                    </p>
                    <button
                        v-if="step !== 'region'"
                        type="button"
                        class="text-sm font-medium text-red-600"
                        @click="reset"
                    >
                        Reset
                    </button>
                </div>

                <div
                    v-if="step !== 'region'"
                    class="mb-4 space-y-2 border-l-2 border-neutral-200 pl-4"
                >
                    <p class="text-sm text-black">{{ region }}</p>
                    <p v-if="province" class="text-sm text-black">
                        {{ province }}
                    </p>
                    <p v-if="city" class="text-sm text-black">{{ city }}</p>
                    <p class="text-sm font-medium text-red-600">
                        Select {{ stepTitle }}
                    </p>
                </div>
            </div>

            <div class="flex-1 pb-8">
                <p class="bg-neutral-100 px-4 py-2 text-xs text-neutral-500">
                    {{ stepTitle }}
                </p>
                <div v-for="group in grouped" :key="group.letter" class="flex">
                    <div class="w-8 pt-3 text-center text-xs text-neutral-400">
                        {{ group.letter === 'Results' ? '' : group.letter }}
                    </div>
                    <div class="flex-1">
                        <template v-if="group.rows">
                            <button
                                v-for="row in group.rows"
                                :key="`${row.region}-${row.barangay}`"
                                type="button"
                                class="block w-full border-b border-neutral-100 px-3 py-3 text-left text-sm text-black"
                                @click="pickSearchRow(row)"
                            >
                                {{ row.barangay }}
                                <span class="block text-xs text-neutral-500">
                                    {{ row.city }}, {{ row.province }}
                                    {{ row.postalCode }}
                                </span>
                            </button>
                        </template>
                        <template v-else>
                            <button
                                v-for="item in group.items"
                                :key="item"
                                type="button"
                                class="block w-full border-b border-neutral-100 px-3 py-3 text-left text-sm text-black"
                                @click="pickName(item)"
                            >
                                {{ item }}
                            </button>
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
