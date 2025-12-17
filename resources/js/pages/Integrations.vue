<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import HeadingSmall from '@/components/HeadingSmall.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { type BreadcrumbItem } from '@/types';
import { Link as LinkIcon, MessageSquare, Settings } from 'lucide-vue-next';
import restaurantsRoute from '@/routes/restaurants';
import { computed, ref, onMounted } from 'vue';
import { useI18n } from '@/composables/useI18n';

interface Props {
    restaurants: Array<{
        id: number;
        name: string;
        ifood_merchant_id: string | null;
        ifood_access_token: string | null;
        whatsapp_number: string | null;
    }>;
    evolutionApiUrl?: string | null;
    webhookUrl?: string;
}

const props = defineProps<Props>();
const { t } = useI18n();
const page = usePage();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: t('common.dashboard'),
        href: '/dashboard',
    },
    {
        title: t('integration.title'),
    },
];

const webhookUrl = computed(() => props.webhookUrl || '');

const copyWebhookUrl = () => {
    if (typeof navigator !== 'undefined' && typeof window !== 'undefined') {
        navigator.clipboard.writeText(webhookUrl.value);
    }
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head :title="t('integration.title')" />
        <div class="px-4 py-6">
            <div class="space-y-6">
                <HeadingSmall
                    :title="t('integration.title')"
                    :description="'Gerencie suas integrações com iFood e WhatsApp'"
                />

                <!-- iFood Integration -->
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <LinkIcon class="h-5 w-5" />
                            {{ t('integration.ifood') }}
                        </CardTitle>
                        <CardDescription>
                            Conecte suas contas do iFood para receber pedidos em tempo real
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div
                            v-if="restaurants.length === 0"
                            class="rounded-lg bg-muted p-4 text-center"
                        >
                            <p class="text-sm text-muted-foreground">
                                Nenhum restaurante cadastrado. Adicione um restaurante primeiro.
                            </p>
                            <Link
                                :href="restaurantsRoute.create().url"
                                class="mt-4 inline-block"
                            >
                                <Button>{{ t('restaurant.create') }}</Button>
                            </Link>
                        </div>

                        <div v-else class="space-y-4">
                            <div
                                v-for="restaurant in restaurants"
                                :key="restaurant.id"
                                class="rounded-lg border p-4"
                            >
                                <div class="flex items-start justify-between">
                                    <div class="flex-1">
                                        <h3 class="font-semibold">{{ restaurant.name }}</h3>
                                        <div class="mt-2 flex items-center gap-2">
                                            <Badge
                                                :variant="
                                                    restaurant.ifood_merchant_id
                                                        ? 'default'
                                                        : 'secondary'
                                                "
                                            >
                                                {{
                                                    restaurant.ifood_merchant_id
                                                        ? t('integration.configured')
                                                        : t('integration.notConfigured')
                                                }}
                                            </Badge>
                                        </div>
                                        <p
                                            v-if="restaurant.ifood_merchant_id"
                                            class="mt-2 text-sm text-muted-foreground"
                                        >
                                            Merchant ID: {{ restaurant.ifood_merchant_id }}
                                        </p>
                                    </div>
                                    <Link
                                        :href="restaurantsRoute.edit({ restaurant: restaurant.id }).url"
                                    >
                                        <Button variant="outline" size="sm">
                                            <Settings class="mr-2 h-4 w-4" />
                                            {{
                                                restaurant.ifood_merchant_id
                                                    ? t('common.edit')
                                                    : t('restaurant.connectIfood')
                                            }}
                                        </Button>
                                    </Link>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- WhatsApp Integration -->
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <MessageSquare class="h-5 w-5" />
                            {{ t('integration.whatsapp') }}
                        </CardTitle>
                        <CardDescription>
                            A integração com WhatsApp é configurada globalmente através das
                            variáveis de ambiente
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="rounded-lg bg-blue-50 p-4 dark:bg-blue-900/20">
                            <p class="text-sm text-blue-800 dark:text-blue-200">
                                ℹ️ A configuração da Evolution API é feita no arquivo
                                <code class="rounded bg-blue-100 px-1 py-0.5 dark:bg-blue-900"
                                    >.env</code
                                >
                                do servidor. Entre em contato com o administrador do sistema
                                para configurar.
                            </p>
                        </div>

                        <div class="mt-4 space-y-2 text-sm">
                            <div class="flex justify-between">
                                <span class="text-muted-foreground">URL da API:</span>
                                <span class="font-mono text-xs">
                                    {{ evolutionApiUrl || t('integration.notConfigured') }}
                                </span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-muted-foreground">Status:</span>
                                <Badge variant="outline">
                                    {{
                                        evolutionApiUrl
                                            ? t('integration.configured')
                                            : t('integration.notConfigured')
                                    }}
                                </Badge>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Webhook Configuration -->
                <Card>
                    <CardHeader>
                        <CardTitle>{{ t('integration.webhook') }}</CardTitle>
                        <CardDescription>
                            Configure o endpoint de webhook no painel do desenvolvedor do iFood
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="space-y-4">
                            <div class="space-y-2">
                                <Label>{{ t('integration.webhookUrl') }}</Label>
                                <div class="flex items-center gap-2">
                                    <Input
                                        :value="webhookUrl"
                                        readonly
                                        class="font-mono text-xs"
                                    />
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        @click="copyWebhookUrl"
                                    >
                                        {{ t('integration.copy') }}
                                    </Button>
                                </div>
                                <p class="text-xs text-muted-foreground">
                                    Use esta URL ao configurar webhooks no portal do desenvolvedor
                                    do iFood
                                </p>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>

