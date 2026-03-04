// FILE: public/assets/js/app.js

document.addEventListener('DOMContentLoaded', function () {

    // ── Sidebar mobile toggle ──────────────────────────────
    const toggle = document.getElementById('sidebarToggle');
    const sidebar = document.getElementById('sidebar');

    if (toggle && sidebar) {
        toggle.addEventListener('click', () => {
            sidebar.classList.toggle('open');
        });
        // Cerrar sidebar al hacer click fuera
        document.addEventListener('click', (e) => {
            if (window.innerWidth <= 768 &&
                !sidebar.contains(e.target) &&
                !toggle.contains(e.target)) {
                sidebar.classList.remove('open');
            }
        });
    }

    // ── Auto-dismiss alerts ────────────────────────────────
    const alerts = document.querySelectorAll('.alert.alert-success, .alert.alert-info');
    alerts.forEach(alert => {
        setTimeout(() => {
            const bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
            bsAlert.close();
        }, 4000);
    });

    // ── Confirm delete forms ───────────────────────────────
    document.querySelectorAll('form[data-confirm]').forEach(form => {
        form.addEventListener('submit', function (e) {
            const msg = form.dataset.confirm || '¿Estás seguro?';
            if (!confirm(msg)) e.preventDefault();
        });
    });

    // ── Checkbox to hidden input for BANT fields ───────────
    // Los checkboxes de tipo switch no envían valor si no están marcados.
    // Aseguramos que siempre se envíe 0 o 1.
    document.querySelectorAll('.qual-switch input[type="checkbox"]').forEach(cb => {
        cb.addEventListener('change', () => {
            cb.value = cb.checked ? '1' : '0';
        });
        // Forzar valor inicial
        cb.value = cb.checked ? '1' : '0';
    });

    // ── Auto-resize textarea ───────────────────────────────
    document.querySelectorAll('textarea').forEach(ta => {
        ta.addEventListener('input', function () {
            this.style.height = 'auto';
            this.style.height = this.scrollHeight + 'px';
        });
    });

    // ── Tooltips Bootstrap ─────────────────────────────────
    const tooltipEls = document.querySelectorAll('[title]');
    tooltipEls.forEach(el => {
        if (el.title) new bootstrap.Tooltip(el, { trigger: 'hover' });
    });

    // ── Highlight overdue rows ─────────────────────────────
    // Ya se hace vía PHP clase row-overdue, pero aseguramos visibilidad
    document.querySelectorAll('.row-overdue').forEach(row => {
        row.style.borderLeft = '3px solid #ef4444';
    });
});
