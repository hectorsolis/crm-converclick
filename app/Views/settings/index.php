<?php $pageTitle = 'Ajustes'; ?>
<div class="container-fluid" style="max-width:600px">
    <div class="card-crm">
        <div class="card-crm-header"><i class="bi bi-gear-fill me-2"></i>Configuración General</div>
        <div class="card-crm-body">
            <form method="POST" action="/settings/save">
                <?=\App\Core\Csrf::field()?>
                <div class="mb-3">
                    <label class="form-label">Nombre del CRM</label>
                    <input type="text" name="app_name" class="form-control"
                        value="<?=\App\Core\View::e($settings['app_name'] ?? 'Converclick CRM')?>">
                </div>
                <div class="mb-3">
                    <label class="form-label">Texto del logo</label>
                    <input type="text" name="logo_text" class="form-control"
                        value="<?=\App\Core\View::e($settings['logo_text'] ?? 'Converclick')?>">
                </div>
                <div class="mb-3">
                    <label class="form-label">Zona horaria</label>
                    <select name="timezone" class="form-select">
                        <?php foreach ($timezones as $tz => $label): ?>
                        <option value="<?= $tz?>" <?=($settings['timezone'] ?? 'America/Santiago' )===$tz ? 'selected'
                            : ''?>>
                            <?=\App\Core\View::e($label)?>
                        </option>
                        <?php
endforeach; ?>
                    </select>
                    <div class="form-text">Se aplica a todas las fechas del sistema.</div>
                </div>
                <div class="mb-4">
                    <label class="form-label">Color principal</label>
                    <div class="d-flex align-items-center gap-2">
                        <input type="color" name="primary_color" class="form-control form-control-color"
                            value="<?=\App\Core\View::e($settings['primary_color'] ?? '#E63946')?>">
                        <small class="text-muted">Color de énfasis del CRM</small>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-save me-1"></i>Guardar Configuración
                </button>
            </form>
        </div>
    </div>
</div>