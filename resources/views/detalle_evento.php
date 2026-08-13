<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NeivActiva - Detalle del Evento</title>
    <link rel="stylesheet" href="/assets/css/neivactiva-2026.css?v=<?php echo @filemtime(ROOT_PATH . '/public/assets/css/neivactiva-2026.css'); ?>">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="/assets/css/views/detalle_evento.css?v=<?php echo @filemtime(ROOT_PATH . '/public/assets/css/views/detalle_evento.css'); ?>">
</head>
<body>

<?php include 'partials/sidebar.php'; ?>

<?php
    $imagen = !empty($evento['ruta_imagen']) ? $evento['ruta_imagen'] : '/assets/img/Neiva,_La_Gaitana_monumento_emblematico_de_la_ciudad.jpg';
    if (strpos($imagen, '/') !== 0 && strpos($imagen, 'http') !== 0) {
        $imagen = '/' . ltrim($imagen, '/');
    }
    $cupoMax      = (int) ($evento['cupo_maximo'] ?? 0);
    $inscritos    = (int) ($evento['inscritos_actuales'] ?? 0);
    $cuposLibres  = max(0, $cupoMax - $inscritos);
    $ocupacion    = $cupoMax > 0 ? min(100, round($inscritos / $cupoMax * 100)) : 0;
    $horaEvento   = !empty($evento['hora_evento']) ? date('g:i A', strtotime($evento['hora_evento'])) : 'Por confirmar';
    $fechaLarga   = !empty($evento['fecha_evento']) ? date('d/m/Y', strtotime($evento['fecha_evento'])) : 'Por confirmar';
    $estado       = $evento['estado_evento'] ?? 'Activo';
    $esActivo     = ($estado === 'Activo') && ($cuposLibres > 0);
    $descripcion  = trim((string) ($evento['descripcion'] ?? ''));

    $dias = ['Sunday'=>'Domingo','Monday'=>'Lunes','Tuesday'=>'Martes','Wednesday'=>'Miercoles','Thursday'=>'Jueves','Friday'=>'Viernes','Saturday'=>'Sabado'];
    $meses = [1=>'ene',2=>'feb',3=>'mar',4=>'abr',5=>'may',6=>'jun',7=>'jul',8=>'ago',9=>'sep',10=>'oct',11=>'nov',12=>'dic'];
    $ts = !empty($evento['fecha_evento']) ? strtotime($evento['fecha_evento']) : time();
    $diaSemana = $dias[date('l', $ts)] ?? '';
    $diaNum = date('j', $ts);
    $mesTxt = $meses[(int) date('n', $ts)] ?? '';
?>

<main class="main-wrapper">
    <nav class="top-navbar">
        <h1 class="page-title">Detalle del Evento</h1>
        <div class="header-actions">
            <a href="javascript:history.back()" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Volver
            </a>
        </div>
    </nav>

    <div class="dashboard-content">
        <article class="de-wrap">
            <!-- Hero -->
            <header class="de-hero">
                <img src="<?php echo htmlspecialchars($imagen); ?>" alt="<?php echo htmlspecialchars($evento['titulo']); ?>"
                     onerror="this.style.display='none'; this.parentElement.classList.add('de-hero--noimg')">
                <div class="de-hero-overlay"></div>
                <div class="de-hero-content">
                    <span class="de-cat"><i class="bi bi-tag-fill"></i> <?php echo htmlspecialchars($evento['categoria'] ?? 'General'); ?></span>
                    <h2 class="de-title"><?php echo htmlspecialchars($evento['titulo']); ?></h2>
                </div>
                <span class="de-status <?php echo $esActivo ? 'is-active' : 'is-closed'; ?>">
                    <i class="bi <?php echo $esActivo ? 'bi-broadcast' : 'bi-slash-circle'; ?>"></i>
                    <?php echo $esActivo ? 'Inscripciones abiertas' : 'No disponible'; ?>
                </span>
            </header>

            <!-- Cuerpo en dos columnas -->
            <div class="de-grid">
                <!-- Columna principal -->
                <section class="de-main">
                    <h3 class="de-section-title"><i class="bi bi-info-circle"></i> Acerca del evento</h3>
                    <?php if ($descripcion !== ''): ?>
                        <p class="de-desc"><?php echo nl2br(htmlspecialchars($descripcion)); ?></p>
                    <?php else: ?>
                        <p class="de-desc de-desc--empty">Este evento aun no tiene una descripcion detallada.</p>
                    <?php endif; ?>

                    <h3 class="de-section-title"><i class="bi bi-calendar2-week"></i> Informacion clave</h3>
                    <div class="de-facts">
                        <div class="de-fact">
                            <span class="de-fact-ico"><i class="bi bi-calendar-event"></i></span>
                            <div><span class="de-fact-label">Fecha</span><strong><?php echo htmlspecialchars($fechaLarga); ?></strong></div>
                        </div>
                        <div class="de-fact">
                            <span class="de-fact-ico"><i class="bi bi-clock"></i></span>
                            <div><span class="de-fact-label">Hora</span><strong><?php echo htmlspecialchars($horaEvento); ?></strong></div>
                        </div>
                        <div class="de-fact">
                            <span class="de-fact-ico"><i class="bi bi-geo-alt-fill"></i></span>
                            <div><span class="de-fact-label">Lugar</span><strong><?php echo htmlspecialchars($evento['ubicacion'] ?? 'Por confirmar'); ?></strong></div>
                        </div>
                        <div class="de-fact">
                            <span class="de-fact-ico"><i class="bi bi-people-fill"></i></span>
                            <div><span class="de-fact-label">Cupos libres</span><strong><?php echo $cuposLibres; ?> de <?php echo $cupoMax; ?></strong></div>
                        </div>
                    </div>
                </section>

                <!-- Columna lateral: tarjeta de accion -->
                <aside class="de-aside">
                    <div class="de-card">
                        <div class="de-datebox">
                            <span class="de-datebox-dow"><?php echo htmlspecialchars($diaSemana); ?></span>
                            <span class="de-datebox-day"><?php echo $diaNum; ?></span>
                            <span class="de-datebox-mon"><?php echo htmlspecialchars($mesTxt); ?> <?php echo date('Y', $ts); ?></span>
                        </div>

                        <div class="de-card-row"><i class="bi bi-clock"></i><span><?php echo htmlspecialchars($horaEvento); ?></span></div>
                        <div class="de-card-row"><i class="bi bi-geo-alt"></i><span><?php echo htmlspecialchars($evento['ubicacion'] ?? 'Por confirmar'); ?></span></div>

                        <div class="de-occ">
                            <div class="de-occ-head">
                                <span>Ocupacion</span>
                                <strong><?php echo $ocupacion; ?>%</strong>
                            </div>
                            <div class="de-occ-track"><div class="de-occ-bar" style="width: <?php echo $ocupacion; ?>%"></div></div>
                            <div class="de-occ-foot"><span><?php echo $inscritos; ?> inscritos</span><span><?php echo $cuposLibres; ?> disponibles</span></div>
                        </div>

                        <?php if (!empty($yaInscrito)): ?>
                            <div class="de-already"><i class="bi bi-check-circle-fill"></i> Ya estas inscrito en este evento</div>
                            <a href="?view=mis_eventos_inscritos" class="de-cta de-cta--ghost">
                                <i class="bi bi-calendar-check"></i> Ver en Mis Eventos
                            </a>
                        <?php elseif ($esActivo): ?>
                            <a href="?view=inscripcion&id=<?php echo (int) $evento['id']; ?>" class="de-cta">
                                <i class="bi bi-ticket-perforated"></i> Inscribirme
                            </a>
                        <?php else: ?>
                            <button class="de-cta" disabled><i class="bi bi-slash-circle"></i> No disponible</button>
                        <?php endif; ?>

                        <p class="de-card-note"><i class="bi bi-shield-check"></i> Reserva segura con QR de acceso</p>
                    </div>
                </aside>
            </div>
        </article>
    </div>
</main>

</body>
</html>
