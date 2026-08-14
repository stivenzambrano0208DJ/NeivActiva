<?php
$usuarioLogueado = isset($_SESSION['usuario_id']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NeivActiva - Eventos de Neiva</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=Outfit:wght@600;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/neivactiva-2026.css">
    <link rel="stylesheet" href="/assets/css/views/landing.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body>
    <nav class="landing-nav">
        <a href="/" class="nav-brand">
            <i class="bi bi-lightning-charge-fill"></i>
            <span>Neiv<em>Activa</em></span>
        </a>
        <ul class="nav-links-list">
            <li><a href="/eventos">Eventos</a></li>
            <li><a href="/calendario">Calendario</a></li>
            <?php if ($usuarioLogueado): ?>
                <li><a href="/dashboard">Dashboard</a></li>
                <li><a href="/logout" class="nav-cta">Cerrar sesión</a></li>
            <?php else: ?>
                <li><a href="/login">Iniciar sesión</a></li>
                <li><a href="/register" class="nav-cta">Crear cuenta</a></li>
            <?php endif; ?>
        </ul>
    </nav>

    <section class="landing-hero">
        <div class="landing-hero-bg">
            <img src="/assets/img/Neiva,_La_Gaitana_monumento_emblematico_de_la_ciudad.jpg" alt="Monumento La Gaitana en Neiva">
        </div>
        <div class="landing-hero-content">
            <div class="hero-text">
                <span class="hero-badge">
                    <i class="bi bi-stars"></i> Agenda cultural y deportiva
                </span>
                <h1>Creamos eventos <br><span>para tu ciudad.</span></h1>
                <p>Una plataforma moderna para descubrir, inscribirte y disfrutar los mejores eventos artísticos, tradicionales y deportivos de Neiva. Reserva tu cupo con QR y descarga certificados al instante.</p>
                <div class="hero-buttons">
                    <a href="/eventos" class="hero-btn-primary">
                        <i class="bi bi-calendar-check"></i> Explorar eventos
                    </a>
                    <?php if ($usuarioLogueado): ?>
                        <a href="/dashboard" class="hero-btn-secondary">
                            <i class="bi bi-graph-up-arrow"></i> Dashboard
                        </a>
                    <?php else: ?>
                        <a href="/register" class="hero-btn-secondary">
                            <i class="bi bi-person-plus"></i> Crear cuenta gratis
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            <div class="hero-image-wrapper">
                <div class="hero-stat hero-stat-top">
                    <i class="bi bi-calendar-check-fill"></i> +50 eventos activos
                </div>
                <div class="hero-image-card">
                    <img src="/assets/img/Capilla_de_La_Inmaculada_Concepción.jpg" alt="Capilla de la Inmaculada Concepción">
                </div>
                <div class="hero-stat hero-stat-bottom">
                    <i class="bi bi-qr-code-scan"></i> Acceso con QR
                </div>
            </div>
        </div>
    </section>

    <section class="categories-bar">
        <div class="categories-grid">
            <a href="/eventos" class="category-icon-item">
                <div class="icon-circle">
                    <i class="bi bi-palette"></i>
                </div>
                <span>Cultura</span>
            </a>
            <a href="/eventos" class="category-icon-item">
                <div class="icon-circle">
                    <i class="bi bi-megaphone"></i>
                </div>
                <span>Eventos</span>
            </a>
            <a href="/eventos" class="category-icon-item">
                <div class="icon-circle">
                    <i class="bi bi-trophy"></i>
                </div>
                <span>Deporte</span>
            </a>
            <a href="/eventos" class="category-icon-item">
                <div class="icon-circle">
                    <i class="bi bi-mortarboard"></i>
                </div>
                <span>Educación</span>
            </a>
            <a href="/eventos" class="category-icon-item">
                <div class="icon-circle">
                    <i class="bi bi-grid-fill"></i>
                </div>
                <span>Otros</span>
            </a>
        </div>
    </section>

    <main>
        <section class="landing-section">
            <div class="section-header">
                <span class="section-kicker">Destacados</span>
                <h2 class="section-title">Próximos Eventos en <em>Neiva</em></h2>
                <p class="section-subtitle">Descubre lo mejor de la ciudad, inscríbete y vive la experiencia.</p>
            </div>

            <?php if (empty($eventos)): ?>
                <div style="text-align: center; padding: 3rem 0; color: var(--text-tertiary);">
                    <i class="bi bi-calendar-x" style="font-size: 3rem; margin-bottom: 1rem; color: var(--primary);"></i>
                    <p>No hay eventos programados en este momento. Vuelve pronto.</p>
                </div>
            <?php else: ?>
                <div class="events-showcase">
                    <?php foreach ($eventos as $evento): ?>
                        <?php
                            $eventoId = (int) ($evento['id'] ?? 0);
                            $titulo = $evento['titulo'] ?? 'Evento';
                            $ubicacion = $evento['ubicacion'] ?? 'Ubicación por confirmar';
                            $fecha = $evento['fecha_evento'] ?? null;
                            $hora = $evento['hora_evento'] ?? '';
                            $categoria = $evento['categoria'] ?? 'Otro';
                            $imagen = $evento['ruta_imagen'] ?? '';
                            $imagen = $imagen !== '' ? $imagen : '/assets/img/Capilla_de_La_Inmaculada_Concepción.jpg';
                            $fechaTexto = $fecha ? date('d/m/Y', strtotime($fecha)) : 'Fecha por confirmar';
                            $horaTexto = $hora ? date('g:i A', strtotime($hora)) : '';
                        ?>
                        <a href="/evento/<?php echo $eventoId; ?>" class="event-showcase-card">
                            <div class="event-card-img">
                                <img src="<?php echo htmlspecialchars($imagen); ?>" alt="<?php echo htmlspecialchars($titulo); ?>" loading="lazy" decoding="async">
                                <span class="event-card-badge"><?php echo htmlspecialchars($categoria); ?></span>
                            </div>
                            <div class="event-card-body">
                                <h3><?php echo htmlspecialchars($titulo); ?></h3>
                                <div class="event-card-meta">
                                    <span><i class="bi bi-geo-alt-fill"></i> <?php echo htmlspecialchars($ubicacion); ?></span>
                                    <span><i class="bi bi-calendar3"></i> <?php echo htmlspecialchars($fechaTexto); ?></span>
                                    <?php if ($horaTexto): ?>
                                        <span><i class="bi bi-clock"></i> <?php echo htmlspecialchars($horaTexto); ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="event-card-footer">
                                <span class="event-card-btn">
                                    Ver detalles <i class="bi bi-arrow-right"></i>
                                </span>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

        <!-- ── How it works ────────────────────────────── -->
        <section class="how-it-works">
            <div class="how-it-works-inner">
                <div class="how-image-side">
                    <div class="how-image-card">
                        <img src="/assets/img/unnamed.jpg" alt="Proceso de inscripción">
                    </div>
                </div>
                <div class="how-text-side">
                    <div>
                        <span class="section-kicker">Así funciona</span>
                        <h2 class="section-title" style="text-align:left; margin-top:0.6rem;">
                            <em>Soluciones simples</em> para cada paso
                        </h2>
                    </div>
                    <div class="how-steps">
                        <div class="how-step">
                            <div class="how-step-num">1</div>
                            <div class="how-step-body">
                                <strong>Contáctanos / Regístrate</strong>
                                <p>Crea tu cuenta gratuita en segundos y accede al catálogo completo de eventos de Neiva.</p>
                            </div>
                        </div>
                        <div class="how-step">
                            <div class="how-step-num">2</div>
                            <div class="how-step-body">
                                <strong>Explora y elige tu evento</strong>
                                <p>Filtra por categoría, fecha o ubicación y encuentra la actividad que más te inspire.</p>
                            </div>
                        </div>
                        <div class="how-step">
                            <div class="how-step-num">3</div>
                            <div class="how-step-body">
                                <strong>Haz tu inscripción</strong>
                                <p>Reserva tu cupo con un clic. Recibes tu código QR de acceso de forma instantánea.</p>
                            </div>
                        </div>
                        <div class="how-step">
                            <div class="how-step-num">4</div>
                            <div class="how-step-body">
                                <strong>Asiste y obtén tu certificado</strong>
                                <p>Presenta tu QR en el evento y descarga tu certificado digital al finalizar.</p>
                            </div>
                        </div>
                    </div>
                    <div class="how-step-cta">
                        <a href="/eventos" class="hero-btn-primary">
                            <i class="bi bi-calendar2-event"></i> Ver eventos
                        </a>
                        <a href="/register" class="hero-btn-secondary">
                            <i class="bi bi-person-plus"></i> Registrarse
                        </a>
                    </div>
                </div>
            </div>
        </section>

        <section class="section-alt">
            <div class="landing-section">
                <div class="section-header">
                    <span class="section-kicker">Explora</span>
                    <h2 class="section-title">Actividades <em>Especiales</em></h2>
                    <p class="section-subtitle">Experiencias únicas que solo encuentras en Neiva.</p>
                </div>
                <div class="events-showcase">
                    <div class="event-showcase-card" style="cursor: default;">
                        <div class="event-card-img">
                            <img src="/assets/img/unnamed.jpg" alt="Talleres Artísticos">
                            <span class="event-card-badge" style="background: var(--bg-tertiary); color: var(--text-primary); border: 1px solid var(--border-primary);">Cultura</span>
                        </div>
                        <div class="event-card-body">
                            <h3>Talleres Artísticos y Danza</h3>
                            <p style="font-size: 0.88rem; color: var(--text-secondary); line-height: 1.6; margin: 0.5rem 0 0;">Talleres semanales gratuitos de danza tradicional (Sanjuanero Huilense), pintura y música en el Parque de la Música Jorge Villamil Cordovez.</p>
                        </div>
                    </div>

                    <div class="event-showcase-card" style="cursor: default;">
                        <div class="event-card-img">
                            <img src="/assets/img/Neiva,_La_Gaitana_monumento_emblematico_de_la_ciudad.jpg" alt="Deportes al aire libre">
                            <span class="event-card-badge" style="background: var(--bg-tertiary); color: var(--text-primary); border: 1px solid var(--border-primary);">Deporte</span>
                        </div>
                        <div class="event-card-body">
                            <h3>Ciclovía y Recreación Dominical</h3>
                            <p style="font-size: 0.88rem; color: var(--text-secondary); line-height: 1.6; margin: 0.5rem 0 0;">Cada domingo recorre la Av. Tenerife en bicicleta, patines o corriendo. Actividades dirigidas de aeróbicos y yoga al aire libre para toda la familia.</p>
                        </div>
                    </div>

                    <div class="event-showcase-card" style="cursor: default;">
                        <div class="event-card-img">
                            <img src="/assets/img/Capilla_de_La_Inmaculada_Concepción.jpg" alt="Historia y Educación">
                            <span class="event-card-badge" style="background: var(--bg-tertiary); color: var(--text-primary); border: 1px solid var(--border-primary);">Educación</span>
                        </div>
                        <div class="event-card-body">
                            <h3>Rutas Históricas e Identidad</h3>
                            <p style="font-size: 0.88rem; color: var(--text-secondary); line-height: 1.6; margin: 0.5rem 0 0;">Recorridos guiados gratuitos por los monumentos históricos de la ciudad de Neiva, conociendo la leyenda del Mohán, la Gaitana y más.</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ── About / Our Platform ───────────────────── -->
        <section>
            <div class="about-section">
                <div class="about-text">
                    <span class="section-kicker">Sobre NeivActiva</span>
                    <h2>Nuestra <em>Plataforma</em></h2>
                    <p>Creemos en el poder de los eventos para transformar la ciudad. NeivActiva nació para centralizar la agenda cultural, deportiva y educativa de Neiva, ofreciendo herramientas modernas a organizadores y ciudadanos por igual.</p>
                    <p>Optimiza la gestión de tus eventos con estadísticas en tiempo real, control de asistencia por QR, certificados digitales automáticos y mucho más.</p>
                    <div class="about-stats">
                        <div class="about-stat-card">
                            <span class="stat-number">50+</span>
                            <span class="stat-label">Eventos realizados</span>
                        </div>
                        <div class="about-stat-card">
                            <span class="stat-number">300+</span>
                            <span class="stat-label">Participantes activos</span>
                        </div>
                        <div class="about-stat-card">
                            <span class="stat-number">4</span>
                            <span class="stat-label">Categorías de eventos</span>
                        </div>
                        <div class="about-stat-card">
                            <span class="stat-number">100%</span>
                            <span class="stat-label">Acceso gratuito</span>
                        </div>
                    </div>
                    <div style="margin-top: 0.5rem;">
                        <a href="/eventos" class="hero-btn-primary">
                            <i class="bi bi-rocket-takeoff"></i> Comenzar ahora
                        </a>
                    </div>
                </div>
                <div class="about-image-side">
                    <div class="about-img-main">
                        <img src="/assets/img/Neiva,_La_Gaitana_monumento_emblematico_de_la_ciudad.jpg" alt="La Gaitana - Neiva">
                    </div>
                </div>
            </div>
        </section>

        <section class="cta-banner">
            <div class="cta-banner-content">
                <h2>Obtén tus Códigos QR y Certificados al Instante</h2>
                <p>Inscríbete en cualquier evento, accede de forma segura con tu código QR generado automáticamente y descarga tu certificado digital una vez confirmada tu asistencia.</p>
                <div class="hero-buttons" style="justify-content: center;">
                    <?php if ($usuarioLogueado): ?>
                        <a href="/mis-eventos" class="hero-btn-primary">
                            <i class="bi bi-qr-code"></i> Mis Eventos Inscritos
                        </a>
                    <?php else: ?>
                        <a href="/register" class="hero-btn-primary">
                            <i class="bi bi-person-plus"></i> Registrarse Ahora
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </section>

        <section class="landing-section">
            <div class="section-header">
                <span class="section-kicker">Opiniones</span>
                <h2 class="section-title">Lo que dicen nuestros <em>usuarios</em></h2>
                <p class="section-subtitle">Personas reales que disfrutan los eventos de Neiva con NeivActiva.</p>
            </div>
            <div class="testimonials-grid">
                <?php if (empty($resenas ?? [])): ?>
                <article class="testimonial-card">
                    <div class="testimonial-stars">
                        <i class="bi bi-star-fill"></i>
                        <i class="bi bi-star-fill"></i>
                        <i class="bi bi-star-fill"></i>
                        <i class="bi bi-star-fill"></i>
                        <i class="bi bi-star-fill"></i>
                    </div>
                    <blockquote>"Me encanta la facilidad para inscribirme en las ciclovías y eventos culturales. El código QR en mi celular hace que el ingreso sea súper rápido y sin complicaciones."</blockquote>
                    <div class="testimonial-author">
                        <div class="testimonial-avatar">AM</div>
                        <div class="testimonial-author-info">
                            <h4>Andrés Mendoza</h4>
                            <p>Participante Activo</p>
                        </div>
                    </div>
                </article>

                <article class="testimonial-card">
                    <div class="testimonial-stars">
                        <i class="bi bi-star-fill"></i>
                        <i class="bi bi-star-fill"></i>
                        <i class="bi bi-star-fill"></i>
                        <i class="bi bi-star-fill"></i>
                        <i class="bi bi-star-fill"></i>
                    </div>
                    <blockquote>"Como gestora cultural, esta plataforma ha sido una bendición. Puedo ver en tiempo real cuántas personas asistirán a mis talleres y los certificados se emiten automáticamente."</blockquote>
                    <div class="testimonial-author">
                        <div class="testimonial-avatar">CP</div>
                        <div class="testimonial-author-info">
                            <h4>Camila Perdomo</h4>
                            <p>Organizadora de Talleres</p>
                        </div>
                    </div>
                </article>

                <article class="testimonial-card">
                    <div class="testimonial-stars">
                        <i class="bi bi-star-fill"></i>
                        <i class="bi bi-star-fill"></i>
                        <i class="bi bi-star-fill"></i>
                        <i class="bi bi-star-fill"></i>
                        <i class="bi bi-star-fill"></i>
                    </div>
                    <blockquote>"Excelente iniciativa para centralizar los eventos de la ciudad. He descargado tres certificados oficiales de cursos educativos muy fácilmente. 10/10."</blockquote>
                    <div class="testimonial-author">
                        <div class="testimonial-avatar">JR</div>
                        <div class="testimonial-author-info">
                            <h4>Juan Ramón</h4>
                            <p>Estudiante Universitario</p>
                        </div>
                    </div>
                </article>
                <?php else: ?>
                <?php foreach ($resenas as $rz):
                    $rzn = trim((string) ($rz['nombre'] ?? 'Participante'));
                    $rzi = strtoupper(mb_substr($rzn, 0, 1, 'UTF-8'));
                    $rzp = preg_split('/\s+/', $rzn);
                    if (count($rzp) > 1) { $rzi .= strtoupper(mb_substr(end($rzp), 0, 1, 'UTF-8')); }
                    $rzc = max(1, min(5, (int) ($rz['calificacion'] ?? 5)));
                    $rzrol = trim((string) ($rz['rol_texto'] ?? '')) !== '' ? (string) $rz['rol_texto'] : 'Asistente';
                    if (!empty($rz['evento_titulo'])) { $rzrol .= ' · ' . $rz['evento_titulo']; }
                ?>
                <article class="testimonial-card">
                    <div class="testimonial-stars">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <i class="bi bi-star<?php echo $i <= $rzc ? '-fill' : ''; ?>"></i>
                        <?php endfor; ?>
                    </div>
                    <blockquote><?php echo htmlspecialchars('"' . ($rz['comentario'] ?? '') . '"'); ?></blockquote>
                    <div class="testimonial-author">
                        <div class="testimonial-avatar"><?php echo htmlspecialchars($rzi); ?></div>
                        <div class="testimonial-author-info">
                            <h4><?php echo htmlspecialchars($rzn); ?></h4>
                            <p><?php echo htmlspecialchars($rzrol); ?></p>
                        </div>
                    </div>
                </article>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>
    </main>

    <footer class="landing-footer">
        <div class="footer-inner">
            <div class="footer-top">
                <div class="footer-brand">
                    <h3><i class="bi bi-lightning-charge-fill"></i> NeivActiva</h3>
                    <p>Conectando a los ciudadanos de Neiva con las mejores actividades culturales, educativas y deportivas de la región.</p>
                </div>
                <div class="footer-col">
                    <h4>Enlaces</h4>
                    <a href="/eventos">Ver eventos</a>
                    <a href="/calendario">Calendario</a>
                </div>
                <div class="footer-col">
                    <h4>Soporte</h4>
                    <a href="#">Contacto</a>
                    <a href="#">Políticas de privacidad</a>
                </div>
                <div class="footer-col footer-newsletter">
                    <h4>Boletín</h4>
                    <p>Suscríbete para recibir notificaciones sobre nuevos eventos.</p>
                    <form class="newsletter-form" onsubmit="event.preventDefault(); alert('¡Gracias por suscribirte al boletín!');">
                        <input type="email" placeholder="Tu correo electrónico" required>
                        <button type="submit">Unirse</button>
                    </form>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> NeivActiva. Todos los derechos reservados.</p>
            </div>
        </div>
    </footer>

    <script>
        window.addEventListener('scroll', () => {
            const nav = document.querySelector('.landing-nav');
            if (window.scrollY > 50) {
                nav.classList.add('scrolled');
            } else {
                nav.classList.remove('scrolled');
            }
        });

        /* ── Scroll reveal ─────────────────────────────── */
        (function () {
            const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
            const targets = document.querySelectorAll(
                '.section-header, .event-showcase-card, .how-step, .how-image-card, ' +
                '.about-stat-card, .about-img-main, .testimonial-card, .cta-banner-content'
            );
            if (reduce || !('IntersectionObserver' in window)) {
                targets.forEach(el => el.classList.add('is-visible'));
                return;
            }
            targets.forEach((el, i) => {
                el.classList.add('reveal');
                el.style.transitionDelay = (Math.min(i % 4, 3) * 80) + 'ms';
            });
            const io = new IntersectionObserver((entries, obs) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('is-visible');
                        obs.unobserve(entry.target);
                    }
                });
            }, { threshold: 0.12, rootMargin: '0px 0px -8% 0px' });
            targets.forEach(el => io.observe(el));
        })();
    </script>
<script src="/assets/js/input-rules.js"></script>
</body>
</html>
