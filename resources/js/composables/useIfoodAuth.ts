import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import restaurantsRoute from '@/routes/restaurants';

export type UserCodeData = {
    userCode: string;
    verificationUrl: string;
    verificationUrlComplete: string;
    expiresIn: number;
};

export function useIfoodAuth() {
    const isConnecting = ref(false);
    const isGettingCode = ref(false);
    const isCheckingAuthorization = ref(false);
    const error = ref<string | null>(null);
    const userCodeData = ref<UserCodeData | null>(null);
    const countdown = ref<number>(0);
    let countdownInterval: ReturnType<typeof setInterval> | null = null;

    const getAuthUrl = async (restaurantId: number): Promise<string> => {
        try {
            const response = await fetch(
                restaurantsRoute.ifood.authUrl({ restaurant: restaurantId }).url
            );
            
            if (!response.ok) {
                const errorData = await response.json().catch(() => ({}));
                throw new Error(errorData.error || `Erro ao obter URL de autorização: ${response.status}`);
            }
            
            const data = await response.json();
            
            if (data.error) {
                throw new Error(data.error);
            }
            
            if (!data.url) {
                throw new Error('URL de autorização não retornada pelo servidor');
            }
            
            return data.url;
        } catch (error) {
            console.error('Error getting iFood auth URL:', error);
            throw error;
        }
    };

    const getUserCode = async (restaurantId: number): Promise<UserCodeData> => {
        isGettingCode.value = true;
        error.value = null;
        
        try {
            const response = await fetch(
                restaurantsRoute.ifood.userCode({ restaurant: restaurantId }).url,
                {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                    },
                }
            );

            const data = await response.json().catch(() => {
                // Se não conseguir fazer parse do JSON, retornar objeto vazio
                return {};
            });
            
            if (!response.ok) {
                const errorMessage = data.error || data.message || `Erro ao obter código: ${response.status}`;
                throw new Error(errorMessage);
            }
            
            if (data.error) {
                throw new Error(data.error);
            }
            
            if (!data.success) {
                throw new Error(data.message || 'Erro desconhecido ao obter código');
            }

            if (!data.userCode || !data.verificationUrlComplete) {
                throw new Error('Dados do código não retornados pelo servidor');
            }

            userCodeData.value = {
                userCode: data.userCode,
                verificationUrl: data.verificationUrl,
                verificationUrlComplete: data.verificationUrlComplete,
                expiresIn: data.expiresIn || 600,
            };

            // Iniciar countdown
            countdown.value = userCodeData.value.expiresIn;
            if (countdownInterval) {
                clearInterval(countdownInterval);
            }
            countdownInterval = setInterval(() => {
                if (countdown.value > 0) {
                    countdown.value--;
                } else {
                    if (countdownInterval) {
                        clearInterval(countdownInterval);
                        countdownInterval = null;
                    }
                    userCodeData.value = null;
                }
            }, 1000);

            return userCodeData.value;
        } catch (err) {
            console.error('Error getting iFood user code:', err);
            const errorMessage = err instanceof Error ? err.message : 'Erro desconhecido';
            error.value = errorMessage;
            throw err;
        } finally {
            isGettingCode.value = false;
        }
    };

    const checkAuthorization = async (restaurantId: number): Promise<boolean> => {
        isCheckingAuthorization.value = true;
        error.value = null;
        
        try {
            router.post(
                restaurantsRoute.ifood.exchangeCode({ restaurant: restaurantId }).url,
                {},
                {
                    onSuccess: () => {
                        // Sucesso - limpar dados e recarregar
                        if (countdownInterval) {
                            clearInterval(countdownInterval);
                            countdownInterval = null;
                        }
                        userCodeData.value = null;
                        countdown.value = 0;
                        isCheckingAuthorization.value = false;
                    },
                    onError: (errors) => {
                        const errorMessage = errors.error || errors.message || 'Código ainda não autorizado';
                        error.value = errorMessage;
                        isCheckingAuthorization.value = false;
                    },
                    onFinish: () => {
                        isCheckingAuthorization.value = false;
                    },
                }
            );
            return true;
        } catch (err) {
            console.error('Error checking authorization:', err);
            const errorMessage = err instanceof Error ? err.message : 'Erro desconhecido';
            error.value = errorMessage;
            isCheckingAuthorization.value = false;
            return false;
        }
    };

    const connect = async (restaurantId: number) => {
        isConnecting.value = true;
        error.value = null;
        
        try {
            // Usar client credentials flow (para aplicações centralizadas)
            router.post(
                restaurantsRoute.ifood.connect({ restaurant: restaurantId }).url,
                {},
                {
                    onSuccess: () => {
                        // Sucesso - a página será recarregada automaticamente
                        isConnecting.value = false;
                    },
                    onError: (errors) => {
                        const errorMessage = errors.error || errors.message || 'Erro ao conectar com iFood';
                        error.value = errorMessage;
                        isConnecting.value = false;
                    },
                    onFinish: () => {
                        isConnecting.value = false;
                    },
                }
            );
        } catch (err) {
            console.error('Error connecting to iFood:', err);
            const errorMessage = err instanceof Error ? err.message : 'Erro desconhecido';
            error.value = errorMessage;
            isConnecting.value = false;
        }
    };

    const formatTime = (seconds: number): string => {
        const mins = Math.floor(seconds / 60);
        const secs = seconds % 60;
        return `${mins}:${secs.toString().padStart(2, '0')}`;
    };

    return {
        isConnecting,
        isGettingCode,
        isCheckingAuthorization,
        error,
        userCodeData,
        countdown,
        connect,
        getUserCode,
        checkAuthorization,
        formatTime,
        getAuthUrl,
    };
}

