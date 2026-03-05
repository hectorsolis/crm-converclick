# Configuração de Integração Mautic - CRM Converclick

## Visão Geral
A integração com Mautic permite capturar leads enviados através de Webhooks. Para garantir a segurança e a qualidade dos dados, o sistema permite restringir quais formulários do Mautic podem criar leads no CRM.

## Configuração de IDs de Formulários Permitidos
No painel de administração (`/integrations`), no campo "IDs de Formulários (separados por vírgula)", você deve listar EXPLICITAMENTE os IDs dos formulários autorizados.

- **Exemplo:** `88, 90, 102`
- **Comportamento:**
  - Se o campo estiver **Vazio**: O sistema aceitará leads de QUALQUER origem (formulários, API, importação manual).
  - Se o campo tiver **IDs**: O sistema aceitará APENAS leads vindos dos formulários listados.
    - Leads de outros formulários serão **bloqueados**.
    - Leads criados manualmente ou via API (sem ID de formulário) serão **bloqueados**.

## Diagnóstico de Problemas Comuns

### Lead de formulário não autorizado sendo capturado
**Causa:** O sistema não conseguiu identificar o ID do formulário no payload do webhook, ou o filtro de formulários estava vazio.
**Solução:**
1. Verifique se o ID do formulário está correto na configuração.
2. Verifique os Logs de Integração (`/integrations`). Se o status for `ignored` com razão `form_not_enabled`, o bloqueio está funcionando.
3. Se o lead passou, verifique se a configuração de IDs não está vazia.

### Erro "Evento ignorado: Restrição de formulário ativa mas evento não possui ID"
**Causa:** O Mautic enviou um evento que não é de submissão de formulário (ex: criação manual de lead, atualização de pontuação) e você tem restrição de formulários ativa.
**Solução:** Isso é o comportamento esperado para garantir que apenas leads de formulários entrem. Se você deseja permitir leads manuais, remova a restrição de IDs de formulários.

## Estrutura Esperada do Webhook
O sistema espera um payload JSON contendo `mautic.form_on_submit`.
Campos verificados para extração do ID:
1. `form.id`
2. `mautic.form_on_submit[0].form.id`
3. `mautic.form_on_submit[0].submission.form.id` (Padrão mais comum)

## Logs
Todas as tentativas de integração, bloqueadas ou aceitas, são registradas em `Configurações > Integrações > Logs`.
