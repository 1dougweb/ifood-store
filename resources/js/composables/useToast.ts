import { ref } from 'vue';

export type ToastType = 'success' | 'error' | 'warning' | 'info';

export interface Toast {
    id: string;
    type: ToastType;
    title?: string;
    message: string;
    duration?: number;
}

const toasts = ref<Toast[]>([]);

export function useToast() {
    const addToast = (toast: Omit<Toast, 'id'>) => {
        const id = Math.random().toString(36).substring(2, 9);
        const newToast: Toast = {
            ...toast,
            id,
            duration: toast.duration ?? 5000,
        };

        toasts.value.push(newToast);

        // Auto remove after duration
        if (newToast.duration && newToast.duration > 0) {
            setTimeout(() => {
                removeToast(id);
            }, newToast.duration);
        }

        return id;
    };

    const removeToast = (id: string) => {
        const index = toasts.value.findIndex((t) => t.id === id);
        if (index > -1) {
            toasts.value.splice(index, 1);
        }
    };

    const success = (message: string, title?: string, duration?: number) => {
        return addToast({ type: 'success', message, title, duration });
    };

    const error = (message: string, title?: string, duration?: number) => {
        return addToast({ type: 'error', message, title, duration: duration ?? 7000 });
    };

    const warning = (message: string, title?: string, duration?: number) => {
        return addToast({ type: 'warning', message, title, duration });
    };

    const info = (message: string, title?: string, duration?: number) => {
        return addToast({ type: 'info', message, title, duration });
    };

    const clear = () => {
        toasts.value = [];
    };

    return {
        toasts,
        addToast,
        removeToast,
        success,
        error,
        warning,
        info,
        clear,
    };
}

