<?php $pageTitle = 'Iniciar Sesión'; ?>
<?php if (!empty($flash)): ?>
<div class="alert alert-<?= $flash['type']?> mb-3">
    <?=\App\Core\View::e($flash['message'])?>
</div>
<?php
endif; ?>

<form method="POST" action="/login" class="auth-form" novalidate>
    <?=\App\Core\Csrf::field()?>
    <div class="mb-3">
        <label for="email" class="form-label">Correo electrónico</label>
        <div class="input-group">
            <span class="input-group-text"><i class="bi bi-envelope"></i></span>
            <input type="email" id="email" name="email" class="form-control" placeholder="tu@email.cl" required
                autocomplete="email" autofocus>
        </div>
    </div>
    <div class="mb-4">
        <label for="password" class="form-label">Contraseña</label>
        <div class="input-group">
            <span class="input-group-text"><i class="bi bi-lock"></i></span>
            <input type="password" id="password" name="password" class="form-control" placeholder="••••••••" required
                autocomplete="current-password">
        </div>
    </div>
    <button type="submit" class="btn btn-primary w-100 btn-crm">
        <i class="bi bi-box-arrow-in-right me-2"></i>Ingresar
    </button>
</form>
<p class="text-center text-muted mt-3" style="font-size:.8rem">
    Converclick CRM &copy;
    <?= date('Y')?>
</p>