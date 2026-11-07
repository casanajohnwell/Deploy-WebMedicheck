import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { useActiveAddress } from '@/composables/useActiveAddress';
import type { Medicine } from '@/lib/medicheck-demo';

export function useSavedMedicines() {
    const page = usePage();
    const { healthCenterFilter } = useActiveAddress();

    const allMedicines = computed(
        () => (page.props.medicines as Medicine[] | undefined) ?? [],
    );

    const medicines = computed(() =>
        healthCenterFilter.value
            ? allMedicines.value.filter(
                  (item) => item.healthCenter === healthCenterFilter.value,
              )
            : [],
    );

    return {
        allMedicines,
        medicines,
        healthCenterFilter,
    };
}
