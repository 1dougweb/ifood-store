# Configuração de Variáveis de Ambiente

Este documento descreve as variáveis de ambiente necessárias para o funcionamento da plataforma de monitoramento iFood.

## Variáveis do iFood

Para usar a integração com o iFood, você precisa configurar as seguintes variáveis no arquivo `.env`:

```env
# iFood API Configuration
IFOOD_CLIENT_ID=seu_client_id_aqui
IFOOD_CLIENT_SECRET=seu_client_secret_aqui
IFOOD_REDIRECT_URI=https://seu-dominio.com/restaurants/{restaurant}/ifood/callback
IFOOD_BASE_URL=https://merchant-api.ifood.com.br
```

### Como obter as credenciais do iFood

1. Acesse o [Portal de Desenvolvedores do iFood](https://developer.ifood.com.br/)
2. Crie uma conta de desenvolvedor
3. Crie uma nova aplicação
4. Configure o `redirect_uri` para: `https://seu-dominio.com/restaurants/{restaurant}/ifood/callback`
5. Copie o `Client ID` e `Client Secret` gerados

### Exemplo de configuração

```env
IFOOD_CLIENT_ID=abc123xyz789
IFOOD_CLIENT_SECRET=secret123456789
IFOOD_REDIRECT_URI=http://localhost:8000/restaurants/1/ifood/callback
IFOOD_BASE_URL=https://merchant-api.ifood.com.br
```

**Nota:** Para desenvolvimento local, você pode usar `http://localhost:8000` como base do `redirect_uri`. Para produção, use seu domínio real.

## Variáveis da Evolution API (WhatsApp)

Para usar notificações via WhatsApp, configure:

```env
# Evolution API Configuration
EVOLUTION_API_URL=https://sua-evolution-api.com
EVOLUTION_API_KEY=sua_api_key_aqui
EVOLUTION_API_INSTANCE_NAME=default
```

## Variáveis do Banco de Dados

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=nome_do_banco
DB_USERNAME=usuario
DB_PASSWORD=senha
```

## Variáveis de Aplicação

```env
APP_NAME="iFood Monitor"
APP_ENV=local
APP_KEY=base64:...
APP_DEBUG=true
APP_URL=http://localhost:8000
```

## Após configurar

1. Execute `php artisan config:cache` para limpar o cache de configuração
2. Execute `php artisan config:clear` se necessário
3. Reinicie o servidor se estiver rodando

## Nota Importante

As variáveis do iFood são **opcionais** durante o desenvolvimento. O sistema funcionará normalmente sem elas, mas a funcionalidade de integração com iFood não estará disponível até que sejam configuradas.

## Próximos Passos

Após configurar as variáveis de ambiente, consulte o [Guia de Conexão com iFood](./GUIA_CONEXAO_IFOOD.md) para aprender como conectar sua conta do iFood à plataforma.

