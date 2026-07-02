<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NeivActiva - Mis Eventos Inscritos</title>
    <link rel="stylesheet" href="/NeivActiva/public/assets/css/neivactiva-2026.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/NeivActiva/public/assets/css/views/mis_eventos_inscritos.css">
</head>
<body class="events-body">

<?php include 'partials/sidebar.php'; ?>

<main class="main-wrapper">
    <!-- Header Premium -->
    <header class="premium-header">
        <div class="header-content">
            <div class="header-text">
                <h1 class="page-title">Mis Eventos Inscritos</h1>
                <p class="page-subtitle">Gestiona y visualiza todos tus eventos en un solo lugar</p>
            </div>
            <div class="header-actions">
                <div class="search-modern">
                    <i class="bi bi-search"></i>
                    <input type="text" id="searchInput" placeholder="Buscar eventos..." value="<?php echo htmlspecialchars($filters['search'] ?? ''); ?>">
                </div>
                <button class="btn btn-primary btn-export" onclick="exportEvents()">
                    <i class="bi bi-download"></i>
                    <span>Exportar</span>
                </button>
                <button class="btn btn-secondary btn-dark-mode" onclick="toggleDarkMode()">
                    <i class="bi bi-moon" id="darkModeIcon"></i>
                </button>
            </div>
        </div>
        
        <!-- Filtros Modernos -->
        <div class="filters-modern">
            <div class="filter-pills">
                <button class="filter-pill active" data-status="all">
                    <i class="bi bi-grid"></i>
                    <span>Todos</span>
                    <span class="count"><?php echo count($events); ?></span>
                </button>
                <button class="filter-pill" data-status="upcoming">
                    <i class="bi bi-calendar-check"></i>
                    <span>Próximos</span>
                    <span class="count"><?php echo $metrics['proximos'] ?? 0; ?></span>
                </button>
                <button class="filter-pill" data-status="completed">
                    <i class="bi bi-check-circle"></i>
                    <span>Completados</span>
                    <span class="count"><?php echo $metrics['completados'] ?? 0; ?></span>
                </button>
                <button class="filter-pill" data-status="cancelled">
                    <i class="bi bi-x-circle"></i>
                    <span>Cancelados</span>
                    <span class="count">0</span>
                </button>
                <button class="filter-pill" data-status="certificate">
                    <i class="bi bi-award"></i>
                    <span>Certificados</span>
                    <span class="count"><?php echo $metrics['certificados_disponibles'] ?? 0; ?></span>
                </button>
            </div>
        </div>
    </header>

    <div class="dashboard-content">
        <!-- KPIs Horizontales Premium -->
        <div class="kpi-horizontal">
            <div class="kpi-card-horizontal skeleton">
                <div class="kpi-icon-wrapper total">
                    <i class="bi bi-calendar-event"></i>
                </div>
                <div class="kpi-info">
                    <div class="kpi-value"><?php echo number_format($metrics['total_inscritos'] ?? 0); ?></div>
                    <div class="kpi-label">Total Inscritos</div>
                    <div class="kpi-trend positive">
                        <i class="bi bi-arrow-up"></i>
                        <span>Todos los tiempos</span>
                    </div>
                </div>
            </div>

            <div class="kpi-card-horizontal skeleton">
                <div class="kpi-icon-wrapper upcoming">
                    <i class="bi bi-clock"></i>
                </div>
                <div class="kpi-info">
                    <div class="kpi-value"><?php echo number_format($metrics['proximos'] ?? 0); ?></div>
                    <div class="kpi-label">Próximos Eventos</div>
                    <div class="kpi-trend neutral">
                        <i class="bi bi-calendar"></i>
                        <span>Por venir</span>
                    </div>
                </div>
            </div>

            <div class="kpi-card-horizontal skeleton">
                <div class="kpi-icon-wrapper completed">
                    <i class="bi bi-check-circle"></i>
                </div>
                <div class="kpi-info">
                    <div class="kpi-value"><?php echo number_format($metrics['completados'] ?? 0); ?></div>
                    <div class="kpi-label">Completados</div>
                    <div class="kpi-trend success">
                        <i class="bi bi-trophy"></i>
                        <span>Finalizados</span>
                    </div>
                </div>
            </div>

            <div class="kpi-card-horizontal skeleton">
                <div class="kpi-icon-wrapper certificate">
                    <i class="bi bi-award"></i>
                </div>
                <div class="kpi-info">
                    <div class="kpi-value"><?php echo number_format($metrics['certificados_disponibles'] ?? 0); ?></div>
                    <div class="kpi-label">Certificados</div>
                    <div class="kpi-trend info">
                        <i class="bi bi-download"></i>
                        <span>Disponibles</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Grid de Eventos Premium -->
        <div class="events-section">
            <div class="section-header">
                <h2 class="section-title">Tus Eventos</h2>
                <div class="section-actions">
                    <button class="btn btn-secondary btn-sm" onclick="toggleView('grid')">
                        <i class="bi bi-grid"></i>
                    </button>
                    <button class="btn btn-secondary btn-sm" onclick="toggleView('list')">
                        <i class="bi bi-list"></i>
                    </button>
                </div>
            </div>

            <?php if (($metrics['proximos'] ?? 0) === 0 && ($metrics['total_inscritos'] ?? 0) >= 0): ?>
            <div class="mei-info-callout">
                <div class="mei-callout-icon"><i class="bi bi-info-circle-fill"></i></div>
                <div class="mei-callout-body">
                    <strong>¿No ves eventos próximos?</strong>
                    <p>Esta sección muestra solo los eventos en los que <em>te has inscrito</em>. Si publicaste un evento como organizador, búscalo en el catálogo e inscríbete para verlo aquí.</p>
                </div>
                <a href="/NeivActiva/public/eventos" class="mei-callout-btn">
                    <i class="bi bi-calendar-event"></i> Ver eventos disponibles
                </a>
            </div>
            <?php endif; ?>

            <div class="events-grid-modern" id="eventsGrid">
                <?php if (!empty($events)): ?>
                    <?php foreach ($events as $event): ?>
                        <?php 
                            $status = $event['status_info'];
                            $imagePath = $event['ruta_imagen'] ? '/NeivActiva/' . ltrim($event['ruta_imagen'], '/') : '/NeivActiva/public/assets/images/event-placeholder.jpg';
                            $fechaFormateada = date('d M', strtotime($event['fecha_evento']));
                            $horaFormateada = $event['hora_evento'] ? date('H:i', strtotime($event['hora_evento'])) : '--:--';
                            $year = date('Y', strtotime($event['fecha_evento']));
                        ?>
                        <div class="event-card-modern skeleton">
                            <div class="event-card-header">
                                <div class="event-date-badge">
                                    <span class="date-day"><?php echo $fechaFormateada; ?></span>
                                    <span class="date-year"><?php echo $year; ?></span>
                                </div>
                                <div class="event-status-badge <?php echo $status['class']; ?>">
                                    <i class="bi <?php echo $status['icon']; ?>"></i>
                                    <?php echo $status['label']; ?>
                                </div>
                            </div>
                            
                            <div class="event-card-body">
                                <div class="event-category-modern">
                                    <span class="category-dot <?php echo strtolower($event['categoria']); ?>"></span>
                                    <span class="category-text"><?php echo $event['categoria']; ?></span>
                                </div>
                                
                                <h3 class="event-title-modern"><?php echo htmlspecialchars($event['titulo']); ?></h3>
                                
                                <div class="event-details-modern">
                                    <div class="detail-item">
                                        <i class="bi bi-clock"></i>
                                        <span><?php echo $horaFormateada; ?></span>
                                    </div>
                                    <div class="detail-item">
                                        <i class="bi bi-geo-alt"></i>
                                        <span><?php echo htmlspecialchars($event['ubicacion'] ?? 'Por definir'); ?></span>
                                    </div>
                                </div>
                                
                                <?php if ($event['days_remaining'] > 0): ?>
                                    <div class="event-countdown-modern">
                                        <div class="countdown-item">
                                            <span class="countdown-value"><?php echo $event['days_remaining']; ?></span>
                                            <span class="countdown-label">días</span>
                                        </div>
                                        <div class="countdown-divider"></div>
                                        <div class="countdown-item">
                                            <span class="countdown-value">--</span>
                                            <span class="countdown-label">horas</span>
                                        </div>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="event-occupancy-modern">
                                    <div class="occupancy-header">
                                        <span class="occupancy-label">Ocupación</span>
                                        <span class="occupancy-percentage"><?php echo $event['occupancy_rate']; ?>%</span>
                                    </div>
                                    <div class="occupancy-track">
                                        <div class="occupancy-progress" style="width: <?php echo min($event['occupancy_rate'], 100); ?>%"></div>
                                    </div>
                                    <div class="occupancy-footer">
                                        <span><?php echo number_format($event['inscritos_actuales']); ?> inscritos</span>
                                        <span>de <?php echo number_format($event['cupo_maximo']); ?> cupos</span>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="event-card-footer">
                                <?php if ($event['has_certificate']): ?>
                                    <button class="btn btn-certificate" onclick="downloadCertificate(<?php echo $event['inscripcion_id']; ?>)">
                                        <i class="bi bi-award"></i>
                                        <span>Certificado</span>
                                    </button>
                                <?php endif; ?>
                                <button class="btn btn-view" onclick="viewEventDetails(<?php echo $event['evento_id']; ?>)">
                                    <i class="bi bi-eye"></i>
                                    <span>Ver detalles</span>
                                </button>
                                <?php if ($event['ruta_qr']): ?>
                                    <button class="btn btn-qr" onclick="viewQR(<?php echo $event['inscripcion_id']; ?>)">
                                        <i class="bi bi-qr-code"></i>
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-state-modern">
                        <div class="empty-illustration">
                            <i class="bi bi-calendar-x"></i>
                        </div>
                        <h3>No tienes eventos inscritos</h3>
                        <p>Explora los eventos disponibles y comienza a participar en la comunidad.</p>
                        <a href="?view=landing" class="btn btn-primary btn-lg">
                            <i class="bi bi-plus-circle"></i>
                            Explorar Eventos
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Timeline de Actividad -->
        <div class="timeline-section">
            <div class="section-header">
                <h2 class="section-title">
                    <i class="bi bi-clock-history"></i>
                    Actividad Reciente
                </h2>
            </div>
            <div class="timeline-modern">
                <?php if (!empty($timeline)): ?>
                    <?php foreach ($timeline as $activity): ?>
                        <div class="timeline-item-modern">
                            <div class="timeline-dot <?php echo $activity['tipo']; ?>"></div>
                            <div class="timeline-content-modern">
                                <div class="timeline-header">
                                    <h4><?php echo htmlspecialchars($activity['titulo']); ?></h4>
                                    <span class="timeline-time"><?php echo date('d M Y - H:i', strtotime($activity['fecha'])); ?></span>
                                </div>
                                <p><?php echo $activity['descripcion']; ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="timeline-empty-modern">
                        <p>Aún no tienes actividad registrada</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Estadísticas Personales -->
        <div class="stats-section-modern">
            <div class="section-header">
                <h2 class="section-title">
                    <i class="bi bi-graph-up"></i>
                    Tu Participación
                </h2>
            </div>
            <div class="stats-grid-modern">
                <div class="stat-card-modern">
                    <div class="stat-icon">
                        <i class="bi bi-check-circle"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-value"><?php echo $attendanceRate; ?>%</div>
                        <div class="stat-label">Tasa de Asistencia</div>
                    </div>
                    <div class="stat-progress">
                        <div class="stat-progress-bar" style="width: <?php echo $attendanceRate; ?>%"></div>
                    </div>
                </div>
                
                <?php if (!empty($popularCategories)): ?>
                    <div class="stat-card-modern">
                        <div class="stat-icon">
                            <i class="bi bi-star"></i>
                        </div>
                        <div class="stat-content">
                            <div class="stat-label">Categorías Favoritas</div>
                            <div class="categories-list-modern">
                                <?php foreach ($popularCategories as $cat): ?>
                                    <span class="category-pill"><?php echo htmlspecialchars($cat['categoria']); ?></span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>

<script>
// Datos desde PHP
const currentStatus = '<?php echo $filters['status'] ?? 'all'; ?>';

// Filtros
document.querySelectorAll('.filter-pill').forEach(pill => {
    pill.addEventListener('click', function() {
        document.querySelectorAll('.filter-pill').forEach(p => p.classList.remove('active'));
        this.classList.add('active');
        
        const status = this.dataset.status;
        applyFilter(status);
    });
});

// Buscador
document.getElementById('searchInput')?.addEventListener('input', function() {
    const search = this.value;
    applySearch(search);
});

function applyFilter(status) {
    const url = new URL(window.location);
    url.searchParams.set('status', status);
    url.searchParams.delete('search');
    window.location.href = url.toString();
}

function applySearch(search) {
    const url = new URL(window.location);
    url.searchParams.set('search', search);
    url.searchParams.delete('status');
    
    clearTimeout(window.searchTimeout);
    window.searchTimeout = setTimeout(() => {
        window.location.href = url.toString();
    }, 500);
}

// Toggle vista
function toggleView(view) {
    const grid = document.getElementById('eventsGrid');
    if (view === 'list') {
        grid.classList.add('list-view');
    } else {
        grid.classList.remove('list-view');
    }
}

// Modo oscuro
function toggleDarkMode() {
    document.body.classList.toggle('dark-mode');
    const icon = document.getElementById('darkModeIcon');
    if (icon) {
        icon.className = document.body.classList.contains('dark-mode') ? 'bi bi-sun' : 'bi bi-moon';
    }
    localStorage.setItem('darkMode', document.body.classList.contains('dark-mode'));
}

// Acciones
function viewEventDetails(eventId) {
    window.location.href = `?view=detalle_evento&id=${eventId}`;
}

function downloadCertificate(inscripcionId) {
    window.location.href = `?view=mis_certificados&descargar=${inscripcionId}`;
}

function viewQR(inscripcionId) {
    window.location.href = `?view=mis_qr`;
}

function exportEvents() {
    let csvContent = "data:text/csv;charset=utf-8,";
    csvContent += "Evento,Fecha,Hora,Ubicación,Categoría,Estado,Inscripción\n";
    
    <?php if (!empty($events)): ?>
        <?php foreach ($events as $event): ?>
            csvContent += `"<?php echo addslashes($event['titulo']); ?>","<?php echo $event['fecha_evento']; ?>","<?php echo $event['hora_evento'] ?? '--:--'; ?>","<?php echo addslashes($event['ubicacion'] ?? 'Por definir'); ?>","<?php echo $event['categoria']; ?>","<?php echo $event['status_info']['label']; ?>","<?php echo date('d/m/Y', strtotime($event['fecha_inscripcion'])); ?>"\n`;
        <?php endforeach; ?>
    <?php endif; ?>
    
    const encodedUri = encodeURI(csvContent);
    const link = document.createElement("a");
    link.setAttribute("href", encodedUri);
    link.setAttribute("download", `mis_eventos_${new Date().toISOString().split('T')[0]}.csv`);
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

// Skeleton loading
window.addEventListener('load', function() {
    setTimeout(() => {
        document.querySelectorAll('.skeleton').forEach(el => {
            el.classList.remove('skeleton');
        });
    }, 300);
});

// Cargar modo oscuro
if (localStorage.getItem('darkMode') === 'true') {
    document.body.classList.add('dark-mode');
    const icon = document.getElementById('darkModeIcon');
    if (icon) icon.className = 'bi bi-sun';
}

// Marcar filtro activo
if (currentStatus) {
    document.querySelector(`.filter-pill[data-status="${currentStatus}"]`)?.classList.add('active');
}
</script>
</body>
</html>

