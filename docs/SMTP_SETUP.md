# Configuración de Correo y DNS

Este sistema utiliza la función `mail()` de PHP para el envío de correos electrónicos. Para garantizar una alta tasa de entrega y evitar la carpeta de SPAM, es crucial configurar correctamente los registros DNS del dominio `converclick.net`.

## 1. Registros DNS (Estado Actual)

Los registros actuales parecen estar configurados correctamente para el servidor actual:

*   **SPF**: `v=spf1 ip4:162.240.176.62 ip4:67.212.191.164 +a +mx ~all`
    *   Este registro autoriza a la IP del servidor (`162.240.176.62`) a enviar correos en nombre del dominio.
*   **DKIM**: Existe un registro `default._domainkey`.
    *   Asegúrese de que el servidor cPanel esté firmando los correos salientes con esta clave. Esto suele ser automático en cPanel.
*   **DMARC**: `v=DMARC1; p=none; rua=mailto:dmarc_agg@vali.email;`
    *   La política está en `none` (monitorización). Se recomienda cambiar a `p=quarantine` o `p=reject` una vez confirmado que el flujo de correo es legítimo.

## 2. Configuración SMTP (Si se desea usar SMTP externo)

Actualmente el sistema usa el transporte local. Si desea cambiar a un SMTP externo (ej: Amazon SES, SendGrid, Gmail), debe editar `app/Services/Mailer.php` o instalar una librería como PHPMailer.

## 3. Cola de Correos (Queue)

El sistema incluye una infraestructura básica de colas para envío asíncrono.

1.  **Tabla**: `jobs` en la base de datos.
2.  **Worker**: Script en `bin/worker.php`.

Para activar el procesamiento en segundo plano:
1.  Configure un cron job en cPanel para ejecutar el worker cada minuto (o use un gestor de procesos como Supervisor si tiene acceso root).
    ```bash
    php /home/converclicknet/crm.converclick.net/bin/worker.php
    ```
2.  Modifique `app/Services/Mailer.php` para enviar los trabajos a la cola en lugar de enviarlos directamente.

## 4. Pruebas

Puede probar el flujo completo de recuperación de contraseña accediendo a:
`https://crm.converclick.net/forgot-password`
