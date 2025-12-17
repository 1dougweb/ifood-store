<script setup lang="ts">
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetFooter,
    SheetHeader,
    SheetTitle,
} from '@/components/ui/sheet';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Checkbox } from '@/components/ui/checkbox';
import { useRestaurants } from '@/composables/useRestaurants';
import { computed } from 'vue';

interface Props {
    open: boolean;
    restaurant?: {
        id: number;
        name: string;
        cnpj?: string | null;
        address?: string | null;
        phone?: string | null;
        whatsapp_number?: string | null;
        is_active: boolean;
    } | null;
}

const props = withDefaults(defineProps<Props>(), {
    restaurant: null,
});

const emit = defineEmits<{
    (e: 'update:open', value: boolean): void;
    (e: 'saved'): void;
}>();

const { createRestaurant, updateRestaurant, isLoading } = useRestaurants();

const isEditing = computed(() => !!props.restaurant);

const form = computed(() => {
    if (props.restaurant) {
        return useForm({
            name: props.restaurant.name,
            cnpj: props.restaurant.cnpj || '',
            address: props.restaurant.address || '',
            phone: props.restaurant.phone || '',
            whatsapp_number: props.restaurant.whatsapp_number || '',
            is_active: props.restaurant.is_active,
        });
    }
    return useForm({
        name: '',
        cnpj: '',
        address: '',
        phone: '',
        whatsapp_number: '',
        is_active: true,
    });
});

const submit = () => {
    if (isEditing.value && props.restaurant) {
        updateRestaurant(props.restaurant.id, form.value.data()).then(() => {
            emit('saved');
            emit('update:open', false);
        });
    } else {
        createRestaurant(form.value.data()).then(() => {
            emit('saved');
            emit('update:open', false);
        });
    }
};
</script>

<template>
    <Sheet :open="open" @update:open="emit('update:open', $event)">
        <SheetContent class="w-full sm:max-w-lg">
            <SheetHeader>
                <SheetTitle>
                    {{ isEditing ? 'Editar Restaurante' : 'Novo Restaurante' }}
                </SheetTitle>
                <SheetDescription>
                    {{
                        isEditing
                            ? 'Atualize as informações do restaurante'
                            : 'Adicione um novo restaurante para monitorar'
                    }}
                </SheetDescription>
            </SheetHeader>

            <form @submit.prevent="submit" class="mt-6 space-y-4">
                <div class="space-y-2">
                    <Label for="name">Nome do Restaurante *</Label>
                    <Input
                        id="name"
                        v-model="form.name"
                        type="text"
                        required
                    />
                </div>

                <div class="space-y-2">
                    <Label for="cnpj">CNPJ</Label>
                    <Input
                        id="cnpj"
                        v-model="form.cnpj"
                        type="text"
                        maxlength="18"
                    />
                </div>

                <div class="space-y-2">
                    <Label for="address">Endereço</Label>
                    <textarea
                        id="address"
                        v-model="form.address"
                        class="flex min-h-[80px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                    />
                </div>

                <div class="space-y-2">
                    <Label for="phone">Telefone</Label>
                    <Input
                        id="phone"
                        v-model="form.phone"
                        type="tel"
                    />
                </div>

                <div class="space-y-2">
                    <Label for="whatsapp_number">WhatsApp *</Label>
                    <Input
                        id="whatsapp_number"
                        v-model="form.whatsapp_number"
                        type="tel"
                        required
                    />
                </div>

                <div class="flex items-center space-x-2">
                    <Checkbox
                        id="is_active"
                        v-model:checked="form.is_active"
                    />
                    <Label for="is_active">Restaurante ativo</Label>
                </div>

                <SheetFooter>
                    <Button
                        type="button"
                        variant="outline"
                        @click="emit('update:open', false)"
                    >
                        Cancelar
                    </Button>
                    <Button type="submit" :disabled="isLoading">
                        {{ isLoading ? 'Salvando...' : 'Salvar' }}
                    </Button>
                </SheetFooter>
            </form>
        </SheetContent>
    </Sheet>
</template>

