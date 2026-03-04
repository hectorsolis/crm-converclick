<?php $pageTitle = 'Pipeline de Leads'; ?>
<div class="container-fluid">
    <!-- Filtros -->
    <form method="GET" action="/pipeline" class="card-crm mb-3">
        <div class="card-crm-header"><i class="bi bi-funnel-fill me-2"></i>Filtros</div>
        <div class="card-crm-body">
            <div class="row g-2">
                <?php if (\App\Core\Auth::isAdmin()): ?>
                <div class="col-md-3">
                    <label class="form-label small">Vendedor</label>
                    <select name="assigned_to" class="form-select form-select-sm">
                        <option value="">Todos</option>
                        <?php foreach ($vendedores as $v): ?>
                        <option value="<?= $v['id']?>" <?=($filters['assigned_to'] ?? '' )==$v['id'] ? 'selected' : ''?>>
                            <?=\App\Core\View::e($v['name'])?>
                        </option>
                        <?php
    endforeach; ?>
                    </select>
                </div>
                <?php
endif; ?>
                <div class="col-md-2">
                    <label class="form-label small">Fuente</label>
                    <select name="source" class="form-select form-select-sm">
                        <option value="">Todas</option>
                        <option value="mautic_form" <?=($filters['source'] ?? '' )==='mautic_form' ? 'selected' : ''?>
                            >Mautic</option>
                        <option value="whatsapp" <?=($filters['source'] ?? '' )==='whatsapp' ? 'selected' : ''?>
                            >WhatsApp</option>
                        <option value="manual" <?=($filters['source'] ?? '' )==='manual' ? 'selected' : ''?>>Manual
                        </option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small">Ingreso desde</label>
                    <input type="date" name="date_from" class="form-control form-control-sm"
                        value="<?=\App\Core\View::e($filters['date_from'] ?? '')?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label small">Ingreso hasta</label>
                    <input type="date" name="date_to" class="form-control form-control-sm"
                        value="<?=\App\Core\View::e($filters['date_to'] ?? '')?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label small">Próximo Paso</label>
                    <select name="next_step" class="form-select form-select-sm">
                        <option value="">Todos</option>
                        <option value="overdue" <?=($filters['next_step'] ?? '' )==='overdue' ? 'selected' : ''?>>⛔
                            Vencido</option>
                        <option value="today" <?=($filters['next_step'] ?? '' )==='today' ? 'selected' : ''?>>📅 Hoy
                        </option>
                        <option value="next7" <?=($filters['next_step'] ?? '' )==='next7' ? 'selected' : ''?>>📆
                            Próximos 7 días</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small">Calificación</label>
                    <select name="qualification" class="form-select form-select-sm">
                        <option value="">Todas</option>
                        <option value="complete" <?=($filters['qualification'] ?? '' )==='complete' ? 'selected' : ''?>
                            >✅ Completa</option>
                        <option value="incomplete" <?=($filters['qualification'] ?? '' )==='incomplete' ? 'selected'
                            : ''?>>⬜ Incompleta</option>
                    </select>
                </div>
            </div>
            <div class="d-flex gap-2 mt-2">
                <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-funnel me-1"></i>Filtrar</button>
                <a href="/pipeline" class="btn btn-outline-secondary btn-sm"><i
                        class="bi bi-x-circle me-1"></i>Limpiar</a>
            </div>
        </div>
    </form>

    <!-- Tabla -->
    <div class="card-crm">
        <div class="card-crm-header d-flex justify-content-between">
            <span><i class="bi bi-kanban-fill me-2"></i>Pipeline (
                <?= count($leads)?> resultados)
            </span>
            <a href="/leads/create" class="btn btn-sm btn-primary">
                <i class="bi bi-plus-circle me-1"></i>Nuevo Lead
            </a>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle table-sm mb-0" id="pipelineTable">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Empresa</th>
                        <th>Teléfono</th>
                        <th>Email</th>
                        <th>Fuente</th>
                        <th>Vendedor</th>
                        <th>BANT</th>
                        <th>Próximo Paso</th>
                        <th>Vence</th>
                        <th>Ingreso</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($leads as $lead): ?>
                    <?php
    $overdue = $lead['next_step_date'] && \App\Helpers\DateHelper::isOverdue($lead['next_step_date']);
?>
                    <tr
                        class="<?= $overdue ? 'row-overdue' : ''?> <?= $lead['conflict_flag'] ? 'row-conflict' : ''?>">
                        <td>
                            <a href="/leads/<?= $lead['id']?>" class="fw-semibold text-decoration-none">
                                <?=\App\Core\View::e($lead['name'])?>
                            </a>
                            <?php if ($lead['conflict_flag']): ?>
                            <i class="bi bi-exclamation-triangle-fill text-warning ms-1"></i>
                            <?php
    endif; ?>
                        </td>
                        <td class="small">
                            <?=\App\Core\View::e($lead['company_name'] ?? '—')?>
                        </td>
                        <td class="small">
                            <?=\App\Core\View::e(\App\Helpers\PhoneNormalizer::format($lead['phone'])) ?: '—'?>
                        </td>
                        <td class="small text-muted">
                            <?=\App\Core\View::e($lead['email'] ?? '—')?>
                        </td>
                        <td>
                            <?= match ($lead['source']) {
            'mautic_form' => '<span class="badge source-badge mautic">Mautic</span>',
            'whatsapp' => '<span class="badge source-badge whatsapp"><i class="bi bi-whatsapp"></i></span>',
            'manual' => '<span class="badge source-badge manual">Manual</span>',
            default => '<span class="badge bg-secondary">' . htmlspecialchars($lead['source']) . '</span>',
        }?>
                        </td>
                        <td class="small">
                            <?=\App\Core\View::e($lead['vendedor_name'] ?? '—')?>
                        </td>
                        <td>
                            <div class="d-flex gap-1">
                                <?php foreach (['has_budget', 'has_deadline', 'has_active_problem', 'decision_maker'] as $q): ?>
                                <span class="qual-dot <?= $lead[$q] ? 'bg-success' : 'bg-light border'?>"></span>
                                <?php
    endforeach; ?>
                            </div>
                        </td>
                        <td class="small">
                            <?=\App\Core\View::e($lead['next_step'] ?? '—')?>
                        </td>
                        <td class="small <?= $overdue ? 'text-danger fw-bold' : ''?>">
                            <?= $lead['next_step_date'] ?\App\Helpers\DateHelper::toLocal($lead['next_step_date']) : '—'?>
                            <?php if ($overdue): ?><i class="bi bi-alarm-fill"></i>
                            <?php
    endif; ?>
                        </td>
                        <td class="small text-muted">
                            <?=\App\Helpers\DateHelper::toLocal($lead['created_at'])?>
                        </td>
                    </tr>
                    <?php
endforeach; ?>
                    <?php if (empty($leads)): ?>
                    <tr>
                        <td colspan="10" class="text-center text-muted py-5">
                            <i class="bi bi-inbox fs-2 d-block mb-2"></i>Sin leads con los filtros aplicados
                        </td>
                    </tr>
                    <?php
endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>