<div class="ambient-bg"></div>
<link rel="stylesheet" href="/NeivActiva/public/assets/css/neivactiva-2026.css">
<?php
$rol = $_SESSION['rol'] ?? 'invitado';
$nombre = $_SESSION['usuario_nombre'] ?? 'Invitado';
$view = $_GET['view'] ?? ($view ?? 'dashboard');

$rolDisplay = [
    'admin' => 'Administrador',
    'organizador' => 'Organizador',
    'cliente' => 'Participante',
    'participante' => 'Participante',
    'invitado' => 'Explorador',
][$rol] ?? 'Usuario';

$navItem = function ($target, $icon, $label) use ($view) {
    $active = $view === $target ? ' active' : '';
    echo '<li class="nav-item"><a href="?view=' . htmlspecialchars($target) . '" class="nav-link' . $active . '">';
    echo '<i class="bi ' . htmlspecialchars($icon) . '"></i><span>' . htmlspecialchars($label) . '</span></a></li>';
};
?>

<div class="sidebar-overlay" data-sidebar-close></div>
<aside class="sidebar" id="appSidebar">
    <a href="?view=dashboard" class="logo" aria-label="Ir al dashboard">
        <i class="bi bi-sun-fill"></i>
        <span>Neiv<span>Activa</span></span>
    </a>

    <div class="user-profile-sm">
        <div class="avatar">
            <?php if ($rol === 'invitado'): ?>
                <i class="bi bi-person-fill"></i>
            <?php else: ?>
                <?php echo htmlspecialchars(mb_strtoupper(mb_substr($nombre, 0, 1))); ?>
            <?php endif; ?>
        </div>
        <div class="info">
            <h4><?php echo htmlspecialchars($rol === 'invitado' ? 'Anonimo' : $nombre); ?></h4>
            <p><?php echo htmlspecialchars($rolDisplay); ?></p>
        </div>
    </div>

    <nav class="nav-menu" aria-label="Navegacion principal">
        <div class="nav-section-title">Explorar</div>
        <?php $navItem('dashboard', 'bi-grid-1x2-fill', 'Dashboard'); ?>
        <?php $navItem('calendario', 'bi-calendar3', 'Calendario'); ?>

        <?php if (in_array($rol, ['cliente', 'participante', 'admin', 'organizador'], true)): ?>
            <div class="nav-section-title">Mi Actividad</div>
            <?php $navItem('mis_eventos_inscritos', 'bi-calendar-check', 'Mis Eventos'); ?>
            <?php $navItem('inscripcion', 'bi-ticket-perforated', 'Inscripciones'); ?>
            <?php $navItem('mis_certificados', 'bi-award', 'Certificados'); ?>
            <?php $navItem('mis_qr', 'bi-qr-code-scan', 'Mis QR'); ?>
        <?php endif; ?>

        <?php if (in_array($rol, ['organizador', 'admin'], true)): ?>
            <div class="nav-section-title">Gestion de Eventos</div>
            <?php $navItem('gestionar_eventos', 'bi-calendar-event', 'Publicar Eventos'); ?>
            <?php $navItem('gestionar_inscripciones', 'bi-people', 'Inscripciones'); ?>
            <?php $navItem('asistencia', 'bi-qr-code-scan', 'Asistencia'); ?>

            <div class="nav-section-title">Participantes</div>
            <?php $navItem('participantes', 'bi-person-lines-fill', 'Participantes'); ?>
            <?php $navItem('inscripciones_admin', 'bi-ticket-perforated', 'Inscripcion Directa'); ?>
            <?php if ($rol === 'admin'): ?>
                <?php $navItem('carga_masiva', 'bi-file-earmark-arrow-up', 'Carga Masiva'); ?>
            <?php endif; ?>
        <?php endif; ?>

        <?php if ($rol === 'admin'): ?>
            <div class="nav-section-title">Administracion</div>
            <?php $navItem('usuarios', 'bi-person-gear', 'Usuarios'); ?>
            <?php $navItem('estadisticas', 'bi-bar-chart-fill', 'Estadisticas'); ?>
            <?php $navItem('configuracion', 'bi-gear-fill', 'Configuracion'); ?>
        <?php endif; ?>
    </nav>

    <div class="sidebar-session">
        <?php if ($rol === 'invitado'): ?>
            <a href="?view=login" class="btn btn-primary">
                <i class="bi bi-box-arrow-in-right"></i> Iniciar Sesion
            </a>
            <a href="?view=registro" class="btn btn-secondary">Crear Cuenta</a>
        <?php else: ?>
            <a href="?view=logout" class="nav-link logout-link">
                <i class="bi bi-box-arrow-left"></i><span>Cerrar Sesion</span>
            </a>
        <?php endif; ?>
    </div>
</aside>

<script>
(() => {
    const body = document.body;
    document.addEventListener('click', event => {
        if (event.target.closest('[data-sidebar-toggle]')) {
            body.classList.toggle('sidebar-open');
        }

        if (event.target.closest('[data-sidebar-close]') || event.target.closest('.sidebar .nav-link')) {
            body.classList.remove('sidebar-open');
        }
    });
})();
</script>
