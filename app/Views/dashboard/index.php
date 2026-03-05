<?php $pageTitle = 'Dashboard'; ?>
<div class="container-fluid">
    <!-- Stats cards -->
    <div class="row g-3 mb-4">
        <!-- Widget WhatsApp Connection -->
        <div class="col-12 col-md-6 col-lg-4 mb-3 mb-md-0 order-md-last">
             <div class="card h-100 border-0 shadow-sm" id="whatsapp-widget">
                 <div class="card-body p-3">
                     <div class="d-flex justify-content-between align-items-center mb-2">
                         <h6 class="card-title mb-0 fw-bold"><i class="bi bi-whatsapp me-2 text-success"></i>Estado Conexión</h6>
                         <span class="badge rounded-pill bg-secondary" id="wa-status-badge">Cargando...</span>
                     </div>
                     
                     <!-- Estado Conectado -->
                     <div id="wa-connected" class="d-none text-center py-2">
                         <div class="d-flex align-items-center justify-content-center mb-2">
                             <div class="position-relative">
                                 <i class="bi bi-phone fs-1 text-success"></i>
                                 <span class="position-absolute top-0 start-100 translate-middle p-1 bg-success border border-light rounded-circle"></span>
                             </div>
                         </div>
                         <h5 class="mb-0" id="wa-phone">--</h5>
                         <small class="text-success fw-semibold">● En línea</small>
                     </div>

                     <!-- Estado Desconectado / QR -->
                     <div id="wa-disconnected" class="d-none text-center">
                         <div class="alert alert-warning py-1 small mb-2"><i class="bi bi-exclamation-triangle me-1"></i>Desconectado</div>
                         <div id="wa-qr-container" class="my-2" style="min-height: 150px; display: flex; align-items: center; justify-content: center;">
                             <div class="spinner-border text-primary" role="status" id="wa-qr-loading">
                                 <span class="visually-hidden">Generando QR...</span>
                             </div>
                             <img id="wa-qr-img" src="" alt="Escanee el código QR" class="img-fluid d-none" style="max-height: 160px; border: 2px solid #eee; padding: 4px; border-radius: 8px;">
                         </div>
                         <p class="small text-muted mb-0">Escanee con WhatsApp en su teléfono<br>(Dispositivos vinculados > Vincular dispositivo)</p>
                     </div>

                     <!-- Estado Conectando -->
                     <div id="wa-connecting" class="d-none text-center py-4">
                         <div class="progress mb-2" style="height: 10px;">
                             <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 100%"></div>
                         </div>
                         <small class="text-muted">Sincronizando...</small>
                     </div>
                     
                 </div>
             </div>
        </div>

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
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?= sourceBadge($lead['source']) ?>
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

<!-- Widget JS -->
<?php require __DIR__ . '/widget_js.php'; ?>