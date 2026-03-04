<?php $pageTitle = 'Editar Usuario'; ?>
<div class="container-fluid" style="max-width:600px">
    <div class="card-crm">
        <div class="card-crm-header"><i class="bi bi-person-gear me-2"></i>Editar: <?= \App\Core\View::e($editUser['name']) ?></div>
        <div class="card-crm-body">
            <form method="POST" action="/users/<?= $editUser['id'] ?>" novalidate>
                <?= \App\Core\Csrf::field() ?>
                <div class="mb-3">
                    <label class="form-label required">Nombre completo</label>
                    <input type="text" name="name" class="form-control" required
                           value="<?= \App\Core\View::e($editUser['name']) ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label required">Email</label>
                    <input type="email" name="email" class="form-control" required
                           value="<?= \App\Core\View::e($editUser['email']) ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label">Nueva contraseña <small class="text-muted">(dejar en blanco para no cambiar)</small></label>
                    <input type="password" name="password" class="form-control"
                           minlength="8" placeholder="Mínimo 8 caracteres">
                </div>
                <div class="mb-4">
                    <label class="form-label">Rol</label>
                    <select name="role" class="form-select">
                        <option value="vendedor" <?= $editUser['role'] === 'vendedor' ? 'selected' : '' ?>>Vendedor</option>
                        <option value="admin"    <?= $editUser['role'] === 'admin'    ? 'selected' : '' ?>>Administrador</option>
                    </select>
                </div>
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle me-1"></i>Guardar</button>
                    <a href="/users" class="btn btn-outline-secondary">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
</div>
