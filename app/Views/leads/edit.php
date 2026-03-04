<?php $pageTitle = 'Editar Lead'; ?>
<div class="container-fluid" style="max-width:800px">
    <div class="card-crm">
        <div class="card-crm-header d-flex justify-content-between">
            <span><i class="bi bi-pencil-fill me-2"></i>Editar:
                <?=\App\Core\View::e($lead['name'])?>
            </span>
            <a href="/leads/<?= $lead['id']?>" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Ver Detalle
            </a>
        </div>
        <div class="card-crm-body">
            <form method="POST" action="/leads/<?= $lead['id']?>" novalidate>
                <?=\App\Core\Csrf::field()?>
                <?php include __DIR__ . '/_form.php'; ?>
                <div class="d-flex gap-2 mt-4">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-circle me-1"></i>Guardar Cambios
                    </button>
                    <a href="/leads/<?= $lead['id']?>" class="btn btn-outline-secondary">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
</div>