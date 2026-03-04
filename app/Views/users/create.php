<?php $pageTitle = 'Nuevo Usuario'; ?>
<div class="container-fluid" style="max-width:600px">
    <div class="card-crm">
        <div class="card-crm-header"><i class="bi bi-person-plus-fill me-2"></i>Crear Usuario</div>
        <div class="card-crm-body">
            <form method="POST" action="/users" novalidate>
                <?=\App\Core\Csrf::field()?>
                <div class="mb-3">
                    <label class="form-label required">Nombre completo</label>
                    <input type="text" name="name" class="form-control" required placeholder="Nombre Apellido">
                </div>
                <div class="mb-3">
                    <label class="form-label required">Email</label>
                    <input type="email" name="email" class="form-control" required placeholder="usuario@empresa.cl">
                </div>
                <div class="mb-3">
                    <label class="form-label required">Contraseña</label>
                    <input type="password" name="password" class="form-control" required minlength="8"
                        placeholder="Mínimo 8 caracteres">
                </div>
                <div class="mb-4">
                    <label class="form-label">Rol</label>
                    <select name="role" class="form-select">
                        <option value="vendedor">Vendedor</option>
                        <option value="admin">Administrador</option>
                    </select>
                </div>
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle me-1"></i>Crear</button>
                    <a href="/users" class="btn btn-outline-secondary">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
</div>