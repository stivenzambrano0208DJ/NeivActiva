<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NeivActiva – Dashboard</title>
    <link rel="stylesheet" href="/assets/css/neivactiva-2026.css?v=<?php echo @filemtime(ROOT_PATH . '/public/assets/css/neivactiva-2026.css'); ?>">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="/assets/css/views/dashboard.css?v=<?php echo @filemtime(ROOT_PATH . '/public/assets/css/views/dashboard.css'); ?>">
</head>
<body>
<?php
$rol            = $_SESSION['rol'] ?? 'invitado';
$nombreUsuario  = $_SESSION['usuario_nombre'] ?? 'Invitado';
$esGuest        = ($rol === 'invitado');
$inscritosIds   = [];
foreach (($eventos_inscritos ?? []) as $i) {
    $inscritosIds[] = (int)($i['evento_id'] ?? $i['id_evento'] ?? $i['id'] ?? 0);
}
$eventos           = $lista_eventos ?? [];
$eventosDestacados = array_slice($eventos, 0, 3);
$fmtFecha = fn($f) => $f ? date('d M Y', strtotime($f)) : 'Por confirmar';
$fmtHora  = fn($h) => $h ? date('g:i A', strtotime($h)) : '';
$catIcon  = fn($c) => match($c) {
    'Cultural'  => 'bi-palette-fill',
    'Deportivo' => 'bi-trophy-fill',
    'Educativo' => 'bi-mortarboard-fill',
    default     => 'bi-calendar-event-fill'
};
?>

<?php include ROOT_PATH . '/resources/views/partials/sidebar.php'; ?>

<main class="main-wrapper db-page">

    <!-- ══════════════════════════════════════════
         HERO BANNER
    ══════════════════════════════════════════ -->
    <div class="db-hero">
        <div class="db-hero-bg"></div>
        <div class="db-hero-glow"></div>
        <div class="db-hero-inner">
            <div class="db-hero-left">
                <div class="db-hero-greeting">
                    <span class="db-greeting-pill">
                        <i class="bi bi-sun-fill"></i>
                        <?php
                            $h = (int)date('H');
                            echo $h < 12 ? 'Buenos días' : ($h < 19 ? 'Buenas tardes' : 'Buenas noches');
                        ?>
                    </span>
                    <h1 class="db-hero-title">
                        Hola, <span><?php echo htmlspecialchars($nombreUsuario); ?></span>
                    </h1>
                    <p class="db-hero-sub">Tu centro de actividad cultural en Neiva</p>
                </div>
                <div class="db-hero-actions">
                    <a href="/eventos" class="db-btn-primary">
                        <i class="bi bi-calendar-event-fill"></i> Explorar eventos
                    </a>
                    <?php if ($esGuest): ?>
                        <a href="/login" class="db-btn-ghost">
                            <i class="bi bi-box-arrow-in-right"></i> Iniciar sesión
                        </a>
                        <a href="/register" class="db-btn-ghost">
                            <i class="bi bi-person-plus"></i> Crear cuenta
                        </a>
                    <?php else: ?>
                        <a href="/dashboard?view=inscripcion" class="db-btn-ghost">
                            <i class="bi bi-ticket-perforated"></i> Nueva inscripción
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            <div class="db-hero-stats">
                <div class="db-hstat">
                    <span class="db-hstat-ico"><i class="bi bi-calendar-event"></i></span>
                    <span class="db-hstat-val stat-big-number"><?php echo (int)($metricas['eventos_activos'] ?? count($eventos)); ?></span>
                    <span class="db-hstat-lbl">Eventos activos</span>
                </div>
                <div class="db-hstat-div"></div>
                <div class="db-hstat">
                    <span class="db-hstat-ico"><i class="bi bi-people"></i></span>
                    <span class="db-hstat-val stat-big-number"><?php echo (int)($metricas['total_inscritos'] ?? 0); ?></span>
                    <span class="db-hstat-lbl">Inscritos</span>
                </div>
                <div class="db-hstat-div"></div>
                <div class="db-hstat">
                    <span class="db-hstat-ico"><i class="bi bi-check2-circle"></i></span>
                    <span class="db-hstat-val"><?php echo htmlspecialchars((string)($metricas['tasa_asistencia'] ?? '0%')); ?></span>
                    <span class="db-hstat-lbl">Asistencia</span>
                </div>
                <div class="db-hstat-div"></div>
                <div class="db-hstat">
                    <span class="db-hstat-ico"><i class="bi bi-award"></i></span>
                    <span class="db-hstat-val stat-big-number"><?php echo (int)($metricas['certificados'] ?? 0); ?></span>
                    <span class="db-hstat-lbl">Certificados</span>
                </div>
            </div>
        </div>
    </div>

    <!-- ══════════════════════════════════════════
         MAIN CONTENT
    ══════════════════════════════════════════ -->
    <div class="db-content">

        <!-- ── Benefits strip ─────────────────── -->
        <div class="db-features">
            <div class="db-feat">
                <span class="db-feat-ico"><i class="bi bi-lightning-charge-fill"></i></span>
                <div class="db-feat-txt"><strong>Inscripción rápida</strong><span>En segundos, sin filas</span></div>
            </div>
            <div class="db-feat">
                <span class="db-feat-ico"><i class="bi bi-qr-code"></i></span>
                <div class="db-feat-txt"><strong>Acceso con QR</strong><span>Tu entrada digital</span></div>
            </div>
            <div class="db-feat">
                <span class="db-feat-ico"><i class="bi bi-award-fill"></i></span>
                <div class="db-feat-txt"><strong>Certificados</strong><span>Descarga al instante</span></div>
            </div>
            <div class="db-feat">
                <span class="db-feat-ico"><i class="bi bi-broadcast"></i></span>
                <div class="db-feat-txt"><strong>Cupos en vivo</strong><span>Disponibilidad real</span></div>
            </div>
        </div>

        <!-- ── Explore section ────────────────── -->
        <section class="db-explore">
            <div class="db-explore-inner">
                <span class="db-kicker">Explorar</span>
                <h2 class="db-explore-title">¿Qué quieres vivir en Neiva?</h2>
                <p class="db-explore-sub">Descubre experiencias culturales, deportivas y educativas cerca de ti.</p>
                <form class="db-explore-search" action="/eventos" method="get" role="search">
                    <i class="bi bi-search"></i>
                    <input type="text" name="q" placeholder="Busca por evento, lugar o categoría…" aria-label="Buscar eventos">
                    <button type="submit"><i class="bi bi-compass"></i> Explorar</button>
                </form>
                <div class="db-explore-chips">
                    <a href="/eventos" class="db-chip db-chip--all"><i class="bi bi-grid-fill"></i> Todos</a>
                    <a href="/eventos?q=Cultural" class="db-chip"><i class="bi bi-palette-fill"></i> Cultural</a>
                    <a href="/eventos?q=Deportivo" class="db-chip"><i class="bi bi-trophy-fill"></i> Deportivo</a>
                    <a href="/eventos?q=Educativo" class="db-chip"><i class="bi bi-mortarboard-fill"></i> Educativo</a>
                    <a href="/calendario" class="db-chip"><i class="bi bi-calendar-month-fill"></i> Calendario</a>
                </div>
            </div>
        </section>

        <div class="db-grid">

            <!-- ── Events column ────────────────── -->
            <section class="db-card db-events-card">
                <div class="db-card-header">
                    <div>
                        <span class="db-kicker">Recomendados</span>
                        <h2 class="db-card-title">Eventos destacados</h2>
                    </div>
                    <a href="/calendario" class="db-btn-outline">
                        Ver agenda <i class="bi bi-arrow-right"></i>
                    </a>
                </div>

                <?php if (empty($eventosDestacados)): ?>
                    <div class="db-empty">
                        <i class="bi bi-calendar2-x"></i>
                        <strong>No hay eventos publicados</strong>
                        <p>Cuando existan eventos activos aparecerán aquí.</p>
                    </div>
                <?php else: ?>
                    <div class="db-events-grid" id="eventos-listados-dashboard">
                        <?php foreach ($eventosDestacados as $ev):
                            $evId      = (int)($ev['id'] ?? 0);
                            $titulo    = htmlspecialchars($ev['titulo'] ?? 'Evento');
                            $ubicacion = htmlspecialchars($ev['ubicacion'] ?? 'Por confirmar');
                            $fecha     = $ev['fecha_evento'] ?? null;
                            $hora      = $ev['hora_evento'] ?? '';
                            $cat       = $ev['categoria'] ?? 'General';
                            $cupoMax   = max(1, (int)($ev['cupo_maximo'] ?? 1));
                            $inscritos = (int)($ev['inscritos_actuales'] ?? 0);
                            $cupos     = max(0, $cupoMax - $inscritos);
                            $pct       = min(100, round($inscritos / $cupoMax * 100));
                            $inscrito  = in_array($evId, $inscritosIds, true);
                            $lleno     = $cupos <= 0;
                            $imgReal   = !empty($ev['ruta_imagen']) ? (strpos($ev['ruta_imagen'], '/') === 0 ? $ev['ruta_imagen'] : '/'.ltrim($ev['ruta_imagen'],'/')) : null;
                            $catColor  = match($cat) {
                                'Cultural'  => 'purple',
                                'Deportivo' => 'blue',
                                'Educativo' => 'teal',
                                default     => 'orange'
                            };
                            $canEnroll = !$inscrito && !$lleno && in_array($rol, ['cliente','participante','organizador','admin'], true);
                        ?>
                        <article class="db-ev-card<?php echo $inscrito ? ' db-ev-inscrito' : ''; ?>"
                                 data-event-id="<?php echo $evId; ?>"
                                 data-inscritos="<?php echo $inscritos; ?>"
                                 data-cupos="<?php echo $cupos; ?>">

                            <a class="db-ev-img db-ev-ph-<?php echo $catColor; ?>" href="/dashboard?view=detalle_evento&id=<?php echo $evId; ?>" aria-label="Ver detalles de <?php echo $titulo; ?>">
                                <div class="db-ev-img-ph"><i class="bi <?php echo $catIcon($cat); ?>"></i></div>
                                <?php if ($imgReal): ?>
                                    <img src="<?php echo $imgReal; ?>" alt="<?php echo $titulo; ?>" loading="lazy" onerror="this.remove()">
                                <?php endif; ?>
                                <!-- Overlays -->
                                <span class="db-ev-cat">
                                    <i class="bi <?php echo $catIcon($cat); ?>"></i> <?php echo htmlspecialchars($cat); ?>
                                </span>
                                <?php if ($inscrito): ?>
                                    <span class="db-ev-badge db-ev-badge--ok"><i class="bi bi-check2-circle"></i> Inscrito</span>
                                <?php elseif ($lleno): ?>
                                    <span class="db-ev-badge db-ev-badge--err"><i class="bi bi-x-circle"></i> Lleno</span>
                                <?php else: ?>
                                    <span class="db-ev-pill"><i class="bi bi-people-fill"></i> <span class="card-cupos"><?php echo $cupos; ?></span> cupos</span>
                                <?php endif; ?>
                            </a>

                            <div class="db-ev-body">
                                <a class="db-ev-title-link" href="/dashboard?view=detalle_evento&id=<?php echo $evId; ?>">
                                    <h3 class="db-ev-title"><?php echo $titulo; ?></h3>
                                </a>
                                <div class="db-ev-meta">
                                    <span><i class="bi bi-geo-alt-fill"></i> <?php echo $ubicacion; ?></span>
                                    <span><i class="bi bi-calendar3"></i> <?php echo htmlspecialchars(trim($fmtFecha($fecha).' '.$fmtHora($hora))); ?></span>
                                </div>
                                <div class="db-ev-capacity">
                                    <div class="db-ev-bar-wrap">
                                        <div class="db-ev-bar" style="width:<?php echo $pct; ?>%"></div>
                                    </div>
                                    <span class="db-ev-cap-txt">
                                        <span class="card-inscritos"><?php echo $inscritos; ?></span> /
                                        <?php echo $cupoMax; ?> inscritos
                                    </span>
                                </div>
                            </div>

                            <div class="db-ev-footer">
                                <div class="db-ev-price">
                                    <span class="db-ev-price-lbl">Entrada</span>
                                    <strong>Gratis</strong>
                                </div>
                                <button type="button"
                                        class="db-ev-btn-enroll<?php echo $inscrito ? ' is-registered' : ($lleno ? ' is-full' : ''); ?>"
                                        data-action="inscribir-evento"
                                        <?php echo !$canEnroll ? 'disabled' : ''; ?>>
                                    <?php if ($inscrito): ?>
                                        <i class="bi bi-check2-circle"></i> Ya inscrito
                                    <?php elseif ($lleno): ?>
                                        <i class="bi bi-x-circle"></i> Lleno
                                    <?php else: ?>
                                        <i class="bi bi-ticket-perforated"></i> Inscribirme
                                    <?php endif; ?>
                                </button>
                            </div>
                        </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>

            <!-- ── Sidebar ───────────────────────── -->
            <aside class="db-sidebar">

                <!-- Quick actions -->
                <div class="db-card db-quick-card">
                    <div class="db-card-header">
                        <span class="db-kicker">Actividad</span>
                        <h2 class="db-card-title">Accesos rápidos</h2>
                    </div>
                    <nav class="db-quick-nav">
                        <a href="/dashboard?view=mis_eventos_inscritos" class="db-quick-item">
                            <span class="db-qi-icon db-qi-orange"><i class="bi bi-calendar-check-fill"></i></span>
                            <span class="db-qi-label">Mis eventos</span>
                        </a>
                        <a href="/dashboard?view=mis_qr" class="db-quick-item">
                            <span class="db-qi-icon db-qi-blue"><i class="bi bi-qr-code-scan"></i></span>
                            <span class="db-qi-label">Mis QR</span>
                        </a>
                        <a href="/dashboard?view=mis_certificados" class="db-quick-item">
                            <span class="db-qi-icon db-qi-purple"><i class="bi bi-award-fill"></i></span>
                            <span class="db-qi-label">Certificados</span>
                        </a>
                        <a href="/calendario" class="db-quick-item">
                            <span class="db-qi-icon db-qi-teal"><i class="bi bi-calendar-month-fill"></i></span>
                            <span class="db-qi-label">Calendario</span>
                        </a>
                        <?php if (in_array($rol, ['admin','organizador'], true)): ?>
                        <a href="/dashboard?view=asistencia" class="db-quick-item db-quick-item--primary db-quick-item--wide">
                            <span class="db-qi-icon db-qi-white"><i class="bi bi-upc-scan"></i></span>
                            <span class="db-qi-label">Validar asistencia</span>
                            <i class="bi bi-chevron-right db-qi-arrow"></i>
                        </a>
                        <?php endif; ?>
                    </nav>
                </div>

                <!-- CTA card for guests -->
                <?php if ($esGuest): ?>
                <div class="db-cta-card">
                    <div class="db-cta-icon"><i class="bi bi-person-plus-fill"></i></div>
                    <h3>Únete a NeivActiva</h3>
                    <p>Crea tu cuenta gratis y accede a inscripciones, QR y certificados digitales.</p>
                    <a href="/register" class="db-btn-primary db-btn-full">
                        <i class="bi bi-person-plus-fill"></i> Crear cuenta gratis
                    </a>
                    <a href="/login" class="db-btn-outline db-btn-full" style="margin-top:.5rem;">
                        <i class="bi bi-box-arrow-in-right"></i> Iniciar sesión
                    </a>
                </div>
                <?php endif; ?>

            </aside>
        </div>
    </div>
</main>

<!-- ── Toast stack ──────────────────────────────── -->
<div id="dashboardToast" class="db-toast-stack" aria-live="polite"></div>

<!-- ── Profile modal ────────────────────────────── -->
<div class="db-modal-overlay" id="eventProfileModal" aria-hidden="true">
    <div class="db-modal">
        <button type="button" class="db-modal-close" data-close-profile-modal>
            <i class="bi bi-x-lg"></i>
        </button>
        <div class="db-modal-icon"><i class="bi bi-person-badge-fill"></i></div>
        <h3>Completa tus datos</h3>
        <p>Necesitamos estos datos para generar tu inscripción y QR.</p>
        <form id="eventProfileForm" class="db-modal-form">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken ?? ''); ?>">
            <input type="hidden" name="evento_id" id="profileEventoId">
            <div class="db-modal-field">
                <label>Nombre completo</label>
                <input type="text" name="nombre_completo" id="profileNombre"
                       value="<?php echo htmlspecialchars($participante_sesion['nombre_completo'] ?? $nombreUsuario); ?>" required>
            </div>
            <div class="db-modal-field">
                <label>Documento de identidad</label>
                <input type="text" name="documento_identidad" id="profileDocumento"
                       value="<?php echo htmlspecialchars($participante_sesion['documento_identidad'] ?? ''); ?>" required>
            </div>
            <div class="db-modal-field">
                <label>Teléfono</label>
                <input type="text" name="telefono" id="profileTelefono"
                       value="<?php echo htmlspecialchars($participante_sesion['telefono'] ?? ''); ?>" required>
            </div>
            <button type="submit" class="db-btn-primary db-btn-full">
                <i class="bi bi-qr-code"></i> Confirmar inscripción
            </button>
        </form>
    </div>
</div>

<script src="/assets/js/dashboard.js"></script>
</body>
</html>
