<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NeivActiva - Configuracion</title>
    <link rel="stylesheet" href="/assets/css/neivactiva-2026.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="/assets/css/views/configuracion.css">
</head>
<body>
<?php include 'partials/sidebar.php'; ?>

<main class="main-wrapper">
    <nav class="top-navbar">
        <button class="sidebar-toggle" type="button" data-sidebar-toggle aria-label="Abrir menu">
            <i class="bi bi-list"></i>
        </button>
        <div>
            <h1 class="page-title">Configuracion del sistema</h1>
            <p class="page-subtitle">Preferencias generales de plataforma, seguridad y automatizaciones.</p>
        </div>
        <div class="header-actions">
            <button class="btn btn-primary" type="button">
                <i class="bi bi-check2-circle"></i>
                Guardar cambios
            </button>
        </div>
    </nav>

    <div class="dashboard-content">
        <div class="config-grid">
            <aside class="config-nav" aria-label="Secciones de configuracion">
                <a href="#" class="config-nav-item active"><i class="bi bi-gear-fill"></i> General</a>
                <a href="#" class="config-nav-item"><i class="bi bi-palette-fill"></i> Apariencia</a>
                <a href="#" class="config-nav-item"><i class="bi bi-shield-lock-fill"></i> Seguridad</a>
                <a href="#" class="config-nav-item"><i class="bi bi-bell-fill"></i> Notificaciones</a>
                <a href="#" class="config-nav-item"><i class="bi bi-hdd-network-fill"></i> API y sistema</a>
            </aside>

            <section class="settings-card">
                <div class="setting-row">
                    <div class="setting-info">
                        <h2>Acceso publico</h2>
                        <p>Permitir que visitantes consulten calendario y eventos sin iniciar sesion.</p>
                    </div>
                    <label class="switch" aria-label="Activar acceso publico">
                        <input type="checkbox" checked>
                        <span class="slider"></span>
                    </label>
                </div>

                <div class="setting-row">
                    <div class="setting-info">
                        <h2>Modo mantenimiento</h2>
                        <p>Desactivar temporalmente el acceso publico para realizar actualizaciones.</p>
                    </div>
                    <label class="switch" aria-label="Activar modo mantenimiento">
                        <input type="checkbox">
                        <span class="slider"></span>
                    </label>
                </div>

                <div class="setting-row">
                    <div class="setting-info">
                        <h2>Certificados automaticos</h2>
                        <p>Emitir certificados inmediatamente tras registrar asistencia valida.</p>
                    </div>
                    <label class="switch" aria-label="Activar certificados automaticos">
                        <input type="checkbox" checked>
                        <span class="slider"></span>
                    </label>
                </div>

                <div class="setting-row setting-row-last">
                    <div class="setting-info setting-info-wide">
                        <h2>Nombre de la plataforma</h2>
                        <p>Texto visible en correos, reportes y encabezados administrativos.</p>
                        <input type="text" class="form-control-modern setting-input" value="NeivActiva">
                    </div>
                </div>
            </section>
        </div>
    </div>
</main>
<script src="/assets/js/input-rules.js"></script>
</body>
</html>

