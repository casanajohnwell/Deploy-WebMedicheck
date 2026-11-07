import { computed, onMounted, ref } from 'vue';
import { healthCenterForBarangay } from '@/lib/ph-locations';

export type AddressLabel = 'home' | 'work' | 'other';

export type SavedAddress = {
    id: string;
    label: AddressLabel;
    region: string;
    province: string;
    city: string;
    barangay: string;
    street: string;
    phone?: string;
    postalCode?: string;
};

const ADDRESSES_KEY = 'medicheck.addresses';
const ACTIVE_KEY = 'medicheck.activeAddressId';

const addresses = ref<SavedAddress[]>([]);
const activeId = ref<string | null>(null);
const hydrated = ref(false);

function loadAddresses(): SavedAddress[] {
    if (typeof window === 'undefined') {
        return [];
    }

    try {
        const raw = window.localStorage.getItem(ADDRESSES_KEY);

        return raw ? (JSON.parse(raw) as SavedAddress[]) : [];
    } catch {
        return [];
    }
}

function loadActiveId(): string | null {
    if (typeof window === 'undefined') {
        return null;
    }

    return window.localStorage.getItem(ACTIVE_KEY);
}

function persist() {
    if (typeof window === 'undefined') {
        return;
    }

    window.localStorage.setItem(ADDRESSES_KEY, JSON.stringify(addresses.value));

    if (activeId.value) {
        window.localStorage.setItem(ACTIVE_KEY, activeId.value);
    } else {
        window.localStorage.removeItem(ACTIVE_KEY);
    }
}

export function useActiveAddress() {
    onMounted(() => {
        const saved = loadAddresses();
        const currentId = loadActiveId();
        const exclusive =
            saved.find((item) => item.id === currentId) ?? saved[0] ?? null;

        addresses.value = exclusive ? [exclusive] : [];
        activeId.value = exclusive?.id ?? null;
        persist();
        hydrated.value = true;
    });

    const activeAddress = computed(
        () =>
            addresses.value.find((item) => item.id === activeId.value) ?? null,
    );

    const healthCenterFilter = computed(() =>
        activeAddress.value
            ? healthCenterForBarangay(activeAddress.value.barangay)
            : null,
    );

    const locationLabel = computed(() => {
        if (!activeAddress.value) {
            return '';
        }

        return `${activeAddress.value.barangay}, ${activeAddress.value.city}, ${activeAddress.value.province}`;
    });

    function setActive(id: string) {
        activeId.value = id;
        persist();
    }

    function saveAddress(address: Omit<SavedAddress, 'id'> & { id?: string }) {
        const id = address.id ?? `${Date.now()}`;
        const next: SavedAddress = { ...address, id };

        addresses.value = [next];
        activeId.value = id;
        persist();

        return next;
    }

    function setLocation(location: {
        barangay: string;
        city: string;
        province: string;
        region: string;
        postalCode?: string | null;
    }) {
        return saveAddress({
            label: 'work',
            region: location.region,
            province: location.province,
            city: location.city,
            barangay: location.barangay,
            postalCode: location.postalCode ?? undefined,
            street: healthCenterForBarangay(location.barangay),
        });
    }

    function syncFromStation(station: {
        barangay: string;
        city: string | null;
        province: string | null;
        region: string | null;
        postal_code?: string | null;
    } | null) {
        if (!station) {
            return;
        }

        const current = activeAddress.value;
        const same =
            current?.barangay === station.barangay &&
            current.city === (station.city ?? '') &&
            current.province === (station.province ?? '');

        if (!same) {
            setLocation({
                barangay: station.barangay,
                city: station.city ?? '',
                province: station.province ?? '',
                region: station.region ?? '',
                postalCode: station.postal_code,
            });
        }

        hydrated.value = true;
    }

    function removeAddress(id: string) {
        addresses.value = addresses.value.filter((item) => item.id !== id);

        if (activeId.value === id) {
            activeId.value = addresses.value[0]?.id ?? null;
        }

        persist();
    }

    return {
        hydrated,
        addresses,
        activeAddress,
        healthCenterFilter,
        locationLabel,
        setActive,
        saveAddress,
        setLocation,
        syncFromStation,
        removeAddress,
    };
}
