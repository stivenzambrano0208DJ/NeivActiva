<?php
/* ── Variables helpers ─────────────────────────── */
$editando = is_array($participanteEditar ?? null);
$formData = $oldInput ?: ($editando ? $participanteEditar : []);
$val      = fn($campo, $def = '') => htmlspecialchars((string)($formData[$campo] ?? $def), ENT_QUOTES, 'UTF-8');

$msgOk  = ['creado'      => 'Participante registrado correctamente.',
           'actualizado' => 'Participante actualizado.',
           'eliminado'   => 'Participante eliminado.',
           'inscrito'    => 'Participante inscrito al evento correctamente.'];
$msgErr = ['csrf'          => 'Sesión expirada. Recarga la página.',
           'validacion'    => 'Revisa los campos del formulario.',
           'bd'            => 'Error de base de datos.',
           'no_encontrado' => 'Participante no encontrado.',
           'ya_inscrito'   => 'Ese participante ya está inscrito en el evento.',
           'evento_lleno'  => 'El evento está lleno o no está disponible.',
           'inscribir_datos' => 'Selecciona un evento para inscribir.'];

$totalParticipantes = count($lista_participantes);
$conCorreo = count(array_filter($lista_participantes,
    fn($p) => trim((string)($p['correo'] ?? $p['correo_electronico'] ?? '')) !== ''));

$iniciales = function($nombre) {
    $partes = preg_split('/\s+/', trim((string)$nombre));
    $ini = '';
    foreach ($partes as $p) {
        if ($p !== '') $ini .= strtoupper(substr($p, 0, 1));
        if (strlen($ini) >= 2) break;
    }
    return $ini ?: 'NA';
};
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NeivActiva – Participantes</title>
    <link rel="stylesheet" href="/assets/css/neivactiva-2026.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="/assets/css/views/participantes.css">
</head>
<body>
<?php include 'partials/sidebar.php'; ?>

<main class="main-wrapper pt-page">

    <!-- ── Topbar ───────────────────────────────── -->
    <header class="pt-topbar">
        <div class="pt-topbar-left">
            <div class="pt-page-icon"><i class="bi bi-people-fill"></i></div>
            <div>
                <h1 class="pt-page-title">Participantes</h1>
                <p class="pt-page-sub">Gestión de personas registradas en la plataforma</p>
            </div>
        </div>
        <div class="pt-topbar-right">
            <div class="pt-stat-pill">
                <i class="bi bi-people"></i>
                <span><?php echo $totalParticipantes; ?> total</span>
            </div>
            <div class="pt-stat-pill pt-stat-pill--green">
                <i class="bi bi-envelope-check"></i>
                <span><?php echo $conCorreo; ?> con correo</span>
            </div>
            <a href="?view=carga_masiva" class="pt-btn-secondary">
                <i class="bi bi-upload"></i> Carga masiva
            </a>
            <button class="pt-btn-primary" id="openCreateBtn">
                <i class="bi bi-person-plus-fill"></i> Nuevo participante
            </button>
        </div>
    </header>

    <!-- ── Toast ────────────────────────────────── -->
    <?php if (!empty($_GET['msg']) && isset($msgOk[$_GET['msg']])): ?>
        <div class="pt-toast pt-toast--ok" id="toastMsg">
            <i class="bi bi-check-circle-fill"></i>
            <span><?php echo $msgOk[$_GET['msg']]; ?></span>
            <button onclick="document.getElementById('toastMsg').remove()"><i class="bi bi-x"></i></button>
        </div>
    <?php elseif (!empty($_GET['error']) && isset($msgErr[$_GET['error']])): ?>
        <div class="pt-toast pt-toast--err" id="toastMsg">
            <i class="bi bi-exclamation-circle-fill"></i>
            <span><?php echo $msgErr[$_GET['error']]; ?></span>
            <button onclick="document.getElementById('toastMsg').remove()"><i class="bi bi-x"></i></button>
        </div>
    <?php endif; ?>

    <!-- ── Main grid ─────────────────────────────── -->
    <div class="pt-grid">

        <!-- ── LEFT: Table ──────────────────────── -->
        <div class="pt-card pt-list-card">

            <!-- Search + filter bar -->
            <div class="pt-toolbar">
                <form method="GET" action="" class="pt-search-form">
                    <input type="hidden" name="view" value="participantes">
                    <div class="pt-search-wrap">
                        <i class="bi bi-search"></i>
                        <input type="text" name="q" class="pt-input"
                               placeholder="Buscar por nombre, correo o documento…"
                               value="<?php echo htmlspecialchars($busqueda); ?>"
                               id="searchInput" autocomplete="off">
                        <?php if ($busqueda): ?>
                            <a href="?view=participantes" class="pt-clear-search" title="Limpiar">
                                <i class="bi bi-x-circle-fill"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                    <button type="submit" class="pt-btn-primary pt-btn-sm">
                        <i class="bi bi-search"></i> Buscar
                    </button>
                </form>
                <span class="pt-count-badge"><?php echo $totalParticipantes; ?> participantes</span>
            </div>

            <?php if (empty($lista_participantes)): ?>
                <div class="pt-empty">
                    <i class="bi bi-person-x"></i>
                    <p><?php echo $busqueda ? 'No se encontraron resultados para "' . htmlspecialchars($busqueda) . '".' : 'Aún no hay participantes registrados.'; ?></p>
                </div>
            <?php else: ?>
                <div class="pt-table-wrap">
                    <table class="pt-table">
                        <thead>
                            <tr>
                                <th>Participante</th>
                                <th>Documento</th>
                                <th>Teléfono</th>
                                <th>Inscripciones</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($lista_participantes as $p):
                                $pId     = (int)($p['id'] ?? 0);
                                $nombre  = htmlspecialchars($p['nombre'] ?? $p['nombre_completo'] ?? '—');
                                $correo  = htmlspecialchars($p['correo'] ?? $p['correo_electronico'] ?? '');
                                $doc     = htmlspecialchars($p['documento'] ?? $p['documento_identidad'] ?? '—');
                                $tel     = htmlspecialchars($p['telefono'] ?? '—');
                                $totalInsc = (int)($p['total_inscripciones'] ?? 0);
                                $ini     = $iniciales($p['nombre'] ?? $p['nombre_completo'] ?? '');
                                $esEditando = $editando && (int)($participanteEditar['id'] ?? 0) === $pId;
                            ?>
                            <tr class="pt-row<?php echo $esEditando ? ' pt-row-editing' : ''; ?>">
                                <td class="pt-td-participant">
                                    <div class="pt-avatar"><?php echo $ini; ?></div>
                                    <div class="pt-pinfo">
                                        <span class="pt-pname"><?php echo $nombre; ?></span>
                                        <?php if ($correo): ?>
                                            <span class="pt-psub"><?php echo $correo; ?></span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td class="pt-td-doc"><?php echo $doc; ?></td>
                                <td class="pt-td-tel"><?php echo $tel; ?></td>
                                <td>
                                    <?php if ($totalInsc > 0): ?>
                                        <a href="?view=participantes&historial=<?php echo $pId; ?>" class="pt-insc-badge">
                                            <i class="bi bi-calendar-check"></i> <?php echo $totalInsc; ?>
                                        </a>
                                    <?php else: ?>
                                        <span class="pt-insc-badge pt-insc-none">—</span>
                                    <?php endif; ?>
                                </td>
                                <td class="pt-td-actions">
                                    <button type="button" class="pt-btn-icon pt-btn-enroll" title="Inscribir a un evento"
                                            data-enroll data-id="<?php echo $pId; ?>" data-name="<?php echo $nombre; ?>">
                                        <i class="bi bi-calendar-plus"></i>
                                    </button>
                                    <a href="?view=participantes&editar=<?php echo $pId; ?>#form-panel"
                                       class="pt-btn-icon pt-btn-edit" title="Editar">
                                        <i class="bi bi-pencil-square"></i>
                                    </a>
                                    <a href="?view=participantes&historial=<?php echo $pId; ?>"
                                       class="pt-btn-icon pt-btn-hist" title="Ver historial">
                                        <i class="bi bi-clock-history"></i>
                                    </a>
                                    <form method="POST" action="/admin/participantes" class="pt-inline-form"
                                          onsubmit="return confirm('¿Eliminar a <?php echo addslashes($nombre); ?>? Esta acción no se puede deshacer.')">
                                        <input type="hidden" name="accion"          value="eliminar">
                                        <input type="hidden" name="participante_id" value="<?php echo $pId; ?>">
                                        <input type="hidden" name="csrf_token"      value="<?php echo htmlspecialchars($csrfToken); ?>">
                                        <button type="submit" class="pt-btn-icon pt-btn-del" title="Eliminar">
                                            <i class="bi bi-trash3"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="pt-table-footer">
                    <span class="pt-footer-count">
                        <?php echo $totalParticipantes; ?> participante<?php echo $totalParticipantes !== 1 ? 's' : ''; ?>
                        <?php if ($busqueda): ?>
                            en resultados para <strong>"<?php echo htmlspecialchars($busqueda); ?>"</strong>
                        <?php endif; ?>
                    </span>
                </div>
            <?php endif; ?>
        </div>

        <!-- ── RIGHT: Form + historial ──────────── -->
        <div class="pt-right-col" id="form-panel">

            <!-- Create / Edit form -->
            <div class="pt-card pt-form-card">
                <div class="pt-card-header">
                    <h2 class="pt-card-title">
                        <i class="bi bi-<?php echo $editando ? 'pencil-square' : 'person-plus-fill'; ?>"></i>
                        <?php echo $editando ? 'Editar participante' : 'Nuevo participante'; ?>
                    </h2>
                    <?php if ($editando): ?>
                        <a href="?view=participantes" class="pt-btn-icon pt-btn-cancel-edit" title="Cancelar edición">
                            <i class="bi bi-x-lg"></i>
                        </a>
                    <?php endif; ?>
                </div>

                <!-- Form errors -->
                <?php if (!empty($formErrors)): ?>
                    <div class="pt-form-errors">
                        <i class="bi bi-exclamation-triangle-fill"></i>
                        <ul>
                            <?php foreach ($formErrors as $e): ?>
                                <li><?php echo htmlspecialchars($e); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form method="POST" action="/admin/participantes" class="pt-form">
                    <input type="hidden" name="csrf_token"      value="<?php echo htmlspecialchars($csrfToken); ?>">
                    <input type="hidden" name="accion"          value="<?php echo $editando ? 'actualizar' : 'crear'; ?>">
                    <?php if ($editando): ?>
                        <input type="hidden" name="participante_id" value="<?php echo (int)($participanteEditar['id'] ?? 0); ?>">
                    <?php endif; ?>

                    <div class="pt-field">
                        <label class="pt-label">Nombre completo <span class="pt-req">*</span></label>
                        <input type="text" name="nombre_completo" class="pt-input"
                               value="<?php echo $val('nombre_completo', $val('nombre')); ?>"
                               placeholder="Ej: Carlos Andrés Vargas" data-rule="letters" required maxlength="150">
                    </div>

                    <?php $tdPt = $val('tipo_documento') ?: 'CC'; ?>
                    <div class="pt-field-row">
                        <div class="pt-field">
                            <label class="pt-label">Tipo de documento <span class="pt-req">*</span></label>
                            <select name="tipo_documento" class="pt-input">
                                <option value="CC" <?php echo $tdPt === 'CC' ? 'selected' : ''; ?>>Cédula de ciudadanía</option>
                                <option value="TI" <?php echo $tdPt === 'TI' ? 'selected' : ''; ?>>Tarjeta de identidad</option>
                                <option value="CE" <?php echo $tdPt === 'CE' ? 'selected' : ''; ?>>Cédula de extranjería</option>
                            </select>
                        </div>
                        <div class="pt-field">
                            <label class="pt-label">Número de documento <span class="pt-req">*</span></label>
                            <input type="text" name="documento_identidad" class="pt-input"
                                   value="<?php echo $val('documento_identidad', $val('documento')); ?>"
                                   placeholder="Solo números" inputmode="numeric" data-rule="digits"
                                   pattern="\d+" required maxlength="30">
                        </div>
                    </div>

                    <div class="pt-field-row">
                        <div class="pt-field">
                            <label class="pt-label">Teléfono</label>
                            <input type="text" name="telefono" class="pt-input"
                                   value="<?php echo $val('telefono'); ?>"
                                   placeholder="Ej: 3101234567" inputmode="numeric" data-rule="digits" maxlength="20">
                        </div>
                    </div>

                    <div class="pt-field">
                        <label class="pt-label">Correo electrónico</label>
                        <input type="email" name="correo_electronico" class="pt-input"
                               value="<?php echo $val('correo_electronico', $val('correo')); ?>"
                               placeholder="correo@ejemplo.com" maxlength="120">
                    </div>

                    <button type="submit" class="pt-btn-submit">
                        <i class="bi bi-<?php echo $editando ? 'check-lg' : 'send-fill'; ?>"></i>
                        <?php echo $editando ? 'Guardar cambios' : 'Registrar participante'; ?>
                    </button>
                </form>
            </div>

            <!-- Historial panel -->
            <?php if (!empty($participanteHistorial)): ?>
                <div class="pt-card pt-hist-card">
                    <div class="pt-card-header">
                        <h2 class="pt-card-title">
                            <i class="bi bi-clock-history"></i> Historial de eventos
                        </h2>
                        <a href="?view=participantes" class="pt-btn-icon pt-btn-cancel-edit" title="Cerrar">
                            <i class="bi bi-x-lg"></i>
                        </a>
                    </div>
                    <div class="pt-hist-name">
                        <div class="pt-avatar pt-avatar-lg">
                            <?php echo $iniciales($participanteHistorial['nombre'] ?? $participanteHistorial['nombre_completo'] ?? ''); ?>
                        </div>
                        <div>
                            <strong><?php echo htmlspecialchars($participanteHistorial['nombre'] ?? $participanteHistorial['nombre_completo'] ?? '—'); ?></strong>
                            <span><?php echo htmlspecialchars($participanteHistorial['correo'] ?? $participanteHistorial['correo_electronico'] ?? ''); ?></span>
                        </div>
                    </div>

                    <?php if (empty($historialParticipante)): ?>
                        <div class="pt-empty pt-empty-sm">
                            <i class="bi bi-calendar-x"></i>
                            <p>Sin eventos registrados.</p>
                        </div>
                    <?php else: ?>
                        <div class="pt-table-wrap">
                            <table class="pt-table pt-table-sm">
                                <thead>
                                    <tr>
                                        <th>Evento</th>
                                        <th>Fecha</th>
                                        <th>Inscripción</th>
                                        <th>Asistencia</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($historialParticipante as $h):
                                        $hEstado = $h['estado'] ?? $h['estado_inscripcion'] ?? 'Confirmada';
                                        $hAsist  = $h['asistencia'] ?? $h['estado_asistencia'] ?? 'Pendiente';
                                        $hBadge  = $hAsist === 'Asistio' ? 'pt-badge--green' : ($hAsist === 'Ausente' ? 'pt-badge--red' : 'pt-badge--amber');
                                    ?>
                                        <tr class="pt-row">
                                            <td class="pt-td-event"><?php echo htmlspecialchars($h['evento_titulo'] ?? 'Evento'); ?></td>
                                            <td class="pt-td-doc">
                                                <?php echo !empty($h['fecha_evento']) ? date('d/m/Y', strtotime($h['fecha_evento'])) : '—'; ?>
                                            </td>
                                            <td><span class="pt-badge pt-badge--neutral"><?php echo htmlspecialchars($hEstado); ?></span></td>
                                            <td><span class="pt-badge <?php echo $hBadge; ?>"><?php echo htmlspecialchars($hAsist); ?></span></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

        </div><!-- /.pt-right-col -->
    </div><!-- /.pt-grid -->
</main>

<!-- Modal: inscribir participante a un evento -->
<div class="pt-modal" id="enrollModal" hidden>
    <div class="pt-modal-backdrop" data-close-enroll></div>
    <div class="pt-modal-card" role="dialog" aria-modal="true" aria-labelledby="enrollTitle">
        <div class="pt-card-header">
            <h2 class="pt-card-title" id="enrollTitle"><i class="bi bi-calendar-plus"></i> Inscribir a un evento</h2>
            <button type="button" class="pt-btn-icon" data-close-enroll aria-label="Cerrar"><i class="bi bi-x-lg"></i></button>
        </div>
        <div class="pt-modal-body">
            <p class="pt-modal-text">Participante: <strong id="enrollNombre"></strong></p>
            <?php if (empty($lista_eventos)): ?>
                <p class="pt-empty" style="padding:1rem 0;">No hay eventos con cupo disponible en este momento.</p>
            <?php else: ?>
            <form method="POST" action="/admin/participantes">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                <input type="hidden" name="accion" value="inscribir">
                <input type="hidden" name="participante_id" id="enrollParticipanteId" value="">
                <label class="pt-label" for="enrollEvento">Evento</label>
                <select name="evento_id" id="enrollEvento" class="pt-input" required>
                    <option value="">Selecciona un evento…</option>
                    <?php foreach ($lista_eventos as $ev): ?>
                        <option value="<?php echo (int)($ev['id'] ?? 0); ?>"><?php echo htmlspecialchars($ev['titulo'] ?? ''); ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="pt-btn-primary pt-modal-submit">
                    <i class="bi bi-check2-circle"></i> Confirmar inscripción
                </button>
            </form>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
(function () {
    /* Auto-dismiss toast after 4s */
    const toast = document.getElementById('toastMsg');
    if (toast) {
        setTimeout(() => {
            toast.style.opacity = '0';
            toast.style.transform = 'translateY(6px)';
            toast.style.transition = 'opacity .3s, transform .3s';
            setTimeout(() => toast.remove(), 350);
        }, 4000);
    }

    /* Escape clears search */
    document.getElementById('searchInput')?.addEventListener('keydown', e => {
        if (e.key === 'Escape') e.target.value = '';
    });

    /* "Nuevo participante" button scrolls to form */
    document.getElementById('openCreateBtn')?.addEventListener('click', () => {
        const panel = document.getElementById('form-panel');
        if (panel) panel.scrollIntoView({ behavior: 'smooth', block: 'start' });
    });

    /* Modal: inscribir participante a un evento */
    const enrollModal  = document.getElementById('enrollModal');
    const enrollPid    = document.getElementById('enrollParticipanteId');
    const enrollNombre = document.getElementById('enrollNombre');

    function openEnroll(id, name) {
        if (enrollPid) enrollPid.value = id || '';
        if (enrollNombre) enrollNombre.textContent = name || '';
        if (enrollModal) enrollModal.hidden = false;
        document.body.classList.add('modal-open');
    }
    function closeEnroll() {
        if (enrollModal) enrollModal.hidden = true;
        document.body.classList.remove('modal-open');
    }

    document.querySelectorAll('[data-enroll]').forEach(btn => {
        btn.addEventListener('click', () => openEnroll(btn.dataset.id, btn.dataset.name));
    });
    document.querySelectorAll('[data-close-enroll]').forEach(btn => {
        btn.addEventListener('click', closeEnroll);
    });
    document.addEventListener('keydown', e => { if (e.key === 'Escape') closeEnroll(); });
})();
</script>
<script src="/assets/js/input-rules.js"></script>
</body>
</html>
