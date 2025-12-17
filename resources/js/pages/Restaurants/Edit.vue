<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Checkbox } from '@/components/ui/checkbox';
import { Badge } from '@/components/ui/badge';
import { dashboard } from '@/routes';
import restaurantsRoute from '@/routes/restaurants';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Link as LinkIcon, Copy, Check } from 'lucide-vue-next';
import { ref, watch, onMounted, computed } from 'vue';
import { useIfoodAuth } from '@/composables/useIfoodAuth';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';

interface Props {
    restaurant: {
        id: number;
        name: string;
        cnpj: string | null;
        address: string | null;
        phone: string | null;
        whatsapp_number: string | null;
        is_active: boolean;
        ifood_merchant_id: string | null;
        ifood_access_token: string | null;
        ifood_client_id?: string | null;
        ifood_client_secret?: string | null;
        notification_settings: any;
    };
    webhookUrl?: string;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard().url,
    },
    {
        title: 'Restaurantes',
        href: restaurantsRoute.index().url,
    },
    {
        title: props.restaurant.name,
        href: restaurantsRoute.show({ restaurant: props.restaurant.id }).url,
    },
    {
        title: 'Editar',
        href: restaurantsRoute.edit({ restaurant: props.restaurant.id }).url,
    },
];

const form = useForm({
    name: props.restaurant.name,
    cnpj: props.restaurant.cnpj || '',
    address: props.restaurant.address || '',
    phone: props.restaurant.phone || '',
    whatsapp_number: props.restaurant.whatsapp_number || '',
    ifood_client_id: props.restaurant.ifood_client_id || '',
    ifood_client_secret: props.restaurant.ifood_client_secret || '',
    is_active: Boolean(props.restaurant.is_active ?? false),
    notification_settings: props.restaurant.notification_settings || {
        enabled_events: [
            'new_order',
            'delayed_order',
            'delivered_order',
            'cancelled_order',
        ],
    },
});

// Máscaras
const cnpjMask = (value: string) => {
    const numbers = value.replace(/\D/g, '');
    if (numbers.length <= 14) {
        return numbers
            .replace(/(\d{2})(\d)/, '$1.$2')
            .replace(/(\d{3})(\d)/, '$1.$2')
            .replace(/(\d{3})(\d)/, '$1/$2')
            .replace(/(\d{4})(\d)/, '$1-$2');
    }
    return value;
};

const phoneMask = (value: string) => {
    const numbers = value.replace(/\D/g, '');
    if (numbers.length <= 10) {
        return numbers
            .replace(/(\d{2})(\d)/, '($1) $2')
            .replace(/(\d{4})(\d)/, '$1-$2');
    } else if (numbers.length <= 11) {
        return numbers
            .replace(/(\d{2})(\d)/, '($1) $2')
            .replace(/(\d{5})(\d)/, '$1-$2');
    }
    return value;
};

const whatsappMask = (value: string) => {
    return value.replace(/\D/g, '');
};

// Campos com máscara visual
const cnpjDisplay = ref('');
const phoneDisplay = ref('');

// Inicializar campos com valores formatados
onMounted(() => {
    if (props.restaurant.cnpj) {
        cnpjDisplay.value = cnpjMask(props.restaurant.cnpj);
    }
    if (props.restaurant.phone) {
        phoneDisplay.value = phoneMask(props.restaurant.phone);
    }
});

watch(cnpjDisplay, (value) => {
    const masked = cnpjMask(value);
    cnpjDisplay.value = masked;
    form.cnpj = masked.replace(/\D/g, '');
});

watch(phoneDisplay, (value) => {
    const masked = phoneMask(value);
    phoneDisplay.value = masked;
    form.phone = masked.replace(/\D/g, '');
});

watch(() => form.whatsapp_number, (value) => {
    form.whatsapp_number = whatsappMask(value);
});

const submit = () => {
    // Garantir que is_active sempre seja enviado como boolean
    form.transform((data) => ({
        ...data,
        is_active: Boolean(data.is_active ?? false),
    })).put(restaurantsRoute.update({ restaurant: props.restaurant.id }).url);
};

const copyUserCode = async () => {
    if (userCodeData.value?.userCode) {
        try {
            await navigator.clipboard.writeText(userCodeData.value.userCode);
        } catch (err) {
            console.error('Failed to copy:', err);
        }
    }
};

const {
    connect: connectIfood,
    isConnecting,
    isGettingCode,
    isCheckingAuthorization,
    error: connectionError,
    userCodeData,
    countdown,
    getUserCode,
    checkAuthorization,
    formatTime,
} = useIfoodAuth();

// Gerar URL do webhook
const webhookUrl = computed(() => {
    // Se a prop vier do servidor, usar ela
    if (props.webhookUrl && typeof props.webhookUrl === 'string' && props.webhookUrl.trim() !== '') {
        return props.webhookUrl;
    }
    
    // Fallback: sempre gerar no cliente
    if (typeof window !== 'undefined' && window.location) {
        return `${window.location.origin}/api/webhooks/ifood`;
    }
    
    // Fallback final para SSR
    return 'http://127.0.0.1:8000/api/webhooks/ifood';
});

const copied = ref(false);

// Computed para garantir que is_active seja sempre boolean e reativo
const isActiveChecked = computed({
    get: () => Boolean(form.is_active),
    set: (value: boolean) => {
        form.is_active = Boolean(value);
    },
});

const copyWebhookUrl = async () => {
    if (typeof navigator !== 'undefined' && webhookUrl.value) {
        try {
            await navigator.clipboard.writeText(webhookUrl.value);
            copied.value = true;
            // Resetar após 2 segundos
            setTimeout(() => {
                copied.value = false;
            }, 2000);
        } catch (error) {
            console.error('Erro ao copiar:', error);
        }
    }
};
</script>

<template>
    <Head :title="`Editar ${restaurant.name}`" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4">
            <div class="flex items-center gap-4">
                <Link :href="restaurantsRoute.show({ restaurant: restaurant.id }).url">
                    <Button variant="ghost" size="icon">
                        <ArrowLeft class="h-5 w-5" />
                    </Button>
                </Link>
                <div>
                    <h1 class="text-3xl font-bold">Editar Restaurante</h1>
                    <p class="text-muted-foreground">
                        Atualize as informações do restaurante
                    </p>
                </div>
            </div>

            <form @submit.prevent="submit" class="max-w-2xl space-y-6">
                <Card>
                    <CardHeader>
                        <CardTitle>Informações Básicas</CardTitle>
                        <CardDescription>
                            Dados principais do restaurante
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div class="space-y-2">
                            <Label for="name">Nome do Restaurante *</Label>
                            <Input
                                id="name"
                                v-model="form.name"
                                type="text"
                                required
                            />
                            <p
                                v-if="form.errors.name"
                                class="text-sm text-destructive"
                            >
                                {{ form.errors.name }}
                            </p>
                        </div>

                        <div class="space-y-2">
                            <Label for="cnpj">CNPJ</Label>
                            <Input
                                id="cnpj"
                                v-model="cnpjDisplay"
                                type="text"
                                placeholder="00.000.000/0000-00"
                                maxlength="18"
                            />
                        </div>

                        <div class="space-y-2">
                            <Label for="address">Endereço</Label>
                            <textarea
                                id="address"
                                v-model="form.address"
                                class="flex min-h-[80px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                            />
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Contato</CardTitle>
                        <CardDescription>
                            Informações de contato e WhatsApp
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4">
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
                                placeholder="5511999999999"
                                maxlength="15"
                                required
                            />
                            <p
                                v-if="form.errors.whatsapp_number"
                                class="text-sm text-destructive"
                            >
                                {{ form.errors.whatsapp_number }}
                            </p>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Integração iFood</CardTitle>
                        <CardDescription>
                            Configure suas credenciais do iFood Developer e conecte sua conta
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <!-- Credentials Input -->
                        <div class="space-y-4">
                            <div class="space-y-2">
                                <Label for="ifood_client_id">Client ID do iFood</Label>
                                <Input
                                    id="ifood_client_id"
                                    v-model="form.ifood_client_id"
                                    type="text"
                                    placeholder="Seu Client ID do portal do desenvolvedor iFood"
                                />
                                <p class="text-xs text-muted-foreground">
                                    Obtenha na aba <strong>"Credenciais"</strong> do <a href="https://developer.ifood.com.br" target="_blank" class="text-primary underline">Portal de Desenvolvedores do iFood</a> (Meus Apps → [seu app] → Credenciais)
                                </p>
                            </div>

                            <div class="space-y-2">
                                <Label for="ifood_client_secret">Client Secret do iFood</Label>
                                <Input
                                    id="ifood_client_secret"
                                    v-model="form.ifood_client_secret"
                                    type="password"
                                    placeholder="Seu Client Secret do portal do desenvolvedor iFood"
                                />
                                <p class="text-xs text-muted-foreground">
                                    Obtenha na aba <strong>"Credenciais"</strong> do portal do desenvolvedor. Mantenha este valor seguro e não compartilhe.
                                    <br><strong>Nota:</strong> O clientSecret do webhook (na aba "Webhook") é diferente do Client Secret usado para autenticação.
                                </p>
                            </div>
                        </div>

                        <!-- Webhook URL -->
                        <div class="space-y-2">
                            <Label>URL do Webhook</Label>
                            <div class="flex items-center gap-2">
                                <Input
                                    :value="webhookUrl"
                                    readonly
                                    class="font-mono text-xs"
                                    placeholder="http://127.0.0.1:8000/api/webhooks/ifood"
                                />
                                <TooltipProvider>
                                    <Tooltip>
                                        <TooltipTrigger as-child>
                                            <Button
                                                type="button"
                                                :variant="copied ? 'default' : 'outline'"
                                                size="sm"
                                                @click="copyWebhookUrl"
                                                :class="copied ? 'bg-green-600 hover:bg-green-700 text-white' : ''"
                                            >
                                                <Check v-if="copied" class="h-4 w-4" />
                                                <Copy v-else class="h-4 w-4" />
                                            </Button>
                                        </TooltipTrigger>
                                        <TooltipContent>
                                            <p>{{ copied ? 'Copiado!' : 'Copiar URL' }}</p>
                                        </TooltipContent>
                                    </Tooltip>
                                </TooltipProvider>
                            </div>
                            <p class="text-xs text-muted-foreground">
                                Configure esta URL no portal do desenvolvedor do iFood (Meus Apps → [seu app] → Webhook)
                            </p>
                        </div>

                        <!-- Connection Status -->
                        <div
                            v-if="restaurant.ifood_merchant_id"
                            class="rounded-lg bg-green-50 p-4 dark:bg-green-900/20"
                        >
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="font-medium text-green-800 dark:text-green-200">
                                        ✅ Conectado ao iFood
                                    </p>
                                    <p class="text-sm text-green-600 dark:text-green-300">
                                        Merchant ID: {{ restaurant.ifood_merchant_id }}
                                    </p>
                                </div>
                                <Badge variant="default">Conectado</Badge>
                            </div>
                        </div>
                        <div
                            v-else-if="form.ifood_client_id && form.ifood_client_secret"
                            class="rounded-lg bg-blue-50 p-4 dark:bg-blue-900/20"
                        >
                            <!-- Mostrar código OTP se já foi gerado -->
                            <div v-if="userCodeData" class="mb-4">
                                <div class="mb-3 rounded-lg bg-white p-4 dark:bg-gray-800 border-2 border-blue-300 dark:border-blue-700">
                                    <p class="text-xs font-medium text-gray-600 dark:text-gray-400 mb-2">
                                        Código de autorização (expira em {{ formatTime(countdown) }})
                                    </p>
                                    <div class="flex items-center justify-between mb-3">
                                        <code class="text-2xl font-bold text-blue-600 dark:text-blue-400 tracking-wider">
                                            {{ userCodeData.userCode }}
                                        </code>
                                        <Button
                                            type="button"
                                            variant="outline"
                                            size="sm"
                                            @click="copyUserCode"
                                        >
                                            <Copy class="h-4 w-4 mr-1" />
                                            Copiar
                                        </Button>
                                    </div>
                                    <p class="text-xs text-gray-600 dark:text-gray-400 mb-3">
                                        1. Acesse o portal do iFood usando o link abaixo<br>
                                        2. Insira o código acima quando solicitado<br>
                                        3. Após autorizar, clique em "Verificar Autorização"
                                    </p>
                                    <a
                                        :href="userCodeData.verificationUrlComplete"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="inline-flex items-center text-sm text-blue-600 dark:text-blue-400 hover:underline"
                                    >
                                        <LinkIcon class="h-4 w-4 mr-1" />
                                        Abrir portal do iFood
                                    </a>
                                </div>
                                <div class="flex gap-2">
                                    <Button
                                        type="button"
                                        @click="() => checkAuthorization(restaurant.id)"
                                        :disabled="isCheckingAuthorization || countdown === 0"
                                        class="flex-1"
                                    >
                                        <LinkIcon class="mr-2 h-4 w-4" />
                                        {{ isCheckingAuthorization ? 'Verificando...' : 'Verificar Autorização' }}
                                    </Button>
                                    <Button
                                        type="button"
                                        variant="outline"
                                        @click="() => {
                                            userCodeData = null;
                                            countdown = 0;
                                        }"
                                    >
                                        Cancelar
                                    </Button>
                                </div>
                            </div>
                            
                            <!-- Mostrar botão para gerar código se ainda não foi gerado -->
                            <div v-else>
                                <p class="mb-3 text-sm text-blue-800 dark:text-blue-200 flex">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-5 mr-2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 18v-5.25m0 0a6.01 6.01 0 0 0 1.5-.189m-1.5.189a6.01 6.01 0 0 1-1.5-.189m3.75 7.478a12.06 12.06 0 0 1-4.5 0m3.75 2.383a14.406 14.406 0 0 1-3 0M14.25 18v-.192c0-.983.658-1.823 1.508-2.316a7.5 7.5 0 1 0-7.517 0c.85.493 1.509 1.333 1.509 2.316V18" />
                                    </svg>
                                    Credenciais configuradas. Clique no botão abaixo para gerar um código de autorização (OTP) que você inserirá no portal do iFood.
                                </p>
                                <div v-if="connectionError" class="mb-3 rounded-lg bg-red-50 p-3 dark:bg-red-900/20">
                                    <p class="text-sm font-medium text-red-800 dark:text-red-200">
                                        Erro:
                                    </p>
                                    <p class="mt-1 text-xs text-red-600 dark:text-red-300">
                                        {{ connectionError }}
                                    </p>
                                    <p v-if="connectionError.includes('Grant type')" class="mt-2 text-xs text-red-600 dark:text-red-300">
                                        <strong>Solução:</strong> Acesse o portal do desenvolvedor do iFood, vá em "Meus Apps" → [seu app] → "Permissões" e certifique-se de que o grant type de autorização por código está habilitado.
                                    </p>
                                </div>
                                <Button
                                    type="button"
                                    @click="() => getUserCode(restaurant.id)"
                                    :disabled="isGettingCode"
                                >
                                    <LinkIcon class="mr-2 h-4 w-4" />
                                    {{ isGettingCode ? 'Gerando código...' : 'Gerar Código de Autorização' }}
                                </Button>
                            </div>
                        </div>
                        <div
                            v-else
                            class="rounded-lg bg-yellow-50 p-4 dark:bg-yellow-900/20"
                        >
                            <p class="text-sm text-yellow-800 dark:text-yellow-200 flex gap-2">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-5 mt-[2px]">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                                </svg>
                                Configure suas credenciais do iFood acima para começar a receber pedidos.
                            </p>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Configurações</CardTitle>
                        <CardDescription>
                            Opções de ativação e notificações
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div class="flex items-center space-x-2">
                            <Checkbox
                                id="is_active"
                                :checked="isActiveChecked"
                                @update:checked="(checked) => { isActiveChecked = Boolean(checked) }"
                            />
                            <Label
                                for="is_active"
                                class="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70 cursor-pointer"
                                @click="isActiveChecked = !isActiveChecked"
                            >
                                Restaurante ativo
                            </Label>
                        </div>
                    </CardContent>
                </Card>

                <div class="flex gap-4">
                    <Button type="submit" :disabled="form.processing">
                        {{ form.processing ? 'Salvando...' : 'Salvar Alterações' }}
                    </Button>
                    <Link :href="restaurantsRoute.show({ restaurant: restaurant.id }).url">
                        <Button type="button" variant="outline">Cancelar</Button>
                    </Link>
                </div>
            </form>
        </div>
    </AppLayout>
</template>

