<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NeivActiva - Calendario de Eventos</title>
    <link rel="stylesheet" href="/assets/css/neivactiva-2026.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- FullCalendar CSS -->
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js'></script>
    <script>
        // Verificar que FullCalendar se cargó correctamente
        if (typeof FullCalendar === 'undefined') {
            console.error('FullCalendar no se cargó correctamente');
        }
    </script>
    <link rel="stylesheet" href="/assets/css/views/calendario.css">
</head>
<body class="calendar-body">

<?php include ROOT_PATH . '/resources/views/partials/sidebar.php'; ?>

<main class="main-wrapper">
    <!-- Header Premium -->
    <header class="calendar-header">
        <div class="header-content">
            <div class="header-text">
                <h1 class="page-title">Calendario de Eventos</h1>
                <p class="page-subtitle">Descubre y explora todos los eventos de Neiva</p>
            </div>
            <div class="header-actions">
                <div class="search-modern">
                    <i class="bi bi-search"></i>
                    <input type="text" id="searchInput" placeholder="Buscar eventos..." value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>">
                </div>
            </div>
        </div>
        
        <!-- Filtros Avanzados -->
        <div class="filters-advanced">
            <div class="filter-group">
                <label class="filter-label">Categoría</label>
                <select id="categoryFilter" class="filter-select">
                    <option value="">Todas</option>
                    <?php if (isset($categories) && is_array($categories)): ?>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo htmlspecialchars($cat); ?>" <?php echo ($_GET['category'] ?? '') === $cat ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cat); ?>
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>
            
            <div class="filter-group">
                <label class="filter-label">Estado</label>
                <select id="statusFilter" class="filter-select">
                    <option value="">Todos</option>
                    <option value="upcoming" <?php echo isset($_GET['upcoming_only']) ? 'selected' : ''; ?>>Próximos</option>
                    <option value="free">Gratuitos</option>
                </select>
            </div>
            
            <button class="btn-reset-filters" onclick="resetFilters()">
                <i class="bi bi-arrow-counterclockwise"></i> Resetear
            </button>
        </div>
    </header>

    <div class="calendar-layout">
        <!-- Calendario Principal -->
        <div class="calendar-main">
            <div class="calendar-stats">
                <div class="stat-item">
                    <span class="stat-ico stat-ico--total"><i class="bi bi-calendar3-event"></i></span>
                    <div class="stat-body">
                        <div class="stat-value"><?php echo isset($stats) ? number_format($stats['total_events'] ?? 0) : '0'; ?></div>
                        <div class="stat-label">Total Eventos</div>
                    </div>
                </div>
                <div class="stat-item">
                    <span class="stat-ico stat-ico--up"><i class="bi bi-clock-history"></i></span>
                    <div class="stat-body">
                        <div class="stat-value"><?php echo isset($stats) ? number_format($stats['upcoming_events'] ?? 0) : '0'; ?></div>
                        <div class="stat-label">Próximos</div>
                    </div>
                </div>
                <div class="stat-item">
                    <span class="stat-ico stat-ico--free"><i class="bi bi-gift"></i></span>
                    <div class="stat-body">
                        <div class="stat-value"><?php echo isset($stats) ? number_format($stats['free_events'] ?? 0) : '0'; ?></div>
                        <div class="stat-label">Gratuitos</div>
                    </div>
                </div>
            </div>
            
            <div id="calendar"></div>
        </div>
        
        <!-- Panel Lateral -->
        <aside class="calendar-sidebar">
            <!-- Próximos Eventos -->
            <div class="sidebar-section">
                <h3 class="sidebar-title">
                    <i class="bi bi-star"></i> Eventos Destacados
                </h3>
                <div class="featured-events">
                    <?php if (isset($featuredEvents) && !empty($featuredEvents)): ?>
                        <?php foreach ($featuredEvents as $event): ?>
                            <?php 
                                $imagePath = $event['ruta_imagen'] ? (strpos($event['ruta_imagen'], '/') === 0 ? $event['ruta_imagen'] : '/' . ltrim($event['ruta_imagen'], '/')) : '/assets/images/event-placeholder.jpg';
                                $fechaFormateada = date('d M', strtotime($event['fecha_evento']));
                            ?>
                            <div class="featured-event-card" onclick="openEventModal(<?php echo $event['id']; ?>)">
                                <img src="<?php echo htmlspecialchars($imagePath); ?>" alt="<?php echo htmlspecialchars($event['titulo']); ?>" onerror="this.src='/assets/images/event-placeholder.jpg'">
                                <div class="featured-info">
                                    <span class="featured-date"><?php echo $fechaFormateada; ?></span>
                                    <h4><?php echo htmlspecialchars($event['titulo']); ?></h4>
                                    <span class="featured-category"><?php echo htmlspecialchars($event['categoria']); ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="empty-sidebar">No hay eventos destacados</p>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Categorías Populares -->
            <div class="sidebar-section">
                <h3 class="sidebar-title">
                    <i class="bi bi-tags"></i> Categorías Populares
                </h3>
                <div class="popular-categories">
                    <?php if (isset($popularCategories) && !empty($popularCategories)): ?>
                        <?php foreach ($popularCategories as $cat): ?>
                            <div class="category-item" onclick="filterByCategory('<?php echo htmlspecialchars($cat['categoria']); ?>')">
                                <span class="category-name"><?php echo htmlspecialchars($cat['categoria']); ?></span>
                                <span class="category-count"><?php echo $cat['event_count']; ?></span>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="empty-sidebar">No hay datos disponibles</p>
                    <?php endif; ?>
                </div>
            </div>
        </aside>
    </div>
</main>

<!-- Modal de Evento -->
<div class="event-modal-overlay" id="eventModal" onclick="if(event.target === this) closeEventModal()">
    <div class="event-modal">
        <button class="modal-close" onclick="closeEventModal()">
            <i class="bi bi-x"></i>
        </button>
        
        <div class="modal-image" id="modalImage">
            <img src="" alt="Evento" id="modalImg">
        </div>
        
        <div class="modal-content">
            <div class="modal-header">
                <span class="modal-category" id="modalCategory">Categoría</span>
                <h2 class="modal-title" id="modalTitle">Título del Evento</h2>
            </div>
            
            <div class="modal-meta">
                <div class="meta-item">
                    <i class="bi bi-calendar-event"></i>
                    <span id="modalDate">Fecha</span>
                </div>
                <div class="meta-item">
                    <i class="bi bi-clock"></i>
                    <span id="modalTime">Hora</span>
                </div>
                <div class="meta-item">
                    <i class="bi bi-geo-alt"></i>
                    <span id="modalLocation">Ubicación</span>
                </div>
                <div class="meta-item">
                    <i class="bi bi-people"></i>
                    <span id="modalCapacity">Cupos</span>
                </div>
            </div>
            
            <p class="modal-description" id="modalDescription">Descripción del evento...</p>
            
            <div class="modal-actions">
                <a href="#" class="btn btn-primary" id="modalRegisterBtn">
                    <i class="bi bi-ticket-perforated"></i> Inscribirme
                </a>
                <button class="btn btn-secondary" onclick="shareEvent()">
                    <i class="bi bi-share"></i> Compartir
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// Configuración del calendario
let calendar;
const API_BASE = '?view=';

document.addEventListener('DOMContentLoaded', function() {
    const calendarEl = document.getElementById('calendar');
    
    if (calendarEl && typeof FullCalendar !== 'undefined') {
        calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'dayGridMonth',
            locale: 'es',
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,timeGridWeek,timeGridDay'
            },
            buttonText: {
                today: 'Hoy',
                month: 'Mes',
                week: 'Semana',
                day: 'Día'
            },
            eventDisplay: 'block',
            dayMaxEvents: 3,
            events: function(info, successCallback, failureCallback) {
                // Cargar eventos dinámicamente vía AJAX
                const start = info.start.toISOString().split('T')[0];
                const end = info.end.toISOString().split('T')[0];
                const category = document.getElementById('categoryFilter')?.value || '';
                const status = document.getElementById('statusFilter')?.value || '';
                const search = document.getElementById('searchInput')?.value || '';
                
                let url = API_BASE + 'api_calendar_events';
                url += '&start=' + start;
                url += '&end=' + end;
                if (category) url += '&category=' + category;
                if (status === 'upcoming') url += '&upcoming_only=1';
                if (status === 'free') url += '&free_only=1';
                if (search) url += '&search=' + search;
                
                fetch(url)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            successCallback(data.events);
                        } else {
                            console.error('Error cargando eventos:', data.error);
                            successCallback([]);
                        }
                    })
                    .catch(error => {
                        console.error('Error en fetch:', error);
                        successCallback([]);
                    });
            },
            eventClick: function(info) {
                openEventModal(info.event);
            },
            eventMouseEnter: function(info) {
                // Tooltip personalizado
            },
            height: 'auto',
            eventDidMount: function(info) {
                // Personalizar apariencia de eventos
            },
            loading: function(isLoading) {
                // Mostrar/ocultar skeleton loading
                if (isLoading) {
                    document.getElementById('calendar').classList.add('loading');
                } else {
                    document.getElementById('calendar').classList.remove('loading');
                }
            }
        });
        
        calendar.render();
    }
    
    // Cargar filtros guardados
    loadFilters();
});

// Filtros dinámicos sin recargar página
document.getElementById('categoryFilter')?.addEventListener('change', refreshCalendar);
document.getElementById('statusFilter')?.addEventListener('change', refreshCalendar);
document.getElementById('searchInput')?.addEventListener('input', debounce(refreshCalendar, 500));

// View toggles
document.querySelectorAll('.view-toggle').forEach(btn => {
    btn.addEventListener('click', function() {
        document.querySelectorAll('.view-toggle').forEach(b => b.classList.remove('active'));
        this.classList.add('active');
        calendar.changeView(this.dataset.view);
    });
});

function refreshCalendar() {
    if (calendar) {
        calendar.refetchEvents();
    }
}

function applyFilters() {
    refreshCalendar();
}

function resetFilters() {
    document.getElementById('categoryFilter').value = '';
    document.getElementById('statusFilter').value = '';
    document.getElementById('searchInput').value = '';
    refreshCalendar();
}

function filterByCategory(category) {
    document.getElementById('categoryFilter').value = category;
    refreshCalendar();
}

function loadFilters() {
    // Los filtros ya están aplicados desde PHP
}

// Modal
function openEventModal(eventOrId) {
    let eventId;
    
    if (typeof eventOrId === 'object') {
        eventId = (eventOrId.event && eventOrId.event.id) ? eventOrId.event.id : eventOrId.id;
    } else {
        eventId = eventOrId;
    }
    
    // Cargar datos del evento desde la API
    const url = API_BASE + 'api_event_by_id&id=' + eventId;
    
    fetch(url)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.event) {
                const event = data.event;
                document.getElementById('modalImg').src = event.ruta_imagen ? (event.ruta_imagen.startsWith('/') ? event.ruta_imagen : '/' + event.ruta_imagen.replace(/^\/+/, '')) : '/assets/images/event-placeholder.jpg';
                document.getElementById('modalCategory').textContent = event.categoria || 'Otro';
                document.getElementById('modalTitle').textContent = event.titulo;
                document.getElementById('modalDate').textContent = new Date(event.fecha_evento).toLocaleDateString('es-ES', { 
                    weekday: 'long', 
                    year: 'numeric', 
                    month: 'long', 
                    day: 'numeric' 
                });
                document.getElementById('modalTime').textContent = event.hora_evento ? new Date('1970-01-01T' + event.hora_evento).toLocaleTimeString('es-ES', { 
                    hour: '2-digit', 
                    minute: '2-digit' 
                }) : 'Por confirmar';
                document.getElementById('modalLocation').textContent = event.ubicacion || 'Por definir';
                document.getElementById('modalCapacity').textContent = `${event.inscritos_actuales} / ${event.cupo_maximo} cupos`;
                document.getElementById('modalDescription').textContent = event.descripcion || 'Sin descripción';
                document.getElementById('modalRegisterBtn').href = `?view=detalle_evento&id=${eventId}`;
                
                const modal = document.getElementById('eventModal');
                modal.style.display = 'flex';
                setTimeout(() => modal.classList.add('active'), 10);
            } else {
                console.error('Error cargando evento:', data.error);
                alert('No se pudo cargar la información del evento');
            }
        })
        .catch(error => {
            console.error('Error en fetch:', error);
            alert('Error al cargar el evento');
        });
}

function closeEventModal() {
    const modal = document.getElementById('eventModal');
    modal.classList.remove('active');
    setTimeout(() => modal.style.display = 'none', 300);
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

// Modo oscuro eliminado: limpiar cualquier estado previo guardado.
localStorage.removeItem('darkMode');
document.body.classList.remove('dark-mode');

// Exportar
function exportCalendar() {
    alert('Exportando calendario...');
}

// Compartir
function shareCalendar() {
    if (navigator.share) {
        navigator.share({
            title: 'Calendario de Eventos - NeivActiva',
            url: window.location.href
        });
    } else {
        navigator.clipboard.writeText(window.location.href);
        alert('Enlace copiado al portapapeles');
    }
}

function shareEvent() {
    if (navigator.share) {
        navigator.share({
            title: document.getElementById('modalTitle').textContent,
            url: window.location.href
        });
    }
}

// Debounce
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// Cerrar modal con ESC
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        closeEventModal();
    }
});
</script>
<script src="/assets/js/input-rules.js"></script>
</body>
</html>

