import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { useActiveAddress } from '@/composables/useActiveAddress';
import type { Booking } from '@/lib/medicheck-demo';

export function useSavedBookings() {
    const page = usePage();
    const { healthCenterFilter } = useActiveAddress();

    const allBookings = computed(
        () => (page.props.bookings as Booking[] | undefined) ?? [],
    );

    const bookings = computed(() =>
        healthCenterFilter.value
            ? allBookings.value.filter(
                  (item) => item.healthCenter === healthCenterFilter.value,
              )
            : [],
    );

    return {
        allBookings,
        bookings,
        healthCenterFilter,
    };
}
