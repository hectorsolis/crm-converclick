# Changelog

Todas as alterações notáveis neste projeto serão documentadas neste arquivo.

O formato é baseado em [Keep a Changelog](https://keepachangelog.com/pt-BR/1.0.0/),
e este projeto adere ao [Versionamento Semântico](https://semver.org/lang/pt-BR/).

## [1.0.1] - 2026-03-04

### Adicionado
- **Mautic**: Adicionada validação estrita para IDs de formulários. Eventos sem ID de formulário agora são bloqueados se houver restrições de formulário ativas.
- **Mautic**: Adicionados logs detalhados para tentativas de submissão bloqueadas (`form_blocked`, `form_missing`).
- **WhatsApp**: Adicionado método `updateConfig` ao modelo `Integration` para permitir atualizações de status via Dashboard.
- **Docs**: Adicionada documentação `docs/MAUTIC_SETUP.md` detalhando a configuração de segurança da integração Mautic.

### Corrigido
- **Mautic**: Corrigida falha na extração do ID do formulário em payloads aninhados (`submission.form.id`), que permitia bypass da restrição de formulários (Correção para leads não autorizados do Form 86).
- **WhatsApp**: Corrigido erro "HTTP 500" no widget do Dashboard causado por chamada a método inexistente no modelo `Integration`.
- **WhatsApp**: Corrigido problema de "Erro de Rede" genérico no frontend; agora exibe mensagens de erro específicas (JSON inválido, 500, etc.).
- **WhatsApp**: Corrigido conflito de sessão (`session_start`) no `DashboardWhatsAppController`.
- **Core**: Aumentado timeout das requisições cURL para a API do WhatsApp (de 10s para 20s/30s) para evitar timeouts em conexões lentas.

### Segurança
- **Mautic**: Fechada brecha que permitia a entrada de leads de formulários não autorizados quando o payload do webhook tinha uma estrutura inesperada.
