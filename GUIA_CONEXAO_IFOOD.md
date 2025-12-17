# Guia de Conexão com iFood

Este guia explica passo a passo como conectar restaurantes com o iFood na plataforma de monitoramento.

## 📋 Como Funciona

A plataforma suporta **múltiplos restaurantes de múltiplos clientes**. Cada restaurante pode ter suas próprias credenciais do iFood Developer, permitindo que cada cliente gerencie sua própria integração.

### Arquitetura Multi-tenant

- **Cada restaurante** pode ter suas próprias credenciais (`ifood_client_id` e `ifood_client_secret`)
- **Cada restaurante** tem seus próprios tokens de acesso (`ifood_access_token`, `ifood_refresh_token`)
- **Cada restaurante** tem seu próprio `Merchant ID` após a conexão
- O `redirect_uri` é gerado dinamicamente: `{APP_URL}/restaurants/{restaurant_id}/ifood/callback`

## 🔧 Pré-requisitos

### 1. Credenciais do iFood Developer

Cada cliente precisa:

1. Acessar o [Portal de Desenvolvedores do iFood](https://developer.ifood.com.br/)
2. Criar uma conta de desenvolvedor
3. Criar uma nova aplicação
4. Configurar o `redirect_uri` no formato:
   - **Desenvolvimento:** `http://localhost:8000/restaurants/*/ifood/callback`
   - **Produção:** `https://seu-dominio.com/restaurants/*/ifood/callback`
   
   **Nota:** O `*` permite qualquer ID de restaurante. O iFood aceita wildcards ou você pode registrar múltiplos redirect URIs.

5. Anotar o `Client ID` e `Client Secret`

### 2. Variáveis de Ambiente (Opcional)

As variáveis globais no `.env` são **opcionais** e servem como fallback:

```env
# Opcional - usado apenas se o restaurante não tiver credenciais próprias
IFOOD_CLIENT_ID=fallback_client_id
IFOOD_CLIENT_SECRET=fallback_client_secret
IFOOD_BASE_URL=https://merchant-api.ifood.com.br
```

**Importante:** O `IFOOD_REDIRECT_URI` não é mais necessário, pois é gerado dinamicamente.

## 🔗 Passo a Passo para Conectar

### 1. Criar ou Editar Restaurante

1. Faça login na plataforma
2. Vá em **Restaurantes** no menu lateral
3. Clique em **Novo Restaurante** ou edite um existente

### 2. Configurar Credenciais do iFood

Na página de criação/edição do restaurante, você verá a seção **"Integração iFood"** com:

- **Campo Client ID:** Digite o Client ID obtido no portal do desenvolvedor iFood
- **Campo Client Secret:** Digite o Client Secret (campo de senha para segurança)

### 3. Salvar as Credenciais

1. Preencha os campos `Client ID` e `Client Secret`
2. Clique em **"Salvar Alterações"** (ou **"Salvar"** se for novo restaurante)
3. As credenciais serão armazenadas de forma segura no banco de dados

### 4. Conectar com o iFood

Após salvar as credenciais:

1. Você verá uma mensagem azul: **"Credenciais configuradas. Clique no botão abaixo para autorizar a conexão."**
2. Clique no botão **"Conectar iFood"**
3. Você será redirecionado para o portal de autorização do iFood

### 5. Autorizar no Portal do iFood

1. Faça login com sua conta do iFood (se ainda não estiver logado)
2. Revise as permissões solicitadas (MERCHANT_ORDERS)
3. Clique em **"Autorizar"** ou **"Permitir"**

### 6. Confirmação Automática

Após autorizar:
- Você será redirecionado automaticamente de volta para a plataforma
- A conexão será processada automaticamente
- Os tokens serão salvos no banco de dados
- O Merchant ID será obtido e salvo
- Você verá uma mensagem de sucesso: **"Conta iFood conectada com sucesso!"**

### 7. Verificar Conexão

Após a conexão bem-sucedida, você verá:
- ✅ Uma mensagem verde indicando que está conectado
- O **Merchant ID** do restaurante
- Um badge **"Conectado"**

## 🔄 Como Funciona o Fluxo OAuth

```
1. Cliente cadastra restaurante com suas credenciais do iFood
   ↓
2. Cliente salva as credenciais (Client ID e Client Secret)
   ↓
3. Cliente clica em "Conectar iFood"
   ↓
4. Sistema gera URL de autorização com:
   - Client ID do restaurante (ou fallback global)
   - Redirect URI: {APP_URL}/restaurants/{id}/ifood/callback
   - Scope: MERCHANT_ORDERS
   - State: ID do restaurante
   ↓
5. Redirecionamento para portal.ifood.com.br/oauth/authorize
   ↓
6. Cliente autoriza no portal do iFood
   ↓
7. iFood redireciona para: /restaurants/{id}/ifood/callback?code=XXX
   ↓
8. Sistema identifica o restaurante pelo ID na URL
   ↓
9. Sistema usa as credenciais do restaurante para trocar código por tokens
   ↓
10. Sistema busca informações do merchant (Merchant ID)
   ↓
11. Salva tokens e Merchant ID no banco de dados
   ↓
12. Redireciona para página de edição com mensagem de sucesso
```

## ✅ Verificar Status da Conexão

### Na página de edição:
- **Conectado:** Card verde com Merchant ID visível
- **Credenciais configuradas, mas não conectado:** Card azul com botão para conectar
- **Não configurado:** Card amarelo pedindo para configurar credenciais

### Na página de detalhes:
- Seção "Integração iFood" mostra o status atual
- Link para editar se não estiver conectado

### Na página de listagem:
- Badge indicando status de conexão
- Ícone visual de integração

## 🔐 Segurança

- **Credenciais por restaurante:** Cada restaurante tem suas próprias credenciais
- **Client Secret criptografado:** O Client Secret é armazenado de forma segura
- **Tokens isolados:** Cada restaurante tem seus próprios tokens de acesso
- **Renovação automática:** O sistema renova tokens automaticamente quando expiram
- **State no OAuth:** Previne ataques CSRF

## 🔧 Solução de Problemas

### Erro: "iFood credentials not configured for this restaurant"

**Causa:** Restaurante não tem credenciais configuradas e não há fallback global

**Solução:**
1. Edite o restaurante
2. Preencha os campos `Client ID` e `Client Secret`
3. Salve as alterações
4. Tente conectar novamente

### Erro: "Código de autorização não encontrado"

**Causa:** O callback do iFood não recebeu o código

**Solução:**
1. Verifique se o redirect URI está registrado no portal do desenvolvedor iFood
2. Use wildcard: `https://seu-dominio.com/restaurants/*/ifood/callback`
3. Ou registre múltiplos redirect URIs para cada restaurante
4. Tente conectar novamente

### Erro: "Erro ao conectar conta iFood"

**Causa:** Falha na troca do código por token

**Solução:**
1. Verifique os logs em `storage/logs/laravel.log`
2. Confirme que o `Client ID` e `Client Secret` estão corretos
3. Verifique se a aplicação está ativa no portal do iFood
4. Verifique se o redirect URI está exatamente como registrado
5. Tente desconectar e conectar novamente

### A conexão não aparece como ativa

**Causa:** Token expirado ou inválido

**Solução:**
1. O sistema tenta renovar tokens automaticamente
2. Se persistir, edite o restaurante e clique em "Conectar iFood" novamente
3. Verifique se o restaurante tem `ifood_merchant_id` no banco

## 📝 Notas Importantes

1. **Um restaurante = Uma conexão:** Cada restaurante precisa ser conectado individualmente
2. **Credenciais próprias:** Cada cliente pode usar suas próprias credenciais do iFood
3. **Tokens expiram:** O sistema renova automaticamente, mas pode ser necessário reconectar se houver problemas
4. **Webhooks:** Após conectar, configure os webhooks no portal do iFood para receber notificações de pedidos
5. **Redirect URI dinâmico:** Não precisa configurar redirect URI no `.env`, ele é gerado automaticamente

## 🆘 Precisa de Ajuda?

Se encontrar problemas:
1. Verifique os logs: `storage/logs/laravel.log`
2. Confirme que as credenciais do restaurante estão corretas
3. Verifique se o redirect URI está registrado no portal do iFood
4. Teste a conexão em ambiente de desenvolvimento primeiro
5. Consulte a [documentação oficial do iFood](https://developer.ifood.com.br/)
