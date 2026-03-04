<?php $pageTitle = 'Detalle del Lead'; ?>
<div class="container-fluid">

    <?php if ($lead['conflict_flag']): ?>
    <div class="alert alert-warning d-flex align-items-center gap-2" role="alert">
        <i class="bi bi-exclamation-triangle-fill fs-5"></i>
        <div><strong>Posible conflicto de identidad:</strong>
            <?=\App\Core\View::e($lead['conflict_detail'] ?? '')?>
        </div>
    </div>
    <?php
endif; ?>

    <div class="row g-3">
        <!-- Columna principal -->
        <div class="col-lg-8">
            <div class="card-crm mb-3">
                <div class="card-crm-header d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-person-fill me-2"></i>
                        <?=\App\Core\View::e($lead['name'])?>
                    </span>
                    <a href="/leads/<?= $lead['id']?>/edit" class="btn btn-sm btn-outline-primary">
                        <i class="bi bi-pencil me-1"></i>Editar
                    </a>
                </div>
                <div class="card-crm-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="detail-label">Email</label>
                            <div>
                                <?=\App\Core\View::e($lead['email'] ?? '—')?>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="detail-label">Teléfono</label>
                            <div>
                                <?=\App\Core\View::e(\App\Helpers\PhoneNormalizer::format($lead['phone'])) ?: '—'?>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="detail-label">Empresa</label>
                            <div>
                                <?=\App\Core\View::e($lead['company_name'] ?? '—')?>
                                <?php if ($lead['company_industry']): ?>
                                <span class="text-muted small"> ·
                                    <?=\App\Core\View::e($lead['company_industry'])?>
                                </span>
                                <?php
endif; ?>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <label class="detail-label">Fuente</label>
                            <div>
                                <?= match ($lead['source']) {
        'mautic_form' => '<span class="badge source-badge mautic">Mautic</span>',
        'whatsapp' => '<span class="badge source-badge whatsapp">WhatsApp</span>',
        'manual' => '<span class="badge source-badge manual">Manual</span>',
        default => '<span class="badge bg-secondary">' . htmlspecialchars($lead['source']) . '</span>',
    }?>
                                <?php if ($lead['source_detail']): ?>
                                <div class="text-muted small">
                                    <?=\App\Core\View::e($lead['source_detail'])?>
                                </div>
                                <?php
endif; ?>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <label class="detail-label">Vendedor</label>
                            <div>
                                <?=\App\Core\View::e($lead['vendedor_name'] ?? 'Sin asignar')?>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="detail-label">Fecha de Ingreso</label>
                            <div>
                                <?=\App\Helpers\DateHelper::toLocal($lead['created_at'])?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Calificación -->
            <div class="card-crm mb-3">
                <div class="card-crm-header"><i class="bi bi-star-fill me-2 text-warning"></i>Calificación BANT</div>
                <div class="card-crm-body">
                    <div class="row g-2">
                        <?php
$qualFields = [
    'has_budget' => ['Presupuesto', 'bi-cash-stack'],
    'has_deadline' => ['Plazo definido', 'bi-calendar-check'],
    'has_active_problem' => ['Problema activo', 'bi-exclamation-circle'],
    'decision_maker' => ['Es decisor', 'bi-person-badge'],
];
foreach ($qualFields as $key => [$label, $icon]): ?>
                        <div class="col-6 col-md-3">
                            <div class="qual-card <?= $lead[$key] ? 'qual-yes' : 'qual-no'?>">
                                <i class="bi <?= $icon?> fs-4"></i>
                                <div class="small fw-semibold mt-1">
                                    <?= $label?>
                                </div>
                                <div>
                                    <?= $lead[$key] ? '<i class="bi bi-check-circle-fill text-success"></i>' : '<i class="bi bi-x-circle text-muted"></i>'?>
                                </div>
                            </div>
                        </div>
                        <?php
endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Gestión comercial -->
            <div class="card-crm mb-3">
                <div class="card-crm-header"><i class="bi bi-journal-text me-2"></i>Gestión Comercial</div>
                <div class="card-crm-body">
                    <div class="mb-3">
                        <label class="detail-label">Contexto / Notas</label>
                        <div class="notes-box">
                            <?= nl2br(\App\Core\View::e($lead['context_notes'] ?? '')) ?: '<span class="text-muted">Sin notas</span>'?>
                        </div>
                    </div>
                    <div class="row g-2">
                        <div class="col-md-8">
                            <label class="detail-label">Próximo Paso</label>
                            <div>
                                <?=\App\Core\View::e($lead['next_step'] ?? '—')?>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="detail-label">Fecha Próximo Paso</label>
                            <?php if ($lead['next_step_date']): ?>
                            <?php $overdue = \App\Helpers\DateHelper::isOverdue($lead['next_step_date']); ?>
                            <div class="<?= $overdue ? 'text-danger fw-semibold' : ''?>">
                                <?=\App\Helpers\DateHelper::toLocal($lead['next_step_date'])?>
                                <?php if ($overdue): ?><i class="bi bi-alarm-fill ms-1"></i>
                                <?php
    endif; ?>
                            </div>
                            <div class="text-muted small">
                                <?=\App\Helpers\DateHelper::relative($lead['next_step_date'])?>
                            </div>
                            <?php
else: ?>
                            <div class="text-muted">—</div>
                            <?php
endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Columna lateral: Timeline -->
        <div class="col-lg-4">
            <div class="card-crm h-100">
                <div class="card-crm-header"><i class="bi bi-activity me-2"></i>Historial de Actividades</div>
                <div class="card-crm-body p-0">
                    <div class="timeline">
                        <?php foreach ($activities as $act): ?>
                        <div class="timeline-item">
                            <div class="timeline-dot <?= timelineDotClass($act['type'])?>"></div>
                            <div class="timeline-content">
                                <div class="fw-semibold small">
                                    <?= timelineLabel($act['type'])?>
                                </div>
                                <div class="text-muted small">
                                    <?=\App\Core\View::e($act['description'])?>
                                </div>
                                <div class="text-muted" style="font-size:.72rem">
                                    <?=\App\Helpers\DateHelper::toLocal($act['created_at'])?>
                                    <?php if ($act['user_name']): ?>·
                                    <?=\App\Core\View::e($act['user_name'])?>
                                    <?php
    endif; ?>
                                </div>
                            </div>
                        </div>
                        <?php
endforeach; ?>
                        <?php if (empty($activities)): ?>
                        <div class="p-3 text-muted text-center small">Sin actividades registradas</div>
                        <?php
endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Acciones -->
    <div class="mt-3 d-flex gap-2 flex-wrap">
        <a href="/leads/<?= $lead['id']?>/edit" class="btn btn-primary">
            <i class="bi bi-pencil me-1"></i>Editar Lead
        </a>
        <a href="/leads" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>Volver a Leads
        </a>
        <?php if (\App\Core\Auth::isAdmin()): ?>
        <form method="POST" action="/leads/<?= $lead['id']?>/delete" class="d-inline"
            onsubmit="return confirm('¿Seguro que deseas eliminar este lead? Esta acción no se puede deshacer.')">
            <?=\App\Core\Csrf::field()?>
            <button type="submit" class="btn btn-outline-danger">
                <i class="bi bi-trash me-1"></i>Eliminar
            </button>
        </form>
        <?php
endif; ?>
    </div>
</div>

<?php
function timelineDotClass(string $type): string
{
    return match ($type) {
            'created' => 'dot-success',
            'updated' => 'dot-primary',
            'source_added' => 'dot-info',
            'conflict_flagged' => 'dot-warning',
            'mautic_received' => 'dot-info',
            'whatsapp_received' => 'dot-success',
            default => 'dot-secondary',
        };
}
function timelineLabel(string $type): string
{
    return match ($type) {
            'created' => 'Lead creado',
            'updated' => 'Datos actualizados',
            'source_added' => 'Nueva fuente / re-ingreso',
            'conflict_flagged' => 'Conflicto detectado',
            'assigned' => 'Vendedor asignado',
            default => ucfirst(str_replace('_', ' ', $type)),
        };
}
?>