<?php $pageTitle = 'Restablecer Contraseña'; ?>
<?php if (!empty($flash)): ?>
<div class="alert alert-<?= $flash['type']?> mb-3">
    <?=\App\Core\View::e($flash['message'])?>
</div>
<?php endif; ?>

<form method="POST" action="/reset-password/update" class="auth-form" novalidate>
    <?=\App\Core\Csrf::field()?>
    <input type="hidden" name="token" value="<?=\App\Core\View::e($token)?>">
    
    <div class="mb-3">
        <label for="password" class="form-label">Nueva Contraseña</label>
        <div class="input-group">
            <span class="input-group-text"><i class="bi bi-lock"></i></span>
            <input type="password" id="password" name="password" class="form-control" placeholder="Mínimo 8 caracteres" required>
        </div>
        <div class="form-text small">
            Debe contener al menos 8 caracteres, mayúsculas, minúsculas, números y símbolos.
        </div>
    </div>

    <div class="mb-4">
        <label for="password_confirmation" class="form-label">Confirmar Contraseña</label>
        <div class="input-group">
            <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
            <input type="password" id="password_confirmation" name="password_confirmation" class="form-control" placeholder="Repita la contraseña" required>
        </div>
    </div>

    <button type="submit" class="btn btn-primary w-100 btn-crm">
        <i class="bi bi-check-circle me-2"></i>Restablecer Contraseña
    </button>
</form>
