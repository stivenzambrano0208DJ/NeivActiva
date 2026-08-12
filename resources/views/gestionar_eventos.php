<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NeivActiva – Publicar Eventos</title>
    <link rel="stylesheet" href="/assets/css/neivactiva-2026.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="/assets/css/views/gestionar_eventos.css?v=<?php echo @filemtime(ROOT_PATH . '/public/assets/css/views/gestionar_eventos.css'); ?>">
</head>
<body>

<?php include 'partials/sidebar.php'; ?>

<?php
    $rolActual        = $_SESSION['rol'] ?? 'invitado';
    $usuarioActualId  = (int) ($_SESSION['usuario_id'] ?? 0);
    $totalInscritos   = array_sum(array_column($lista_eventos, 'inscritos_actuales'));
    $eventosActivos   = count(array_filter($lista_eventos, fn($ev) => ($ev['estado_evento'] ?? 'Activo') === 'Activo'));

    $puedeEditarEvento = fn($evento) =>
        $rolActual === 'admin' ||
        ($rolActual === 'organizador' && (int)($evento['organizador_id'] ?? 0) === $usuarioActualId);

    $formatearHora = fn($hora) => empty($hora) ? 'Por confirmar' : date('g:i A', strtotime($hora));
?>

<main class="main-wrapper ge-page">

    <!-- ── Top bar ──────────────────────────────── -->
    <header class="ge-topbar">
        <div class="ge-topbar-left">
            <div class="ge-page-icon"><i class="bi bi-calendar-plus-fill"></i></div>
            <div>
                <h1 class="ge-page-title">Publicar Eventos</h1>
                <p class="ge-page-sub">Gestiona y publica eventos culturales de Neiva</p>
            </div>
        </div>
        <div class="ge-topbar-right">
            <span class="ge-status-pill">
                <span class="ge-status-dot"></span>
                <?php echo count($lista_eventos); ?> evento<?php echo count($lista_eventos) !== 1 ? 's' : ''; ?>
            </span>
            <?php if (!empty($_GET['exito'])): ?>
                <span class="ge-toast ge-toast--ok"><i class="bi bi-check-circle-fill"></i> Evento publicado</span>
            <?php elseif (!empty($_GET['actualizado'])): ?>
                <span class="ge-toast ge-toast--ok"><i class="bi bi-check-circle-fill"></i> Evento actualizado</span>
            <?php elseif (!empty($_GET['terminado'])): ?>
                <span class="ge-toast ge-toast--info"><i class="bi bi-info-circle-fill"></i> Evento terminado</span>
            <?php elseif (!empty($_GET['error'])): ?>
                <span class="ge-toast ge-toast--err"><i class="bi bi-exclamation-circle-fill"></i>
                    <?php
                        $err = $_GET['error'];
                        echo match($err) {
                            'validacion'  => 'Completa todos los campos',
                            'imagen'      => 'Formato de imagen no válido',
                            'subida'      => 'No se pudo subir la imagen',
                            'permiso'     => 'Sin permiso para esa acción',
                            default       => 'Ocurrió un error'
                        };
                    ?>
                </span>
            <?php endif; ?>
        </div>
    </header>

    <!-- ── Summary pills ────────────────────────── -->
    <div class="ge-summary">
        <div class="ge-sum-card">
            <i class="bi bi-calendar-event ge-sum-icon"></i>
            <div>
                <span class="ge-sum-val"><?php echo count($lista_eventos); ?></span>
                <span class="ge-sum-lbl">Total eventos</span>
            </div>
        </div>
        <div class="ge-sum-card">
            <i class="bi bi-check-circle ge-sum-icon ge-sum-icon--green"></i>
            <div>
                <span class="ge-sum-val"><?php echo $eventosActivos; ?></span>
                <span class="ge-sum-lbl">Activos</span>
            </div>
        </div>
        <div class="ge-sum-card">
            <i class="bi bi-people ge-sum-icon ge-sum-icon--blue"></i>
            <div>
                <span class="ge-sum-val"><?php echo number_format($totalInscritos); ?></span>
                <span class="ge-sum-lbl">Inscritos totales</span>
            </div>
        </div>
    </div>

    <!-- ── Main grid ─────────────────────────────── -->
    <div class="ge-grid">

        <!-- ── LEFT: Event list ─────────────────── -->
        <section class="ge-card ge-list-card">
            <div class="ge-card-header">
                <h2 class="ge-card-title"><i class="bi bi-list-ul"></i> Eventos publicados</h2>
            </div>

            <?php if (empty($lista_eventos)): ?>
                <div class="ge-empty">
                    <i class="bi bi-calendar-x"></i>
                    <p>Aún no hay eventos. Crea el primero usando el formulario.</p>
                </div>
            <?php else: ?>
                <div class="ge-event-list">
                    <?php foreach ($lista_eventos as $ev):
                        $eId      = (int)($ev['id'] ?? 0);
                        $titulo   = htmlspecialchars($ev['titulo'] ?? 'Sin título');
                        $ubicacion= htmlspecialchars($ev['ubicacion'] ?? '—');
                        $fecha    = !empty($ev['fecha_evento']) ? date('d M Y', strtotime($ev['fecha_evento'])) : '—';
                        $hora     = $formatearHora($ev['hora_evento'] ?? '');
                        $cat      = htmlspecialchars($ev['categoria'] ?? 'Otro');
                        $inscritos= (int)($ev['inscritos_actuales'] ?? 0);
                        $cupo     = (int)($ev['cupo_maximo'] ?? 0);
                        $estado   = $ev['estado_evento'] ?? 'Activo';
                        $pct      = $cupo > 0 ? min(100, round($inscritos / $cupo * 100)) : 0;
                        $img      = !empty($ev['ruta_imagen']) ? (strpos($ev['ruta_imagen'], '/') === 0 ? $ev['ruta_imagen'] : '/' . ltrim($ev['ruta_imagen'], '/')) : null;
                        $catColor = match($cat) {
                            'Cultural'   => 'purple',
                            'Deportivo'  => 'blue',
                            'Educativo'  => 'teal',
                            default      => 'orange'
                        };
                    ?>
                    <article class="ge-event-row">
                        <div class="ge-event-thumb">
                            <?php if ($img): ?>
                                <img src="<?php echo $img; ?>" alt="<?php echo $titulo; ?>">
                            <?php else: ?>
                                <div class="ge-thumb-placeholder ge-thumb-<?php echo $catColor; ?>">
                                    <i class="bi bi-<?php echo match($cat) { 'Cultural'=>'palette', 'Deportivo'=>'trophy', 'Educativo'=>'mortarboard', default=>'calendar-event' }; ?>"></i>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="ge-event-info">
                            <div class="ge-event-top">
                                <span class="ge-cat-badge ge-cat-<?php echo $catColor; ?>"><?php echo $cat; ?></span>
                                <span class="ge-estado-badge ge-estado-<?php echo strtolower($estado); ?>"><?php echo $estado; ?></span>
                            </div>
                            <h3 class="ge-event-title"><?php echo $titulo; ?></h3>
                            <div class="ge-event-meta">
                                <span><i class="bi bi-calendar3"></i> <?php echo $fecha; ?></span>
                                <span><i class="bi bi-clock"></i> <?php echo $hora; ?></span>
                                <span><i class="bi bi-geo-alt"></i> <?php echo $ubicacion; ?></span>
                            </div>
                            <div class="ge-capacity-row">
                                <div class="ge-cap-bar-wrap">
                                    <div class="ge-cap-bar" style="width:<?php echo $pct; ?>%"></div>
                                </div>
                                <span class="ge-cap-text"><?php echo $inscritos; ?> / <?php echo $cupo; ?> cupos (<?php echo $pct; ?>%)</span>
                            </div>
                        </div>

                        <?php if ($puedeEditarEvento($ev)): ?>
                        <div class="ge-event-actions">
                            <button class="ge-btn-icon ge-btn-edit" title="Editar"
                                data-id="<?php echo $eId; ?>"
                                data-titulo="<?php echo $titulo; ?>"
                                data-ubicacion="<?php echo $ubicacion; ?>"
                                data-fecha="<?php echo htmlspecialchars($ev['fecha_evento'] ?? ''); ?>"
                                data-hora="<?php echo htmlspecialchars(substr($ev['hora_evento'] ?? '', 0, 5)); ?>"
                                data-categoria="<?php echo $cat; ?>"
                                data-aforo="<?php echo $cupo; ?>"
                                data-descripcion="<?php echo htmlspecialchars($ev['descripcion'] ?? ''); ?>">
                                <i class="bi bi-pencil-square"></i>
                            </button>
                            <?php if ($estado === 'Activo'): ?>
                            <form method="POST" action="/admin/eventos" class="ge-inline-form"
                                  onsubmit="return confirm('¿Marcar este evento como terminado?')">
                                <input type="hidden" name="accion" value="terminar_evento">
                                <input type="hidden" name="evento_id" value="<?php echo $eId; ?>">
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">
                                <button type="submit" class="ge-btn-icon ge-btn-end" title="Terminar evento">
                                    <i class="bi bi-stop-circle"></i>
                                </button>
                            </form>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                    </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

        <!-- ── RIGHT: Create form ────────────────── -->
        <aside class="ge-card ge-form-card">
            <div class="ge-card-header">
                <h2 class="ge-card-title"><i class="bi bi-plus-circle-fill"></i> Nuevo evento</h2>
            </div>

            <form method="POST" action="/admin/eventos" enctype="multipart/form-data" class="ge-form" id="createForm">
                <input type="hidden" name="accion" value="crear_evento">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">

                <div class="ge-field">
                    <label class="ge-label">Título del evento <span class="ge-req">*</span></label>
                    <input type="text" name="titulo" class="ge-input" placeholder="Ej: Festival Sanjuanero Huilense 2026" required maxlength="150">
                </div>

                <div class="ge-field-row">
                    <div class="ge-field">
                        <label class="ge-label">Fecha <span class="ge-req">*</span></label>
                        <input type="date" name="fecha_evento" class="ge-input" required>
                    </div>
                    <div class="ge-field">
                        <label class="ge-label">Hora <span class="ge-req">*</span></label>
                        <input type="time" name="hora_evento" class="ge-input" required>
                    </div>
                </div>

                <div class="ge-field">
                    <label class="ge-label">Ubicación <span class="ge-req">*</span></label>
                    <input type="text" name="ubicacion" class="ge-input" placeholder="Ej: Parque Santander, Neiva" required maxlength="200">
                </div>

                <div class="ge-field-row">
                    <div class="ge-field">
                        <label class="ge-label">Categoría <span class="ge-req">*</span></label>
                        <select name="categoria" class="ge-select" required>
                            <option value="">Seleccionar…</option>
                            <option value="Cultural">Cultural</option>
                            <option value="Deportivo">Deportivo</option>
                            <option value="Educativo">Educativo</option>
                            <option value="Otro">Otro</option>
                        </select>
                    </div>
                    <div class="ge-field">
                        <label class="ge-label">Cupo máximo <span class="ge-req">*</span></label>
                        <input type="number" name="cupo_maximo" class="ge-input" min="1" value="50" required>
                    </div>
                </div>

                <div class="ge-field">
                    <label class="ge-label">Descripción <span class="ge-req">*</span></label>
                    <textarea name="descripcion" class="ge-textarea" rows="3"
                              placeholder="Describe el evento, actividades, artistas, etc." required maxlength="1000"></textarea>
                </div>

                <div class="ge-field">
                    <label class="ge-label">Imagen del evento</label>
                    <label class="ge-upload-zone" id="uploadZone">
                        <input type="file" name="imagen" accept="image/jpeg,image/png,image/webp" id="imgInput" class="ge-file-hidden">
                        <div class="ge-upload-inner" id="uploadInner">
                            <i class="bi bi-cloud-arrow-up-fill ge-upload-icon"></i>
                            <span class="ge-upload-text">Arrastra una imagen o <u>haz clic</u></span>
                            <span class="ge-upload-hint">JPG, PNG o WEBP · máx. 5 MB</span>
                        </div>
                    </label>
                    <div class="ge-img-preview" id="imgPreview" style="display:none">
                        <img id="previewImg" src="" alt="Vista previa">
                        <button type="button" class="ge-img-remove" id="removeImg" title="Quitar imagen">
                            <i class="bi bi-x-circle-fill"></i>
                        </button>
                    </div>
                </div>

                <button type="submit" class="ge-btn-submit">
                    <i class="bi bi-send-fill"></i> Publicar evento
                </button>
            </form>
        </aside>
    </div>
</main>

<!-- ── Edit Modal ──────────────────────────────── -->
<div class="ge-modal-overlay" id="editModal">
    <div class="ge-modal">
        <div class="ge-modal-header">
            <h3 class="ge-modal-title"><i class="bi bi-pencil-square"></i> Editar evento</h3>
            <button class="ge-modal-close" data-close-edit><i class="bi bi-x"></i></button>
        </div>
        <form method="POST" action="/admin/eventos" enctype="multipart/form-data" class="ge-form" id="editForm">
            <input type="hidden" name="accion" value="editar_evento">
            <input type="hidden" name="evento_id" id="editId">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">
            <input type="hidden" id="editHoraOriginal">

            <div class="ge-field">
                <label class="ge-label">Título <span class="ge-req">*</span></label>
                <input type="text" name="titulo" id="editTitulo" class="ge-input" required maxlength="150">
            </div>

            <div class="ge-field-row">
                <div class="ge-field">
                    <label class="ge-label">Fecha <span class="ge-req">*</span></label>
                    <input type="date" name="fecha_evento" id="editFecha" class="ge-input" required>
                </div>
                <div class="ge-field">
                    <label class="ge-label">Hora <span class="ge-req">*</span></label>
                    <input type="time" name="hora_evento" id="editHora" class="ge-input" required>
                </div>
            </div>

            <div class="ge-field">
                <label class="ge-label">Ubicación <span class="ge-req">*</span></label>
                <input type="text" name="ubicacion" id="editUbicacion" class="ge-input" required maxlength="200">
            </div>

            <div class="ge-field-row">
                <div class="ge-field">
                    <label class="ge-label">Categoría <span class="ge-req">*</span></label>
                    <select name="categoria" id="editCategoria" class="ge-select" required>
                        <option value="Cultural">Cultural</option>
                        <option value="Deportivo">Deportivo</option>
                        <option value="Educativo">Educativo</option>
                        <option value="Otro">Otro</option>
                    </select>
                </div>
                <div class="ge-field">
                    <label class="ge-label">Cupo máximo <span class="ge-req">*</span></label>
                    <input type="number" name="cupo_maximo" id="editAforo" class="ge-input" min="1" required>
                </div>
            </div>

            <div class="ge-field">
                <label class="ge-label">Descripción <span class="ge-req">*</span></label>
                <textarea name="descripcion" id="editDescripcion" class="ge-textarea" rows="4" required maxlength="1000"></textarea>
            </div>

            <div class="ge-field">
                <label class="ge-label">Nueva imagen <small>(opcional)</small></label>
                <label class="ge-upload-zone">
                    <input type="file" name="imagen" accept="image/jpeg,image/png,image/webp" class="ge-file-hidden">
                    <div class="ge-upload-inner">
                        <i class="bi bi-cloud-arrow-up-fill ge-upload-icon"></i>
                        <span class="ge-upload-text">Reemplazar imagen</span>
                    </div>
                </label>
            </div>

            <div class="ge-modal-actions">
                <button type="button" class="ge-btn-cancel" data-close-edit>Cancelar</button>
                <button type="submit" class="ge-btn-submit">
                    <i class="bi bi-check-lg"></i> Guardar cambios
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ── Confirm time-change Modal ──────────────── -->
<div class="ge-modal-overlay" id="confirmModal">
    <div class="ge-modal ge-modal--sm">
        <div class="ge-modal-header">
            <h3 class="ge-modal-title"><i class="bi bi-clock-history"></i> Cambio de horario</h3>
        </div>
        <p class="ge-modal-body">Cambiaste el horario del evento. Los participantes inscritos <strong>serán notificados</strong> por correo. ¿Continuar?</p>
        <div class="ge-modal-actions">
            <button type="button" class="ge-btn-cancel" id="cancelTimeChange">No, volver</button>
            <button type="button" class="ge-btn-submit" id="confirmTimeChange">
                <i class="bi bi-check-lg"></i> Sí, guardar
            </button>
        </div>
    </div>
</div>

<script>
(function () {
    /* ── helpers ─────────────────────────────── */
    const openModal  = el => { el.style.display = 'flex'; requestAnimationFrame(() => el.classList.add('ge-modal-active')); };
    const closeModal = el => { el.classList.remove('ge-modal-active'); setTimeout(() => el.style.display = 'none', 240); };

    /* ── image preview ───────────────────────── */
    const imgInput   = document.getElementById('imgInput');
    const preview    = document.getElementById('imgPreview');
    const previewImg = document.getElementById('previewImg');
    const removeBtn  = document.getElementById('removeImg');
    const uploadZone = document.getElementById('uploadZone');

    if (imgInput) {
        imgInput.addEventListener('change', () => {
            const file = imgInput.files[0];
            if (!file) return;
            const reader = new FileReader();
            reader.onload = e => {
                previewImg.src = e.target.result;
                preview.style.display = 'block';
                uploadZone.style.display = 'none';
            };
            reader.readAsDataURL(file);
        });
    }

    if (removeBtn) {
        removeBtn.addEventListener('click', () => {
            imgInput.value = '';
            preview.style.display = 'none';
            uploadZone.style.display = '';
        });
    }

    /* ── edit modal ──────────────────────────── */
    const editModal = document.getElementById('editModal');
    const editForm  = document.getElementById('editForm');
    let pendingSubmit = false;

    document.querySelectorAll('.ge-btn-edit').forEach(btn => {
        btn.addEventListener('click', () => {
            document.getElementById('editId').value          = btn.dataset.id          || '';
            document.getElementById('editTitulo').value      = btn.dataset.titulo       || '';
            document.getElementById('editUbicacion').value   = btn.dataset.ubicacion    || '';
            document.getElementById('editFecha').value       = btn.dataset.fecha        || '';
            document.getElementById('editHora').value        = btn.dataset.hora         || '';
            document.getElementById('editHoraOriginal').value= btn.dataset.hora         || '';
            document.getElementById('editCategoria').value   = btn.dataset.categoria    || 'Otro';
            document.getElementById('editAforo').value       = btn.dataset.aforo        || 1;
            document.getElementById('editDescripcion').value = btn.dataset.descripcion  || '';

            pendingSubmit = false;
            openModal(editModal);
        });
    });

    document.querySelectorAll('[data-close-edit]').forEach(btn => {
        btn.addEventListener('click', () => closeModal(editModal));
    });

    editModal.addEventListener('click', e => { if (e.target === editModal) closeModal(editModal); });

    /* ── confirm time-change ─────────────────── */
    const confirmModal = document.getElementById('confirmModal');

    if (editForm) {
        editForm.addEventListener('submit', e => {
            const orig = document.getElementById('editHoraOriginal').value;
            const curr = document.getElementById('editHora').value;
            if (!pendingSubmit && orig !== curr) {
                e.preventDefault();
                openModal(confirmModal);
            }
        });
    }

    document.getElementById('cancelTimeChange')?.addEventListener('click', () => {
        pendingSubmit = false;
        closeModal(confirmModal);
    });

    document.getElementById('confirmTimeChange')?.addEventListener('click', () => {
        pendingSubmit = true;
        closeModal(confirmModal);
        editForm.submit();
    });
})();
</script>
</body>
</html>
