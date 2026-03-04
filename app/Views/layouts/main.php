<!DOCTYPE html>
<html lang="es-CL">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>
        <?=\App\Core\View::e($pageTitle ?? 'CRM')?> — Converclick CRM
    </title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="/assets/css/app.css">
</head>

<body>
    <div class="crm-wrapper">
        <!-- Sidebar -->
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-logo">
                <img src="https://marketing-digital.converclick.com/wp-content/uploads/2025/01/Logo-Converclick-2022-negativo@4x-1-1.png" alt="Converclick">
            </div>
            <nav class="sidebar-nav">
                <a href="/dashboard" class="nav-item <?=($activeMenu ?? '') === 'dashboard' ? 'active' : ''?>">
                    <i class="bi bi-speedometer2"></i><span>Dashboard</span>
                </a>
                <a href="/leads" class="nav-item <?=($activeMenu ?? '') === 'leads' ? 'active' : ''?>">
                    <i class="bi bi-people-fill"></i><span>Leads</span>
                </a>
                <a href="/pipeline" class="nav-item <?=($activeMenu ?? '') === 'pipeline' ? 'active' : ''?>">
                    <i class="bi bi-kanban-fill"></i><span>Pipeline</span>
                </a>
                <?php if (\App\Core\Auth::isAdmin()): ?>
                <a href="/integrations" class="nav-item <?=($activeMenu ?? '') === 'integrations' ? 'active' : ''?>">
                    <i class="bi bi-plug-fill"></i><span>Integraciones</span>
                </a>
                <a href="/users" class="nav-item <?=($activeMenu ?? '') === 'users' ? 'active' : ''?>">
                    <i class="bi bi-person-gear"></i><span>Usuarios</span>
                </a>
                <a href="/settings" class="nav-item <?=($activeMenu ?? '') === 'settings' ? 'active' : ''?>">
                    <i class="bi bi-gear-fill"></i><span>Ajustes</span>
                </a>
                <?php
endif; ?>
            </nav>
            <div class="sidebar-footer">
                <div class="user-info">
                    <div class="user-avatar">
                        <i class="bi bi-person-circle"></i>
                    </div>
                    <div>
                        <div class="fw-semibold" style="font-size:.85rem">
                            <?=\App\Core\View::e($user['name'] ?? '')?>
                        </div>
                        <div class="text-muted" style="font-size:.75rem">
                            <?=\App\Core\View::e(ucfirst($user['role'] ?? ''))?>
                        </div>
                    </div>
                </div>
                <a href="/logout" class="btn btn-sm btn-outline-danger mt-2 w-100">
                    <i class="bi bi-box-arrow-right"></i> Salir
                </a>
            </div>
        </aside>

        <!-- Main content -->
        <main class="main-content">
            <div class="topbar">
                <button class="btn btn-sm sidebar-toggle" id="sidebarToggle">
                    <i class="bi bi-list"></i>
                </button>
                <div class="topbar-title">
                    <?=\App\Core\View::e($pageTitle ?? '')?>
                </div>
            </div>

            <?php if (!empty($flash)): ?>
            <div class="container-fluid pt-3">
                <div class="alert alert-<?= $flash['type']?> alert-dismissible fade show" role="alert">
                    <?=\App\Core\View::e($flash['message'])?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            </div>
            <?php
endif; ?>

            <div class="content-area">
                <?= $content?>
            </div>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="/assets/js/app.js"></script>
</body>

</html>