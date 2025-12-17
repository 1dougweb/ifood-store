<script setup lang="ts">
import { computed } from 'vue';
import { X, CheckCircle2, AlertCircle, Info, AlertTriangle } from 'lucide-vue-next';
import { cn } from '@/lib/utils';
import type { Toast } from '@/composables/useToast';

interface Props {
    toast: Toast;
}

const props = defineProps<Props>();

const emit = defineEmits<{
    remove: [id: string];
}>();

const typeConfig = computed(() => {
    const configs = {
        success: {
            icon: CheckCircle2,
            bgColor: 'bg-green-50 dark:bg-green-900/20',
            borderColor: 'border-green-200 dark:border-green-800',
            textColor: 'text-green-800 dark:text-green-200',
            iconColor: 'text-green-600 dark:text-green-400',
        },
        error: {
            icon: AlertCircle,
            bgColor: 'bg-red-50 dark:bg-red-900/20',
            borderColor: 'border-red-200 dark:border-red-800',
            textColor: 'text-red-800 dark:text-red-200',
            iconColor: 'text-red-600 dark:text-red-400',
        },
        warning: {
            icon: AlertTriangle,
            bgColor: 'bg-yellow-50 dark:bg-yellow-900/20',
            borderColor: 'border-yellow-200 dark:border-yellow-800',
            textColor: 'text-yellow-800 dark:text-yellow-200',
            iconColor: 'text-yellow-600 dark:text-yellow-400',
        },
        info: {
            icon: Info,
            bgColor: 'bg-blue-50 dark:bg-blue-900/20',
            borderColor: 'border-blue-200 dark:border-blue-800',
            textColor: 'text-blue-800 dark:text-blue-200',
            iconColor: 'text-blue-600 dark:text-blue-400',
        },
    };

    return configs[props.toast.type];
});

const Icon = computed(() => typeConfig.value.icon);

const handleRemove = () => {
    emit('remove', props.toast.id);
};
</script>

<template>
    <div
        :class="
            cn(
                'flex items-start gap-3 rounded-lg border p-4 shadow-lg transition-all',
                typeConfig.bgColor,
                typeConfig.borderColor,
            )
        "
    >
        <component :is="Icon" :class="cn('h-5 w-5 flex-shrink-0', typeConfig.iconColor)" />
        <div class="flex-1 min-w-0">
            <p v-if="toast.title" :class="cn('font-semibold text-sm', typeConfig.textColor)">
                {{ toast.title }}
            </p>
            <p :class="cn('text-sm', typeConfig.textColor, { 'mt-1': toast.title })">
                {{ toast.message }}
            </p>
        </div>
        <button
            @click="handleRemove"
            :class="
                cn(
                    'flex-shrink-0 rounded-md p-1 transition-colors hover:bg-black/5 dark:hover:bg-white/5',
                    typeConfig.textColor,
                )
            "
        >
            <X class="h-4 w-4" />
        </button>
    </div>
</template>

