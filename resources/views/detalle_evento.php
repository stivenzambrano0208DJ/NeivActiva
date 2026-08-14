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

            <!-- Reseñas del evento -->
            <section class="de-reviews">
                <h3 class="de-section-title">
                    <i class="bi bi-chat-quote"></i> Reseñas
                    <?php if ((int) ($resumenResenas['total'] ?? 0) > 0): ?>
                        <span class="de-reviews-avg">
                            <i class="bi bi-star-fill"></i> <?php echo number_format((float) $resumenResenas['promedio'], 1); ?>
                            <small>(<?php echo (int) $resumenResenas['total']; ?>)</small>
                        </span>
                    <?php endif; ?>
                </h3>

                <?php if (isset($_GET['resena'])): ?>
                    <div class="de-review-flash de-review-flash--ok"><i class="bi bi-check-circle-fill"></i> ¡Gracias! Tu reseña se publicó.</div>
                <?php elseif (isset($_GET['resena_error'])): ?>
                    <?php
                        $rerr = [
                            'csrf'        => 'Sesión expirada, intenta de nuevo.',
                            'datos'       => 'Escribe un comentario y elige una calificación.',
                            'no_elegible' => 'Solo puedes reseñar eventos a los que asististe y que ya finalizaron.',
                            'general'     => 'No se pudo guardar la reseña.',
                        ];
                        $msgR = $rerr[$_GET['resena_error']] ?? 'No se pudo guardar la reseña.';
                    ?>
                    <div class="de-review-flash de-review-flash--err"><i class="bi bi-exclamation-circle-fill"></i> <?php echo htmlspecialchars($msgR); ?></div>
                <?php endif; ?>

                <?php if (!empty($puedeReseniar)): ?>
                    <form class="de-review-form" method="POST" action="/resena">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(\App\Core\Csrf::token()); ?>">
                        <input type="hidden" name="evento_id" value="<?php echo (int) $evento['id']; ?>">
                        <span class="de-review-label">Tu calificación</span>
                        <div class="de-rate">
                            <?php for ($s = 5; $s >= 1; $s--): ?>
                                <input type="radio" name="calificacion" id="star<?php echo $s; ?>" value="<?php echo $s; ?>"<?php echo $s === 5 ? ' checked' : ''; ?>>
                                <label for="star<?php echo $s; ?>" title="<?php echo $s; ?> estrellas">★</label>
                            <?php endfor; ?>
                        </div>
                        <label class="de-review-label" for="comentario">Tu comentario</label>
                        <textarea name="comentario" id="comentario" class="de-review-textarea" rows="3" maxlength="600" required placeholder="Cuéntanos cómo estuvo el evento…"></textarea>
                        <button type="submit" class="de-cta de-cta--review"><i class="bi bi-send"></i> Publicar reseña</button>
                    </form>
                <?php endif; ?>

                <?php if (empty($resenasEvento)): ?>
                    <p class="de-desc de-desc--empty">Este evento aún no tiene reseñas.</p>
                <?php else: ?>
                    <div class="de-review-list">
                        <?php foreach ($resenasEvento as $r):
                            $rn   = trim((string) ($r['nombre'] ?? 'Participante'));
                            $rini = strtoupper(mb_substr($rn, 0, 1, 'UTF-8'));
                            $rp   = preg_split('/\s+/', $rn);
                            if (count($rp) > 1) { $rini .= strtoupper(mb_substr(end($rp), 0, 1, 'UTF-8')); }
                            $rc   = max(1, min(5, (int) ($r['calificacion'] ?? 5)));
                        ?>
                        <article class="de-review">
                            <div class="de-review-avatar"><?php echo htmlspecialchars($rini); ?></div>
                            <div class="de-review-body">
                                <div class="de-review-head">
                                    <strong><?php echo htmlspecialchars($rn); ?></strong>
                                    <span class="de-review-stars">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <i class="bi bi-star<?php echo $i <= $rc ? '-fill' : ''; ?>"></i>
                                        <?php endfor; ?>
                                    </span>
                                </div>
                                <?php if (!empty($r['rol_texto'])): ?>
                                    <span class="de-review-role"><?php echo htmlspecialchars($r['rol_texto']); ?></span>
                                <?php endif; ?>
                                <p><?php echo nl2br(htmlspecialchars((string) ($r['comentario'] ?? ''))); ?></p>
                            </div>
                        </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>
        </article>
    </div>
</main>

<script src="/assets/js/input-rules.js"></script>
</body>
</html>
