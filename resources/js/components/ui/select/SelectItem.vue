<script setup lang="ts">
import type { SelectItemProps } from 'reka-ui';
import type { HTMLAttributes } from 'vue';
import { SelectItem as RekaSelectItem, useForwardProps } from 'reka-ui';
import { reactiveOmit } from '@vueuse/core';
import { cn } from '@/lib/utils';

const props = withDefaults(
    defineProps<SelectItemProps & { class?: HTMLAttributes['class'] }>(),
    {}
);

const delegatedProps = reactiveOmit(props, 'class');
const forwarded = useForwardProps(delegatedProps);
</script>

<template>
    <RekaSelectItem
        v-bind="forwarded"
        :class="
            cn(
                'relative flex w-full cursor-default select-none items-center rounded-sm py-1.5 pl-2 pr-8 text-sm outline-none focus:bg-accent focus:text-accent-foreground data-[disabled]:pointer-events-none data-[disabled]:opacity-50',
                props.class
            )
        "
    >
        <slot />
    </RekaSelectItem>
</template>

