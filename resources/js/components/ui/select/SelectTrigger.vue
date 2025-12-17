<script setup lang="ts">
import type { SelectTriggerProps } from 'reka-ui';
import type { HTMLAttributes } from 'vue';
import { SelectTrigger as RekaSelectTrigger, useForwardProps } from 'reka-ui';
import { reactiveOmit } from '@vueuse/core';
import { cn } from '@/lib/utils';

const props = withDefaults(
    defineProps<SelectTriggerProps & { class?: HTMLAttributes['class'] }>(),
    {}
);

const delegatedProps = reactiveOmit(props, 'class');
const forwarded = useForwardProps(delegatedProps);
</script>

<template>
    <RekaSelectTrigger
        v-bind="forwarded"
        :class="
            cn(
                'flex h-9 w-full items-center justify-between rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-sm ring-offset-background placeholder:text-muted-foreground focus:outline-none focus:ring-1 focus:ring-ring disabled:cursor-not-allowed disabled:opacity-50',
                props.class
            )
        "
    >
        <slot />
    </RekaSelectTrigger>
</template>

