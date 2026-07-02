<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NeivActiva - Mis Certificados</title>
    <link rel="stylesheet" href="/NeivActiva/public/assets/css/neivactiva-2026.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="/NeivActiva/public/assets/css/views/mis_certificados.css">
</head>
<body>
<?php include 'partials/sidebar.php'; ?>

<main class="main-wrapper">
    <nav class="top-navbar">
        <button class="sidebar-toggle" type="button" data-sidebar-toggle aria-label="Abrir menu">
            <i class="bi bi-list"></i>
        </button>
        <div>
            <h1 class="page-title">Certificaciones digitales</h1>
            <p class="page-subtitle">Consulta tus documentos disponibles y el estado de cada participacion.</p>
        </div>
        <div class="header-actions">
            <a href="?view=inscripcion" class="btn btn-primary">
                <i class="bi bi-plus-lg"></i>
                Nueva inscripcion
            </a>
        </div>
    </nav>

    <div class="dashboard-content">
        <div class="split-layout">
            <aside class="info-sidebar">
                <section class="welcome-card-premium">
                    <span class="cert-eyebrow">Mi historial</span>
                    <h2>Tu recorrido</h2>
                    <p>Guardamos tus participaciones y certificados para que puedas consultarlos cuando los necesites.</p>

                    <div class="stat-mini-grid">
                        <div class="stat-mini-card">
                            <i class="bi bi-calendar-check"></i>
                            <span><?php echo count($mis_inscripciones); ?></span>
                            <label>Eventos</label>
                        </div>
                        <div class="stat-mini-card">
                            <i class="bi bi-award"></i>
                            <span><?php
                                $aprobados = array_filter($mis_inscripciones, function($i) {
                                    $eventoFinalizado = ($i['estado_evento'] ?? 'Activo') === 'Terminado' || strtotime($i['fecha_evento']) < time();
                                    return $eventoFinalizado && $i['estado_asistencia'] === 'Asistio';
                                });
                                echo count($aprobados);
                            ?></span>
                            <label>Logros</label>
                        </div>
                    </div>

                    <div class="cert-note">
                        <i class="bi bi-info-circle-fill"></i>
                        <span>Certificados verificados y listos para descarga.</span>
                    </div>
                </section>

                <section class="help-card">
                    <h3>Necesitas ayuda?</h3>
                    <p>Si un certificado no aparece tras 24 horas del evento, contacta a soporte.</p>
                    <a href="#">Soporte tecnico <i class="bi bi-arrow-right"></i></a>
                </section>
            </aside>

            <section class="certs-container">
                <div class="section-header">
                    <h2>Mis documentos</h2>
                    <div class="records-count">Mostrando <?php echo count($mis_inscripciones); ?> registros</div>
                </div>

                <?php if (isset($_GET['correo'])): ?>
                    <div class="cert-alert success">Certificado enviado al correo registrado.</div>
                <?php elseif (isset($_GET['correo_error'])): ?>
                    <div class="cert-alert error">No se pudo enviar el correo. Revisa la configuracion de correo del servidor.</div>
                <?php elseif (isset($_GET['error'])): ?>
                    <div class="cert-alert error">El certificado no esta disponible todavia.</div>
                <?php endif; ?>

                <?php if (empty($mis_inscripciones)): ?>
                    <div class="empty-state-dash">
                        <i class="bi bi-stars"></i>
                        <h3>Tu vitrina esta esperando</h3>
                        <p>Aun no tienes inscripciones registradas. Neiva tiene mucho para ofrecerte.</p>
                        <a href="?view=calendario" class="btn-explore">Explorar calendario <i class="bi bi-rocket-takeoff"></i></a>
                    </div>
                <?php else: ?>
                    <div class="cert-list-grid">
                        <?php foreach ($mis_inscripciones as $i): ?>
                            <?php
                                $finalizado = ($i['estado_evento'] ?? 'Activo') === 'Terminado' || strtotime($i['fecha_evento']) < time();
                                $asistio = $i['estado_asistencia'] === 'Asistio';
                            ?>
                            <article class="cert-item-card <?php echo !$finalizado ? 'is-pending' : ''; ?>">
                                <div class="cert-icon-circle">
                                    <i class="bi <?php echo $finalizado ? ($asistio ? 'bi-patch-check-fill' : 'bi-x-circle') : 'bi-hourglass-split'; ?>"></i>
                                </div>
                                <h3 class="cert-item-title"><?php echo htmlspecialchars($i['evento_titulo']); ?></h3>
                                <div class="cert-item-date">
                                    <i class="bi bi-calendar3"></i>
                                    <?php echo date('d M, Y', strtotime($i['fecha_evento'])); ?>
                                </div>

                                <div class="cert-card-footer">
                                    <?php if (!$finalizado): ?>
                                        <span class="status-pill pill-wait">Pendiente</span>
                                        <span class="cert-state-info info">Proximamente</span>
                                    <?php elseif ($asistio): ?>
                                        <span class="status-pill pill-success">Verificado</span>
                                        <div class="cert-action-group">
                                            <a href="?view=descargar_certificado&id=<?php echo (int) $i['id']; ?>" class="btn-action-sm">Descargar PDF</a>
                                            <form action="?view=enviar_certificado" method="POST">
                                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                                                <input type="hidden" name="id" value="<?php echo (int) $i['id']; ?>">
                                                <button type="submit" class="btn-action-sm email">Enviar correo</button>
                                            </form>
                                        </div>
                                    <?php else: ?>
                                        <span class="status-pill pill-fail">Sin asistencia</span>
                                        <span class="cert-state-info error">No disponible</span>
                                    <?php endif; ?>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>
        </div>
    </div>
</main>
</body>
</html>

