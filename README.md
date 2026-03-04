# Converclick CRM — Documentación

Sistema mini-CRM para leads de Google Ads con integraciones Mautic, WhatsApp (uazapiGO) y Chatwoot.

---

## 🏗️ Arquitectura Resumida

```
PHP 8+ · MySQL · Bootstrap 5 · Sin frameworks pesados
micro-MVC propio con Router, Views, Auth, CSRF
DocumentRoot → /public
```

---

## 📁 Estructura de Carpetas

```
crm-converclick/
├── public/             # DocumentRoot Apache
│   ├── index.php       # Front controller
│   ├── .htaccess       # Rewrite rules + headers
│   └── assets/css/js/
├── app/
│   ├── Core/           # Router, Database, Auth, Csrf, View, Controller
│   ├── Controllers/    # Auth, Dashboard, Lead, Pipeline, User, Settings, Integration, Webhooks
│   ├── Models/         # User, Lead, LeadActivity, Setting, Integration, IntegrationLog
│   ├── Views/          # layouts/, auth/, dashboard/, leads/, pipeline/, ...
│   ├── Middleware/     # AuthMiddleware, AdminMiddleware
│   ├── Helpers/        # LeadDeduplicator, PhoneNormalizer, DateHelper
│   └── Services/       # MauticService, UazapiService, ChatwootService
├── config/             # app.php, database.php, session.php
├── database/           # schema.sql, seed.sql
├── logs/               # (gitignored)
├── .env.example
├── .htaccess           # Redirect raíz → /public
└── README.md
```

---

## 🚀 Instalación en cPanel (Paso a Paso)

### Requisitos previos
- PHP 8.0+ con extensiones: `pdo_mysql`, `mbstring`, `json`, `curl`
- MySQL 5.7+ o MariaDB 10.3+
- mod_rewrite habilitado en Apache

### Pasos

**1. Subir archivos al servidor**

Opción A — Git:
```bash
# En el directorio raíz de tu hosting (ej: /home/usuario/)
git clone https://github.com/TU_USUARIO/crm-converclick.git crm
```

Opción B — Administrador de archivos cPanel:
- Comprime el repo en `.zip`
- Sube y extrae en `/home/usuario/crm/`

**2. Configurar DocumentRoot**

En cPanel → Dominios → Editar dominio → DocumentRoot:
```
/home/usuario/crm/public
```

Si no puedes cambiar DocumentRoot, el `.htaccess` raíz ya redirige automáticamente.

**3. Crear la base de datos**

En cPanel → Bases de Datos MySQL:
1. Crear base de datos: `miusuario_crm`
2. Crear usuario: `miusuario_crmuser`
3. Asignar todos los privilegios

**4. Importar el SQL**

En phpMyAdmin:
```
Importar → database/schema.sql → Ejecutar
Importar → database/seed.sql   → Ejecutar
```

**5. Configurar variables de entorno**

```bash
cp .env.example .env
```

Editar `.env`:
```ini
APP_URL="https://tudominio.com"
APP_KEY="genera_una_cadena_aleatoria_de_32_chars"
TIMEZONE="America/Santiago"

DB_HOST="localhost"
DB_NAME="miusuario_crm"
DB_USER="miusuario_crmuser"
DB_PASS="tu_password_segura"

MAUTIC_WEBHOOK_SECRET="token_secreto_mautic"
UAZAPI_WEBHOOK_SECRET="token_secreto_uazapi"
```

**6. Permisos de carpetas**

```bash
chmod 755 /home/usuario/crm/
chmod 755 /home/usuario/crm/public/
chmod 775 /home/usuario/crm/logs/
```

**7. Primer acceso**

```
URL:        https://tudominio.com
Email:      admin@converclick.cl
Contraseña: Admin1234!
```

> ⚠️ **Cambia la contraseña del admin inmediatamente** en Usuarios → Editar.

---

## ⚙️ Configuración Mautic

### En el CRM (Integraciones → Mautic)

| Campo | Descripción |
|-------|-------------|
| URL base Mautic | `https://mautic.tudominio.cl` |
| Client ID / Secret | Credenciales de API OAuth de Mautic |
| IDs de formularios | Ej: `1, 3, 7` (solo estos crearán leads) |
| Secret webhook | Token para validar llamadas entrantes |

### En Mautic (Configuración → Webhooks)

1. Crear nuevo webhook
2. URL del webhook:
   ```
   https://tudominio.com/integrations/mautic/webhook?secret=TOKEN_SECRETO
   ```
3. Eventos seleccionados: **Form Submit** (mautic.form_on_submit)
4. Guardar

### Cómo Mautic envía los datos (Modo A)

El CRM lee el contacto completo del payload. Campos mapeados:
- `firstname` + `lastname` → nombre
- `email` → email
- `phone` / `mobile` → teléfono
- `company` → empresa

Si el payload no incluye datos del contacto (solo ID), el CRM consulta automáticamente la API de Mautic (Modo B).

---

## 📱 Configuración uazapiGO V2

### En el CRM (Integraciones → uazapiGO / WhatsApp)

| Campo | Descripción |
|-------|-------------|
| URL base uazapi | `https://miinstancia.uazapi.com` |
| Instance Token | Token de instancia (header `token`) |
| Admin Token | Opcional (header `admintoken`) |
| Secret webhook CRM | Cadena aleatoria para proteger el endpoint |

### Endpoint del CRM para uazapi

```
POST https://tudominio.com/integrations/uazapi/webhook?secret=TU_SECRET
```

### Registro automático desde el panel

1. Ir a Integraciones → uazapiGO
2. Guardar configuración
3. Hacer click en **"Registrar Webhook en uazapiGO"**

Esto enviará a uazapi:
```json
{
  "url": "https://tudominio.com/integrations/uazapi/webhook",
  "events": ["messages", "connection"],
  "excludeMessages": ["wasSentByApi"]
}
```

> ⚠️ **IMPORTANTE**: `excludeMessages: ["wasSentByApi"]` evita que los mensajes enviados por la API generen bucles infinitos.

### Registro manual (alternativa curl)

```bash
curl -X POST https://miinstancia.uazapi.com/webhook \
  -H "token: TU_INSTANCE_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"url":"https://tudominio.com/integrations/uazapi/webhook?secret=TU_SECRET","events":["messages","connection"],"excludeMessages":["wasSentByApi"]}'
```

---

## � Configuración Chatwoot

### En el CRM (Integraciones → Chatwoot)

| Campo | Descripción |
|-------|-------------|
| URL base Chatwoot | `https://app.chatwoot.com` |
| API Access Token | Token de usuario (Perfil -> Access Token) |
| Account ID | ID de la cuenta |
| Inbox Token | Identificador del Inbox |
| Secret webhook | Token para validar llamadas entrantes |

### En Chatwoot (Ajustes → Integraciones → Webhooks)

1. Agregar nuevo webhook
2. URL: `https://tudominio.com/integrations/chatwoot/webhook?secret=TOKEN_SECRETO`
3. Eventos: `conversation_created`, `message_created`, etc.

---

## �🔌 Endpoints Completos

### UI (requieren sesión)

| Método | URL | Descripción |
|--------|-----|-------------|
| GET | `/login` | Formulario de login |
| POST | `/login` | Autenticar |
| GET | `/logout` | Cerrar sesión |
| GET | `/dashboard` | Dashboard |
| GET | `/leads` | Lista de leads |
| GET | `/leads/create` | Formulario nuevo lead |
| POST | `/leads` | Crear lead |
| GET | `/leads/export` | Exportar leads a CSV |
| GET | `/leads/:id` | Detalle del lead |
| GET | `/leads/:id/edit` | Editar lead |
| POST | `/leads/:id` | Actualizar lead |
| POST | `/leads/:id/delete` | Eliminar (solo admin) |
| GET | `/pipeline` | Vista pipeline con filtros |
| GET | `/integrations` | Configuración integraciones (admin) |
| POST | `/integrations/mautic/save` | Guardar config Mautic |
| POST | `/integrations/uazapi/save` | Guardar config uazapi |
| POST | `/integrations/chatwoot/save` | Guardar config Chatwoot |
| POST | `/integrations/uazapi/register-webhook` | Registrar webhook |
| GET | `/integrations/uazapi/test` | Probar conexión (JSON) |
| GET | `/users` | Lista usuarios (admin) |
| GET | `/users/create` | Nuevo usuario (admin) |
| POST | `/users` | Crear usuario |
| GET | `/users/:id/edit` | Editar usuario |
| POST | `/users/:id` | Actualizar usuario |
| POST | `/users/:id/toggle` | Activar/desactivar |
| GET | `/settings` | Ajustes generales (admin) |
| POST | `/settings/save` | Guardar ajustes |

### Webhooks / APIs de integración (públicos con secret)

| Método | URL | Descripción |
|--------|-----|-------------|
| POST | `/integrations/mautic/webhook` | Recibir leads de Mautic |
| POST | `/integrations/uazapi/webhook` | Recibir mensajes WhatsApp |
| POST | `/integrations/chatwoot/webhook` | Recibir eventos Chatwoot |

---

## 🧪 Pruebas de Webhooks (curl)

### Probar webhook Mautic
```bash
curl -X POST "https://tudominio.com/integrations/mautic/webhook?secret=TOKEN_SECRETO" \
  -H "Content-Type: application/json" \
  -d '{
    "mautic.form_on_submit": [{
      "form": {"id": 1},
      "contact": {
        "id": 123,
        "fields": {
          "all": {
            "firstname": "María",
            "lastname": "González",
            "email": "maria@empresa.cl",
            "phone": "+56912345678",
            "company": "Empresa S.A."
          }
        }
      }
    }]
  }'
```

### Probar webhook uazapiGO
```bash
curl -X POST "https://tudominio.com/integrations/uazapi/webhook?secret=TOKEN_SECRETO" \
  -H "Content-Type: application/json" \
  -d '{
    "event": "messages",
    "pushName": "Juan Pérez",
    "key": {
      "remoteJid": "56987654321@s.whatsapp.net",
      "fromMe": false
    }
  }'
```

---

## 🔐 Seguridad

- Contraseñas con bcrypt cost=12
- Tokens CSRF en todos los formularios
- Sanitización XSS con `htmlspecialchars`
- Webhooks protegidos por secret (query param o header)
- Sesiones seguras (httponly, samesite=Lax, strict mode)
- Cabeceras de seguridad HTTP vía `.htaccess`

---

## 🔄 Lógica de Deduplicación

1. Si llega **email** que ya existe → actualizar ese lead
2. Else si llega **teléfono** que ya existe → actualizar ese lead
3. Si **email** apunta a lead A y **teléfono** a lead B → **Conflicto**: ambos se marcan con `conflict_flag=1` y aparece alerta en UI
4. Si no existe ninguno → crear lead nuevo

Los campos faltantes (ej: llega teléfono y el lead ya tenía email pero no teléfono) se completan automáticamente.

---

## 👥 Roles

| Rol | Permisos |
|-----|---------|
| **Admin** | Todo: leads, pipeline, usuarios, integraciones, ajustes |
| **Vendedor** | Solo sus leads asignados: ver, editar, actualizar |

---

## 🐛 Debug

Los logs de integración se guardan en la tabla `integration_logs` y son visibles en:

**Integraciones → Logs**

También puedes consultar directamente:
```sql
SELECT * FROM integration_logs ORDER BY created_at DESC LIMIT 50;
```
