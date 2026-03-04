-- FILE: database/seed.sql
-- Datos iniciales: usuario admin + configuración por defecto
-- IMPORTANTE: Cambiar el email y la contraseña del admin antes de producción

SET NAMES utf8mb4;

-- Usuario admin por defecto
-- Contraseña: Admin1234!
INSERT INTO `users` (`name`, `email`, `password`, `role`, `is_active`) VALUES
('Administrador', 'admin@converclick.cl',
 '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', -- Admin1234!
 'admin', 1);

-- Nota: el hash de arriba es bcrypt de "Admin1234!" con cost=12
-- Si quieres regenerarlo: echo password_hash('Admin1234!', PASSWORD_BCRYPT, ['cost'=>12]);

-- Configuración por defecto de la aplicación
INSERT INTO `settings` (`key`, `value`) VALUES
('app_name',        'Converclick CRM'),
('timezone',        'America/Santiago'),
('date_format',     'd/m/Y H:i'),
('logo_text',       'Converclick'),
('primary_color',   '#E63946'),
('version',         '1.0.0');

-- Integraciones vacías (se configuran desde el panel)
INSERT INTO `integrations` (`type`, `config`, `is_active`) VALUES
('mautic', JSON_OBJECT(
    'base_url',      '',
    'client_id',     '',
    'client_secret', '',
    'form_ids',      JSON_ARRAY(),
    'webhook_secret', ''
), 0),
('uazapi', JSON_OBJECT(
    'base_url',        '',
    'instance_token',  '',
    'admin_token',     '',
    'webhook_secret',  ''
), 0);
