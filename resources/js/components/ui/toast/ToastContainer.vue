<script setup lang="ts">
import { useToast, type Toast } from '@/composables/useToast';
import ToastComponent from './Toast.vue';

const { toasts, removeToast } = useToast();
</script>

<template>
    <div
        v-if="toasts.length > 0"
        class="fixed bottom-0 right-0 z-50 flex max-h-screen w-full flex-col gap-2 p-4 sm:max-w-md"
        role="region"
        aria-live="polite"
        aria-label="Notifications"
    >
        <TransitionGroup
            name="toast"
            tag="div"
            class="flex flex-col gap-2"
        >
            <ToastComponent
                v-for="toast in toasts"
                :key="toast.id"
                :toast="toast"
                @remove="removeToast"
            />
        </TransitionGroup>
    </div>
</template>

<style scoped>
.toast-enter-active,
.toast-leave-active {
    transition: all 0.3s ease;
}

.toast-enter-from {
    opacity: 0;
    transform: translateX(100%);
}

.toast-leave-to {
    opacity: 0;
    transform: translateX(100%);
}

.toast-move {
    transition: transform 0.3s ease;
}
</style>

