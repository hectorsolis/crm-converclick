<?php $pageTitle = 'Integraciones'; ?>
<div class="container-fluid">
    <!-- Tabs -->
    <ul class="nav nav-tabs mb-3" id="integTabs">
        <li class="nav-item">
            <a class="nav-link active" href="#mautic-tab" data-bs-toggle="tab">
                <i class="bi bi-ui-checks me-1"></i>Mautic
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="#uazapi-tab" data-bs-toggle="tab">
                <i class="bi bi-whatsapp me-1"></i>uazapiGO / WhatsApp
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="#chatwoot-tab" data-bs-toggle="tab">
                <i class="bi bi-chat-dots me-1"></i>Chatwoot
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="#logs-tab" data-bs-toggle="tab">
                <i class="bi bi-journal-code me-1"></i>Logs
            </a>
        </li>
    </ul>

    <div class="tab-content">
        <!-- ── MAUTIC ───────────────────────────────── -->
        <div class="tab-pane fade show active" id="mautic-tab">
            <div class="card-crm" style="max-width:700px">
                <div class="card-crm-header"><i class="bi bi-ui-checks me-2"></i>Configuración Mautic v2.16.3</div>
                <div class="card-crm-body">
                    <div class="alert alert-info small">
                        <strong>Webhook URL para Mautic:</strong><br>
                        <code><?= APP_URL?>/integrations/mautic/webhook</code><br>
                        Agregar en Mautic → Configuración → Webhooks → Nuevo Webhook.
                    </div>
                    <form method="POST" action="/integrations/mautic/save">
                        <?=\App\Core\Csrf::field()?>
                        <div class="mb-3">
                            <label class="form-label">URL base de Mautic</label>
                            <input type="url" name="base_url" class="form-control"
                                value="<?=\App\Core\View::e($mautic['base_url'] ?? '')?>"
                                placeholder="https://mautic.tudominio.cl">
                        </div>
                        <div class="row g-2 mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Client ID (API)</label>
                                <input type="text" name="client_id" class="form-control"
                                    value="<?=\App\Core\View::e($mautic['client_id'] ?? '')?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Client Secret</label>
                                <input type="text" name="client_secret" class="form-control"
                                    value="<?=\App\Core\View::e($mautic['client_secret'] ?? '')?>">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">IDs de formularios habilitados</label>
                            <input type="text" name="form_ids" class="form-control"
                                value="<?=\App\Core\View::e(implode(', ', $mautic['form_ids'] ?? []))?>"
                                placeholder="1, 2, 5 (separados por coma)">
                            <div class="form-text">Solo los formularios con IDs en esta lista crearán leads. Dejar vacío
                                para aceptar todos.</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Secret de validación del webhook</label>
                            <input type="text" name="webhook_secret" class="form-control"
                                value="<?=\App\Core\View::e($mautic['webhook_secret'] ?? '')?>"
                                placeholder="Token secreto para validar llamadas entrantes">
                        </div>
                        <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i>Guardar</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- ── UAZAPI ──────────────────────────────── -->
        <div class="tab-pane fade" id="uazapi-tab">
            <div class="card-crm" style="max-width:700px">
                <div class="card-crm-header"><i class="bi bi-whatsapp me-2"></i>Configuración uazapiGO V2</div>
                <div class="card-crm-body">
                    <div class="alert alert-info small">
                        <strong>Webhook URL para uazapiGO:</strong><br>
                        <code><?= APP_URL?>/integrations/uazapi/webhook?secret=TU_SECRET</code><br>
                        <strong>Recomendado:</strong> configurar <code>excludeMessages: ["wasSentByApi"]</code> para
                        evitar bucles.
                    </div>
                    <form method="POST" action="/integrations/uazapi/save" class="mb-4">
                        <?=\App\Core\Csrf::field()?>
                        <div class="mb-3">
                            <label class="form-label">URL base de uazapi</label>
                            <input type="url" name="base_url" class="form-control"
                                value="<?=\App\Core\View::e($uazapi['base_url'] ?? '')?>"
                                placeholder="https://miinstancia.uazapi.com">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Instance Token <small class="text-muted">(header:
                                    token)</small></label>
                            <input type="text" name="instance_token" class="form-control"
                                value="<?=\App\Core\View::e($uazapi['instance_token'] ?? '')?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Admin Token <small class="text-muted">(opcional, header:
                                    admintoken)</small></label>
                            <input type="text" name="admin_token" class="form-control"
                                value="<?=\App\Core\View::e($uazapi['admin_token'] ?? '')?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Secret del Webhook del CRM</label>
                            <input type="text" name="webhook_secret" class="form-control"
                                value="<?=\App\Core\View::e($uazapi['webhook_secret'] ?? '')?>"
                                placeholder="Cadena aleatoria para proteger el endpoint">
                        </div>
                        <button type="submit" class="btn btn-primary me-2"><i
                                class="bi bi-save me-1"></i>Guardar</button>
                    </form>

                    <hr>
                    <h6>Registrar Webhook automáticamente</h6>
                    <p class="text-muted small">Presiona el botón para que el CRM registre el webhook en tu instancia de
                        uazapiGO.</p>
                    <form method="POST" action="/integrations/uazapi/register-webhook">
                        <?=\App\Core\Csrf::field()?>
                        <button type="submit" class="btn btn-success me-2">
                            <i class="bi bi-broadcast me-1"></i>Registrar Webhook en uazapiGO
                        </button>
                    </form>
                    <button class="btn btn-outline-secondary btn-sm mt-2" onclick="testUzapi()">
                        <i class="bi bi-wifi me-1"></i>Probar Conexión
                    </button>
                    <div id="uazapiTestResult" class="mt-2 small text-muted"></div>
                </div>
            </div>
        </div>

        <!-- ── CHATWOOT ──────────────────────────────── -->
        <div class="tab-pane fade" id="chatwoot-tab">
            <div class="card-crm" style="max-width:700px">
                <div class="card-crm-header"><i class="bi bi-chat-dots me-2"></i>Configuración Chatwoot</div>
                <div class="card-crm-body">
                    <div class="alert alert-info small">
                        <strong>Webhook URL para Chatwoot:</strong><br>
                        <code><?= APP_URL?>/integrations/chatwoot/webhook?secret=TU_SECRET</code><br>
                        Configurar en Chatwoot → Ajustes → Integraciones → Webhooks.
                    </div>
                    <form method="POST" action="/integrations/chatwoot/save">
                        <?=\App\Core\Csrf::field()?>
                        <div class="mb-3">
                            <label class="form-label">URL base de Chatwoot</label>
                            <input type="url" name="base_url" class="form-control"
                                value="<?=\App\Core\View::e($chatwoot['base_url'] ?? '')?>"
                                placeholder="https://app.chatwoot.com">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">API Access Token</label>
                            <input type="password" name="api_access_token" class="form-control"
                                value="<?=\App\Core\View::e($chatwoot['api_access_token'] ?? '')?>">
                        </div>
                        <div class="row g-2 mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Account ID</label>
                                <input type="number" name="account_id" class="form-control"
                                    value="<?=\App\Core\View::e($chatwoot['account_id'] ?? '')?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Inbox Token (Identifier)</label>
                                <input type="text" name="inbox_identifier" class="form-control"
                                    value="<?=\App\Core\View::e($chatwoot['inbox_identifier'] ?? '')?>">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Secret del Webhook</label>
                            <input type="text" name="webhook_secret" class="form-control"
                                value="<?=\App\Core\View::e($chatwoot['webhook_secret'] ?? '')?>"
                                placeholder="Token para validar llamadas entrantes">
                        </div>
                        <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i>Guardar</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- ── LOGS ───────────────────────────────────── -->
        <div class="tab-pane fade" id="logs-tab">
            <div class="card-crm">
                <div class="card-crm-header"><i class="bi bi-journal-code me-2"></i>Últimos Eventos de Integración</div>
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Fuente</th>
                                <th>Dir.</th>
                                <th>Evento</th>
                                <th>Estado</th>
                                <th>Mensaje</th>
                                <th>IP</th>
                                <th>Fecha</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($logs as $log): ?>
                            <tr>
                                <td><span class="badge bg-secondary">
                                        <?=\App\Core\View::e($log['source'])?>
                                    </span></td>
                                <td>
                                    <?= $log['direction'] === 'in' ? '↓ IN' : '↑ OUT'?>
                                </td>
                                <td class="small">
                                    <?=\App\Core\View::e($log['event_type'] ?? '')?>
                                </td>
                                <td>
                                    <?= statusBadge($log['status'])?>
                                </td>
                                <td class="small text-muted">
                                    <?=\App\Core\View::e(substr($log['message'] ?? '', 0, 60))?>
                                </td>
                                <td class="small text-muted">
                                    <?=\App\Core\View::e($log['ip_address'] ?? '')?>
                                </td>
                                <td class="small text-muted">
                                    <?=\App\Helpers\DateHelper::toLocal($log['created_at'])?>
                                </td>
                            </tr>
                            <?php
endforeach; ?>
                            <?php if (empty($logs)): ?>
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">Sin eventos registrados</td>
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

<script>
    function testUzapi() {
        const el = document.getElementById('uazapiTestResult');
        el.textContent = 'Probando…';
        fetch('/integrations/uazapi/test')
            .then(r => r.json())
            .then(d => { el.textContent = '✅ Respuesta: ' + JSON.stringify(d, null, 2); })
            .catch(e => { el.textContent = '❌ Error: ' + e.message; });
    }
</script>

<?php
function statusBadge(string $status): string
{
    return match ($status) {
            'ok' => '<span class="badge bg-success">OK</span>',
            'error' => '<span class="badge bg-danger">Error</span>',
            'conflict' => '<span class="badge bg-warning text-dark">Conflicto</span>',
            'duplicate' => '<span class="badge bg-info text-dark">Duplicado</span>',
            default => '<span class="badge bg-secondary">' . $status . '</span>',
        };
}
?>