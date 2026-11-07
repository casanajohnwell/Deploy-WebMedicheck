<script setup lang="ts">
import { useActiveAddress } from '@/composables/useActiveAddress';

const { locationLabel } = useActiveAddress();
</script>

<template>
    <div class="flex aspect-square size-8 items-center justify-center">
        <img src="/images/medicheck-logo.svg" alt="MediCheck" class="size-8" />
    </div>
    <div class="ml-1 grid flex-1 text-left text-sm leading-tight">
        <span class="truncate font-semibold tracking-wide"> MEDICHECK </span>
        <span class="truncate text-[10px] tracking-wider text-black uppercase">
            {{ locationLabel || 'Select lugar' }}
        </span>
    </div>
</template>
