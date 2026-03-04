<?php $pageTitle = 'Usuarios'; ?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="mb-0">Gestión de Usuarios</h5>
        <a href="/users/create" class="btn btn-primary btn-sm">
            <i class="bi bi-person-plus-fill me-1"></i>Nuevo Usuario
        </a>
    </div>
    <div class="card-crm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Email</th>
                        <th>Rol</th>
                        <th>Estado</th>
                        <th>Creado</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $u): ?>
                    <tr class="<?=!$u['is_active'] ? 'text-muted' : ''?>">
                        <td class="fw-semibold">
                            <?=\App\Core\View::e($u['name'])?>
                        </td>
                        <td class="small">
                            <?=\App\Core\View::e($u['email'])?>
                        </td>
                        <td>
                            <?php if ($u['role'] === 'admin'): ?>
                            <span class="badge bg-danger">Admin</span>
                            <?php
    else: ?>
                            <span class="badge bg-info text-dark">Vendedor</span>
                            <?php
    endif; ?>
                        </td>
                        <td>
                            <?php if ($u['is_active']): ?>
                            <span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>Activo</span>
                            <?php
    else: ?>
                            <span class="badge bg-secondary"><i class="bi bi-x-circle me-1"></i>Inactivo</span>
                            <?php
    endif; ?>
                        </td>
                        <td class="small text-muted">
                            <?=\App\Helpers\DateHelper::toLocal($u['created_at'])?>
                        </td>
                        <td>
                            <div class="d-flex gap-1">
                                <a href="/users/<?= $u['id']?>/edit" class="btn btn-xs btn-outline-primary">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <?php if ($u['id'] != \App\Core\Auth::id()): ?>
                                <form method="POST" action="/users/<?= $u['id']?>/toggle" class="d-inline">
                                    <?=\App\Core\Csrf::field()?>
                                    <button type="submit"
                                        class="btn btn-xs btn-outline-<?= $u['is_active'] ? 'warning' : 'success'?>"
                                        title="<?= $u['is_active'] ? 'Desactivar' : 'Activar'?>">
                                        <i class="bi bi-<?= $u['is_active'] ? 'pause-circle' : 'play-circle'?>"></i>
                                    </button>
                                </form>
                                <?php
    endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php
endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>