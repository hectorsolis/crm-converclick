<?php $pageTitle = 'Dashboard'; ?>
<div class="container-fluid">
    <!-- Stats cards -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="stat-card">
                <div class="stat-icon bg-primary-soft"><i class="bi bi-people-fill text-primary"></i></div>
                <div class="stat-value">
                    <?=(int)$totalLeads?>
                </div>
                <div class="stat-label">Total Leads</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card">
                <div class="stat-icon bg-success-soft"><i class="bi bi-person-plus-fill text-success"></i></div>
                <div class="stat-value">
                    <?=(int)$todayLeads?>
                </div>
                <div class="stat-label">Nuevos Hoy</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card border-danger">
                <div class="stat-icon bg-danger-soft"><i class="bi bi-clock-history text-danger"></i></div>
                <div class="stat-value text-danger">
                    <?=(int)$overdueSteps?>
                </div>
                <div class="stat-label">Pasos Vencidos</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card">
                <div class="stat-icon bg-warning-soft"><i class="bi bi-whatsapp text-success"></i></div>
                <div class="stat-value">
                    <?=(int)($sourceMap['whatsapp'] ?? 0)?>
                </div>
                <div class="stat-label">Por WhatsApp</div>
            </div>
        </div>
    </div>

    <!-- Fuentes -->
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card-crm h-100">
                <div class="card-crm-header">
                    <i class="bi bi-bar-chart-fill me-2"></i>Leads por Fuente
                </div>
                <div class="card-crm-body">
                    <?php
$sourceLabels = [
    'mautic_form' => ['Mautic / Formularios', 'bi-ui-checks', 'text-info'],
    'whatsapp' => ['WhatsApp', 'bi-whatsapp', 'text-success'],
    'manual' => ['Entrada Manual', 'bi-pencil-fill', 'text-secondary'],
];
foreach ($sourceLabels as $key => [$label, $icon, $color]): ?>
                    <div class="source-row">
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi <?= $icon?> <?= $color?>"></i>
                            <span>
                                <?= $label?>
                            </span>
                        </div>
                        <span class="badge bg-primary rounded-pill">
                            <?=(int)($sourceMap[$key] ?? 0)?>
                        </span>
                    </div>
                    <?php
endforeach; ?>
                </div>
            </div>
        </div>
        <div class="col-md-8">
            <div class="card-crm h-100">
                <div class="card-crm-header d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-clock-history me-2"></i>Últimos Leads</span>
                    <a href="/leads" class="btn btn-sm btn-outline-primary">Ver todos</a>
                </div>
                <div class="card-crm-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover table-sm mb-0">
                            <thead>
                                <tr>
                                    <th>Nombre</th>
                                    <th>Fuente</th>
                                    <th>Vendedor</th>
                                    <th>Ingreso</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentLeads as $lead): ?>
                                <tr>
                                    <td>
                                        <a href="/leads/<?= $lead['id']?>" class="text-decoration-none fw-semibold">
                                            <?=\App\Core\View::e($lead['name'])?>
                                        </a>
                                        <?php if ($lead['conflict_flag']): ?>
                                        <span class="badge bg-warning ms-1" title="Posible conflicto"><i
                                                class="bi bi-exclamation-triangle"></i></span>
                                        <?php
    endif; ?>
                                    </td>
                                    <td>
                                        <?=\App\Core\View::e(sourceBadge($lead['source']))?>
                                    </td>
                                    <td>
                                        <?=\App\Core\View::e($lead['vendedor_name'] ?? '—')?>
                                    </td>
                                    <td class="text-muted small">
                                        <?=\App\Helpers\DateHelper::toLocal($lead['created_at'])?>
                                    </td>
                                </tr>
                                <?php
endforeach; ?>
                                <?php if (empty($recentLeads)): ?>
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-4">Sin leads aún</td>
                                </tr>
                                <?php
endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Accesos rápidos -->
    <div class="row g-3">
        <div class="col-12">
            <div class="d-flex gap-2 flex-wrap">
                <a href="/leads/create" class="btn btn-primary">
                    <i class="bi bi-plus-circle me-2"></i>Nuevo Lead Manual
                </a>
                <a href="/pipeline?next_step=overdue" class="btn btn-outline-danger">
                    <i class="bi bi-alarm me-2"></i>Ver Pasos Vencidos
                </a>
                <?php if (\App\Core\Auth::isAdmin()): ?>
                <a href="/integrations" class="btn btn-outline-secondary">
                    <i class="bi bi-plug me-2"></i>Integraciones
                </a>
                <?php
endif; ?>
            </div>
        </div>
    </div>
</div>

<?php
function sourceBadge(string $source): string
{
    $badges = [
        'mautic_form' => '<span class="badge source-badge mautic">Mautic</span>',
        'whatsapp' => '<span class="badge source-badge whatsapp">WhatsApp</span>',
        'manual' => '<span class="badge source-badge manual">Manual</span>',
    ];
    return $badges[$source] ?? '<span class="badge bg-secondary">' . htmlspecialchars($source) . '</span>';
}
?>