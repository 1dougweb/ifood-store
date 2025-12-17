import { router, useForm } from '@inertiajs/vue3';
import restaurants from '@/routes/restaurants';
import { ref, computed } from 'vue';

export function useRestaurants() {
    const isLoading = ref(false);

    const createRestaurant = (data: {
        name: string;
        cnpj?: string;
        address?: string;
        phone?: string;
        whatsapp_number: string;
        is_active?: boolean;
        notification_settings?: any;
    }) => {
        const form = useForm(data);
        form.post(restaurants.store().url, {
            onStart: () => {
                isLoading.value = true;
            },
            onFinish: () => {
                isLoading.value = false;
            },
        });
        return form;
    };

    const updateRestaurant = (
        id: number,
        data: {
            name?: string;
            cnpj?: string;
            address?: string;
            phone?: string;
            whatsapp_number?: string;
            is_active?: boolean;
            notification_settings?: any;
        }
    ) => {
        const form = useForm(data);
        form.put(restaurants.update({ restaurant: id }).url, {
            onStart: () => {
                isLoading.value = true;
            },
            onFinish: () => {
                isLoading.value = false;
            },
        });
        return form;
    };

    const deleteRestaurant = (id: number) => {
        if (confirm('Tem certeza que deseja excluir este restaurante?')) {
            router.delete(restaurants.destroy({ restaurant: id }).url);
        }
    };

    const connectIfood = (id: number) => {
        window.location.href = restaurants.ifood.authUrl({ restaurant: id }).url;
    };

    return {
        isLoading,
        createRestaurant,
        updateRestaurant,
        deleteRestaurant,
        connectIfood,
    };
}

