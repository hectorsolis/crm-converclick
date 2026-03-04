<?php $pageTitle = 'Leads'; ?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="mb-0">Todos los Leads</h5>
        <div>
            <a href="/leads/export?search=<?=\App\Core\View::e($search)?>&source=<?=\App\Core\View::e($source)?>" class="btn btn-success btn-sm me-2">
                <i class="bi bi-download me-1"></i>Exportar CSV
            </a>
            <a href="/leads/create" class="btn btn-primary btn-sm">
                <i class="bi bi-plus-circle me-1"></i>Nuevo Lead
            </a>
        </div>
    </div>

    <!-- Búsqueda rápida -->
    <form method="GET" action="/leads" class="row g-2 mb-3">
        <div class="col-md-5">
            <input type="text" name="search" class="form-control form-control-sm"
                placeholder="Buscar por nombre, email o teléfono…" value="<?=\App\Core\View::e($search ?? '')?>">
        </div>
        <div class="col-md-3">
            <select name="source" class="form-select form-select-sm">
                <option value="">Todas las fuentes</option>
                <option value="mautic_form" <?=($source ?? '' )==='mautic_form' ? 'selected' : ''?>>Mautic /
                    Formularios</option>
                <option value="whatsapp" <?=($source ?? '' )==='whatsapp' ? 'selected' : ''?>>WhatsApp</option>
                <option value="chatwoot" <?=($source ?? '' )==='chatwoot' ? 'selected' : ''?>>Chatwoot / Social</option>
                <option value="manual" <?=($source ?? '' )==='manual' ? 'selected' : ''?>>Manual</option>
            </select>
        </div>
        <div class="col-md-2">
            <button type="submit" class="btn btn-outline-primary btn-sm w-100">
                <i class="bi bi-search me-1"></i>Buscar
            </button>
        </div>
        <?php if (!empty($search) || !empty($source)): ?>
        <div class="col-md-2">
            <a href="/leads" class="btn btn-outline-secondary btn-sm w-100">
                <i class="bi bi-x-circle me-1"></i>Limpiar
            </a>
        </div>
        <?php
endif; ?>
    </form>

    <div class="card-crm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="leadsTable">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Email</th>
                        <th>Teléfono</th>
                        <th>Empresa</th>
                        <th>Fuente</th>
                        <th>Vendedor</th>
                        <th>Calif.</th>
                        <th>Ingreso</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($leads as $lead): ?>
                    <tr class="<?= $lead['conflict_flag'] ? 'table-warning' : ''?>">
                        <td>
                            <a href="/leads/<?= $lead['id']?>" class="fw-semibold text-decoration-none">
                                <?=\App\Core\View::e($lead['name'])?>
                            </a>
                            <?php if ($lead['conflict_flag']): ?>
                            <i class="bi bi-exclamation-triangle-fill text-warning ms-1"
                                title="<?=\App\Core\View::e($lead['conflict_detail'] ?? 'Posible conflicto de identidad')?>"></i>
                            <?php
    endif; ?>
                        </td>
                        <td class="text-muted small">
                            <?=\App\Core\View::e($lead['email'] ?? '—')?>
                        </td>
                        <td class="small">
                            <?=\App\Core\View::e(\App\Helpers\PhoneNormalizer::format($lead['phone'])) ?: '—'?>
                        </td>
                        <td class="small">
                            <?=\App\Core\View::e($lead['company_name'] ?? '—')?>
                        </td>
                        <td>
                            <?= sourceBadgeLeads($lead['source'])?>
                        </td>
                        <td class="small">
                            <?=\App\Core\View::e($lead['vendedor_name'] ?? '—')?>
                        </td>
                        <td>
                            <?= qualificationDots($lead)?>
                        </td>
                        <td class="text-muted small">
                            <?=\App\Helpers\DateHelper::toLocal($lead['created_at'])?>
                        </td>
                        <td>
                            <a href="/leads/<?= $lead['id']?>" class="btn btn-xs btn-outline-primary">
                                <i class="bi bi-eye"></i>
                            </a>
                        </td>
                    </tr>
                    <?php
endforeach; ?>
                    <?php if (empty($leads)): ?>
                    <tr>
                        <td colspan="9" class="text-center text-muted py-5">
                            <i class="bi bi-search fs-2 d-block mb-2"></i>Sin resultados
                        </td>
                    </tr>
                    <?php
endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <div class="text-muted small mt-2">
        <?= count($leads)?> lead(s) encontrado(s)
    </div>
</div>

<?php
function sourceBadgeLeads(string $source): string
{
    return match ($source) {
            'mautic_form' => '<span class="badge source-badge mautic">Mautic</span>',
            'whatsapp' => '<span class="badge source-badge whatsapp"><i class="bi bi-whatsapp"></i> WA</span>',
            'chatwoot' => '<span class="badge bg-info text-dark"><i class="bi bi-chat-dots"></i> Chatwoot</span>',
            'manual' => '<span class="badge source-badge manual">Manual</span>',
            default => '<span class="badge bg-secondary">' . htmlspecialchars($source) . '</span>',
        };
}
function qualificationDots(array $lead): string
{
    $keys = ['has_budget', 'has_deadline', 'has_active_problem', 'decision_maker'];
    $html = '<div class="d-flex gap-1">';
    foreach ($keys as $k) {
        $filled = $lead[$k] ? 'bg-success' : 'bg-light border';
        $html .= "<span class='qual-dot {$filled}' title='{$k}'></span>";
    }
    $html .= '</div>';
    return $html;
}
?>