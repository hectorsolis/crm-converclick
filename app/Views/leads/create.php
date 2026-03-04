<?php $pageTitle = 'Nuevo Lead'; ?>
<div class="container-fluid" style="max-width:800px">
    <div class="card-crm">
        <div class="card-crm-header"><i class="bi bi-person-plus-fill me-2"></i>Crear Lead Manual</div>
        <div class="card-crm-body">
            <form method="POST" action="/leads" novalidate>
                <?=\App\Core\Csrf::field()?>
                <?php include __DIR__ . '/_form.php'; ?>
                <div class="d-flex gap-2 mt-4">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-circle me-1"></i>Guardar Lead
                    </button>
                    <a href="/leads" class="btn btn-outline-secondary">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
</div>