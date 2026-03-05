<?php $pageTitle = 'Recuperar Contraseña'; ?>
<?php if (!empty($flash)): ?>
<div class="alert alert-<?= $flash['type']?> mb-3">
    <?=\App\Core\View::e($flash['message'])?>
</div>
<?php endif; ?>

<form method="POST" action="/forgot-password/send" class="auth-form" novalidate>
    <?=\App\Core\Csrf::field()?>
    <div class="mb-4">
        <p class="text-muted small">Ingrese su correo electrónico y le enviaremos un enlace para restablecer su contraseña.</p>
        <label for="email" class="form-label">Correo electrónico</label>
        <div class="input-group">
            <span class="input-group-text"><i class="bi bi-envelope"></i></span>
            <input type="email" id="email" name="email" class="form-control" placeholder="tu@email.cl" required
                autocomplete="email" autofocus>
        </div>
    </div>
    <button type="submit" class="btn btn-primary w-100 btn-crm">
        <i class="bi bi-send me-2"></i>Enviar Enlace
    </button>
    <div class="text-center mt-3">
        <a href="/login" class="text-decoration-none small"><i class="bi bi-arrow-left me-1"></i>Volver al inicio de sesión</a>
    </div>
</form>
