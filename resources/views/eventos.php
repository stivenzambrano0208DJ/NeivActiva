<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NeivActiva - Eventos</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=Outfit:wght@600;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/neivactiva-2026.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body class="public-shell">
    <div class="ambient-bg"></div>
    <nav class="navbar">
        <div class="container navbar-content">
            <a href="/" class="brand-logo">
                <i class="bi bi-lightning-charge-fill"></i>
                <span>NeivActiva</span>
            </a>
            <ul class="nav-links">
                <li><a href="/">Inicio</a></li>
                <li><a href="/calendario">Calendario</a></li>
                <li><a href="/login">Iniciar sesion</a></li>
                <li><a href="/register" class="btn btn-primary btn-sm">Crear cuenta</a></li>
            </ul>
        </div>
    </nav>

    <main class="main-content public-page">
        <div class="container">
            <header class="public-toolbar">
                <div>
                    <span class="badge">Eventos disponibles</span>
                    <h1>Agenda NeivActiva</h1>
                    <p class="text-muted">Encuentra actividades abiertas y revisa sus detalles antes de inscribirte.</p>
                </div>
                <input type="search" class="form-control" id="eventSearch" placeholder="Buscar eventos..." aria-label="Buscar eventos"
                       value="<?php echo htmlspecialchars($_GET['q'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
            </header>

            <?php if (empty($eventos)): ?>
                <section class="card empty-state">
                    <i class="bi bi-calendar-x"></i>
                    <h2>No hay eventos disponibles</h2>
                    <p>Vuelve pronto para consultar nuevas actividades publicadas.</p>
                </section>
            <?php else: ?>
                <section class="public-events-grid" id="eventGrid">
                    <?php foreach ($eventos as $evento): ?>
                        <?php
                            $eventoId = (int) ($evento['id'] ?? 0);
                            $titulo = $evento['titulo'] ?? $evento['nombre'] ?? 'Evento';
                            $ubicacion = $evento['ubicacion'] ?? $evento['lugar'] ?? 'Lugar por confirmar';
                            $fecha = $evento['fecha_evento'] ?? $evento['fecha'] ?? null;
                            $hora = $evento['hora_evento'] ?? '';
                            $categoria = $evento['categoria'] ?? 'General';
                            $imagen = $evento['ruta_imagen'] ?? '';
                            $imagen = $imagen !== '' ? $imagen : '/assets/img/Capilla_de_La_Inmaculada_Concepción.jpg';
                            $fechaTexto = $fecha ? date('d/m/Y', strtotime($fecha)) : 'Fecha por confirmar';
                            $horaTexto = $hora ? date('g:i A', strtotime($hora)) : '';
                        ?>
                        <article class="card public-event-card" data-event-card data-search="<?php echo htmlspecialchars(strtolower($titulo . ' ' . $ubicacion . ' ' . $categoria), ENT_QUOTES, 'UTF-8'); ?>">
                            <div class="public-event-media">
                                <img src="<?php echo htmlspecialchars($imagen); ?>" alt="<?php echo htmlspecialchars($titulo); ?>" loading="lazy" decoding="async">
                                <span class="event-badge"><?php echo htmlspecialchars($categoria); ?></span>
                            </div>
                            <div class="public-event-body">
                                <h2><?php echo htmlspecialchars($titulo); ?></h2>
                                <div class="public-event-meta">
                                    <span><i class="bi bi-geo-alt-fill"></i> <?php echo htmlspecialchars($ubicacion); ?></span>
                                    <span><i class="bi bi-clock"></i> <?php echo htmlspecialchars(trim($fechaTexto . ' ' . $horaTexto)); ?></span>
                                </div>
                                <a href="/evento/<?php echo $eventoId; ?>" class="btn btn-primary">
                                    Ver detalles
                                    <i class="bi bi-arrow-right"></i>
                                </a>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </section>
            <?php endif; ?>
        </div>
    </main>

    <footer class="footer">
        <div class="container text-center">
            <p>&copy; <?php echo date('Y'); ?> NeivActiva. Todos los derechos reservados.</p>
        </div>
    </footer>

    <script>
        const eventSearch = document.getElementById('eventSearch');
        const eventCards = Array.from(document.querySelectorAll('[data-event-card]'));

        const filtrarEventos = () => {
            const query = eventSearch.value.toLowerCase().trim();
            eventCards.forEach(card => {
                card.hidden = query !== '' && !(card.dataset.search || '').includes(query);
            });
        };

        eventSearch?.addEventListener('input', filtrarEventos);

        // Aplicar el filtro al cargar si viene ?q= desde el dashboard.
        if (eventSearch && eventSearch.value.trim() !== '') {
            filtrarEventos();
        }
    </script>
<script src="/assets/js/input-rules.js"></script>
</body>
</html>

