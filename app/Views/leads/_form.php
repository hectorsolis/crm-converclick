<!-- FILE: app/Views/leads/_form.php — Partial compartido entre create y edit -->
<?php
$lead = $lead ?? [];
$v    = fn($k, $d='') => \App\Core\View::e($lead[$k] ?? $d);
?>

<!-- Datos personales -->
<h6 class="section-heading">Datos del Contacto</h6>
<div class="row g-3 mb-3">
    <div class="col-md-6">
        <label class="form-label required">Nombre completo</label>
        <input type="text" name="name" class="form-control" value="<?= $v('name') ?>" required placeholder="Ej: María González">
    </div>
    <div class="col-md-6">
        <label class="form-label">Email</label>
        <input type="email" name="email" class="form-control" value="<?= $v('email') ?>" placeholder="contacto@empresa.cl">
    </div>
    <div class="col-md-6">
        <label class="form-label">Teléfono</label>
        <input type="tel" name="phone" class="form-control" value="<?= $v('phone') ?>" placeholder="+56 9 XXXX XXXX">
    </div>
    <div class="col-md-6">
        <label class="form-label">Vendedor asignado</label>
        <select name="assigned_to" class="form-select">
            <option value="">— Sin asignar —</option>
            <?php foreach ($vendedores as $v2): ?>
            <option value="<?= $v2['id'] ?>" <?= isset($lead['assigned_to']) && $lead['assigned_to'] == $v2['id'] ? 'selected' : '' ?>>
                <?= \App\Core\View::e($v2['name']) ?>
            </option>
            <?php endforeach; ?>
        </select>
    </div>
</div>

<h6 class="section-heading">Empresa</h6>
<div class="row g-3 mb-3">
    <div class="col-md-5">
        <label class="form-label">Nombre empresa</label>
        <input type="text" name="company_name" class="form-control" value="<?= $v('company_name') ?>" placeholder="Empresa S.A.">
    </div>
    <div class="col-md-4">
        <label class="form-label">Rubro</label>
        <input type="text" name="company_industry" class="form-control" value="<?= $v('company_industry') ?>" placeholder="Tecnología, Retail…">
    </div>
    <div class="col-md-3">
        <label class="form-label">Tamaño</label>
        <select name="company_size" class="form-select">
            <option value="">— —</option>
            <?php foreach (['1-10','11-50','51-200','201-500','500+'] as $sz): ?>
            <option value="<?= $sz ?>" <?= $v('company_size') === $sz ? 'selected' : '' ?>><?= $sz ?> personas</option>
            <?php endforeach; ?>
        </select>
    </div>
</div>

<h6 class="section-heading">Calificación BANT</h6>
<div class="row g-2 mb-3">
    <?php
    $qualItems = [
        'has_budget'         => 'Tiene presupuesto',
        'has_deadline'       => 'Tiene plazo definido',
        'has_active_problem' => 'Problema activo',
        'decision_maker'     => 'Es decisor de compra',
    ];
    foreach ($qualItems as $field => $label): ?>
    <div class="col-6 col-md-3">
        <div class="form-check form-switch qual-switch">
            <input class="form-check-input" type="checkbox" name="<?= $field ?>" id="<?= $field ?>"
                   value="1" <?= !empty($lead[$field]) ? 'checked' : '' ?>>
            <label class="form-check-label" for="<?= $field ?>"><?= $label ?></label>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<h6 class="section-heading">Gestión Comercial</h6>
<div class="row g-3">
    <div class="col-12">
        <label class="form-label">Contexto / Notas</label>
        <textarea name="context_notes" class="form-control" rows="3"
                  placeholder="Información relevante del lead, necesidades, conversaciones previas…"><?= $v('context_notes') ?></textarea>
    </div>
    <div class="col-md-8">
        <label class="form-label">Próximo paso</label>
        <input type="text" name="next_step" class="form-control" value="<?= $v('next_step') ?>"
               placeholder="Ej: Enviar propuesta, Agendar demo…">
    </div>
    <div class="col-md-4">
        <label class="form-label">Fecha próximo paso</label>
        <input type="datetime-local" name="next_step_date" class="form-control"
               value="<?= $v('next_step_date') ? date('Y-m-d\TH:i', strtotime($lead['next_step_date'])) : '' ?>">
    </div>
</div>
