<script setup lang="ts">
import type { SelectValueProps } from 'reka-ui';
import { SelectValue as RekaSelectValue, useForwardProps } from 'reka-ui';

const props = defineProps<SelectValueProps>();
const forwarded = useForwardProps(props);
</script>

<template>
    <RekaSelectValue v-bind="forwarded">
        <slot />
    </RekaSelectValue>
</template>

