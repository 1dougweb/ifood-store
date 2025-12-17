# Deploy no EasyPanel

Este guia explica como fazer deploy da aplicação no EasyPanel.

## 📋 Configuração no EasyPanel

### 1. Variáveis de Ambiente

Configure as seguintes variáveis no painel do EasyPanel:

#### Aplicação
```
APP_NAME=Laravel
APP_ENV=production
APP_DEBUG=false
APP_URL=https://seu-dominio.com
```

#### Banco de Dados
```
DB_CONNECTION=mysql
DB_HOST=<host-do-banco>
DB_PORT=3306
DB_DATABASE=<nome-do-banco>
DB_USERNAME=<usuario>
DB_PASSWORD=<senha>
```

#### Cache e Sessão
```
CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis
REDIS_HOST=<host-redis>
REDIS_PORT=6379
REDIS_PASSWORD=<senha-redis>
```

#### iFood Integration
```
IFOOD_CLIENT_ID=<seu-client-id>
IFOOD_CLIENT_SECRET=<seu-client-secret>
IFOOD_BASE_URL=https://merchant-api.ifood.com.br
```

### 2. Build Args (Opcional)

O EasyPanel pode passar build args. O Dockerfile está configurado para aceitar variáveis de ambiente, então não é necessário configurar build args separadamente.

### 3. Porta

Configure a porta **80** no EasyPanel.

### 4. Health Check

O Dockerfile inclui um health check no endpoint `/health`. O EasyPanel pode usar isso para verificar a saúde da aplicação.

## 🚀 Deploy

1. Conecte seu repositório Git no EasyPanel
2. Configure as variáveis de ambiente acima
3. Configure a porta 80
4. Faça o deploy

O Dockerfile irá:
- Buildar os assets do Node.js
- Instalar dependências do PHP
- Configurar Nginx, PHP-FPM, Queue Worker e Scheduler
- Executar migrações automaticamente
- Cachear configurações para produção

## 🔧 Pós-Deploy

Após o primeiro deploy, você pode precisar:

1. **Executar seeders** (se necessário):
   - Acesse o terminal do container no EasyPanel
   - Execute: `php artisan db:seed --force`

2. **Verificar logs**:
   - Logs do Nginx: `/var/log/nginx/`
   - Logs do PHP-FPM: `/var/log/php-fpm.out.log`
   - Logs da Queue: `/var/log/queue.out.log`
   - Logs do Scheduler: `/var/log/scheduler.out.log`

3. **Verificar permissões**:
   - Storage e cache devem ter permissões 775
   - Ownership deve ser www-data:www-data

## 📝 Notas Importantes

- O Dockerfile usa **multi-stage build** para otimizar o tamanho da imagem
- Assets são buildados durante o build da imagem
- Supervisor gerencia PHP-FPM, Nginx, Queue Worker e Scheduler
- Migrações são executadas automaticamente no startup
- A aplicação está configurada para produção por padrão

## 🐛 Troubleshooting

### Erro ao fazer build

- Verifique se todos os arquivos necessários estão no repositório
- Certifique-se de que `package.json` e `composer.json` estão presentes
- Verifique os logs de build no EasyPanel

### Erro 502 Bad Gateway

- Verifique se PHP-FPM está rodando: `ps aux | grep php-fpm`
- Verifique logs do Nginx: `/var/log/nginx/error.log`
- Verifique se a porta 80 está configurada corretamente

### Assets não carregam

- Verifique se o build dos assets foi executado: `ls -la public/build`
- Rebuild os assets se necessário: `npm run build`

### Migrações não executam

- Verifique conexão com banco de dados
- Verifique logs: `php artisan migrate --force -vvv`
- Execute manualmente se necessário
