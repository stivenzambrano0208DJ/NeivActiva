<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NeivActiva - Inscripcion</title>
    <link rel="stylesheet" href="/assets/css/neivactiva-2026.css?v=<?php echo @filemtime(ROOT_PATH . '/public/assets/css/neivactiva-2026.css'); ?>">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="/assets/css/views/inscripcion.css?v=<?php echo @filemtime(ROOT_PATH . '/public/assets/css/views/inscripcion.css'); ?>">
</head>
<body>

<?php include 'partials/sidebar.php'; ?>

<?php
    $mensajesError = [
        'evento_cerrado' => 'Este evento ya termino o no tiene cupos disponibles.',
        'obligatorios' => 'Completa todos los datos obligatorios del participante.',
        'correo' => 'Ingresa un correo electronico valido.',
        'duplicado' => 'Ya existe un participante con ese documento o correo. Verifica que ambos datos correspondan a la misma persona.',
        'ya_inscrito' => 'Este participante ya esta inscrito en el evento seleccionado.',
        'registro' => 'No se pudo completar la inscripcion. Intentalo nuevamente.',
        'qr_no_disponible' => 'No se encontro el QR solicitado.',
        'csrf' => 'La sesion expiro. Recarga la pagina e intenta nuevamente.',
        'password_corta' => 'La contrasena debe tener minimo 8 caracteres.',
        'password_confirmacion' => 'La confirmacion de contrasena no coincide.',
        'usuario_correo' => 'Ya existe una cuenta con ese correo electronico.',
        'usuario_documento' => 'Ya existe una cuenta con ese documento.',
        'usuario_conflicto' => 'El correo o documento pertenecen a otra cuenta registrada.'
    ];
    $errorActual = $_GET['error'] ?? '';
    $eventoSeleccionadoId = (int) ($_GET['id'] ?? 0);
    $eventoResumen = $lista_eventos[0] ?? null;
    $puedeCrearCuentaParticipante = false;
    $erroresFormulario = $_SESSION['inscripcion_errores'] ?? [];
    unset($_SESSION['inscripcion_errores']);

    foreach ($lista_eventos as $eventoDisponible) {
        if ((int) $eventoDisponible['id'] === $eventoSeleccionadoId) {
            $eventoResumen = $eventoDisponible;
            break;
        }
    }

    $formatearFecha = function($fecha) {
        return !empty($fecha) ? date('d/m/Y', strtotime($fecha)) : 'Sin fecha';
    };

    $formatearHora = function($hora) {
        return !empty($hora) ? date('g:i A', strtotime($hora)) : 'Por confirmar';
    };

    // Metadatos visuales por categoria (icono + color de placeholder)
    $catMeta = function($cat) {
        switch (strtolower(trim((string) $cat))) {
            case 'cultural':  return ['icon' => 'bi-palette', 'color' => 'purple'];
            case 'deportivo': return ['icon' => 'bi-trophy', 'color' => 'blue'];
            case 'educativo': return ['icon' => 'bi-mortarboard', 'color' => 'green'];
            case 'social':    return ['icon' => 'bi-people', 'color' => 'teal'];
            case 'musical':   return ['icon' => 'bi-music-note-beamed', 'color' => 'pink'];
            default:          return ['icon' => 'bi-calendar-event', 'color' => 'orange'];
        }
    };

    $resolverImagen = function($ruta) {
        if (empty($ruta)) return null;
        return strpos($ruta, '/') === 0 ? $ruta : '/' . ltrim($ruta, '/');
    };

    // Lista de categorias unicas para los chips de filtro
    $categoriasDisponibles = [];
    foreach ($lista_eventos as $eventoDisponible) {
        $catNombre = trim((string) ($eventoDisponible['categoria'] ?? ''));
        if ($catNombre !== '' && !in_array($catNombre, $categoriasDisponibles, true)) {
            $categoriasDisponibles[] = $catNombre;
        }
    }
?>

<main class="main-wrapper">
    <nav class="top-navbar enrollment-navbar">
        <div>
            <h1 class="page-title">Inscripcion a Evento</h1>
            <p class="page-subtitle">Reserva tu cupo y genera tu QR de acceso.</p>
        </div>
        <div class="header-actions">
            <a href="?view=dashboard" class="btn-back-dashboard">
                <i class="bi bi-arrow-left"></i> Volver al inicio
            </a>
        </div>
    </nav>

    <div class="dashboard-content enrollment-page">
        <?php if (isset($_GET['exito'])): ?>
        <div class="success-banner">
            <i class="bi bi-check-circle-fill"></i>
            <div>
                <h4>Inscripcion completada</h4>
                <p>Tu cupo fue reservado y tu codigo QR ya esta listo.</p>
            </div>
        </div>
        <?php endif; ?>

        <?php if (isset($_GET['correo'])): ?>
        <div class="form-alert success">El QR fue enviado al correo registrado.</div>
        <?php elseif (isset($_GET['correo_error'])): ?>
        <div class="form-alert error">La inscripcion sigue confirmada, pero no se pudo enviar el correo. Revisa la configuracion de correo del servidor.</div>
        <?php endif; ?>

        <?php if (isset($mensajesError[$errorActual])): ?>
        <div class="form-alert error"><?php echo $mensajesError[$errorActual]; ?></div>
        <?php endif; ?>

        <?php if (!empty($erroresFormulario)): ?>
        <div class="form-alert error">
            <?php foreach ($erroresFormulario as $errorFormulario): ?>
                <div><?php echo htmlspecialchars($errorFormulario); ?></div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <?php if (($_GET['cuenta'] ?? '') === 'existente'): ?>
        <div class="form-alert info">El participante ya tiene una cuenta registrada. La inscripcion fue asociada a esa cuenta.</div>
        <?php elseif (($_GET['cuenta'] ?? '') === 'creada'): ?>
        <div class="form-alert success">Cuenta del participante creada. Se envio acceso y confirmacion al correo registrado.</div>
        <?php endif; ?>

        <?php if (!empty($ultimaInscripcion) && isset($_GET['exito'])): ?>
        <?php
            $qrToken = $_SESSION['ultimo_qr_token'] ?? ($ultimaInscripcion['token_qr'] ?? $ultimaInscripcion['datos_qr']);
            $qrUrl = str_replace('/public/', '/', $ultimaInscripcion['ruta_qr']);
            $downloadUrl = '?view=descargar_qr&id=' . (int) $ultimaInscripcion['id'] . '&token=' . urlencode($qrToken);
        ?>
        <section class="qr-success-card">
            <div class="qr-preview">
                <img src="<?php echo htmlspecialchars($qrUrl); ?>" alt="Codigo QR de inscripcion">
            </div>
            <div class="qr-success-info">
                <span class="qr-kicker">QR de acceso</span>
                <h2><?php echo htmlspecialchars($ultimaInscripcion['nombre_completo']); ?></h2>
                <p><?php echo htmlspecialchars($ultimaInscripcion['evento_titulo']); ?> - <?php echo date('d/m/Y', strtotime($ultimaInscripcion['fecha_evento'])); ?></p>
                <div class="qr-actions">
                    <a class="btn btn-primary" href="<?php echo htmlspecialchars($downloadUrl); ?>">
                        <i class="bi bi-download"></i> Descargar QR
                    </a>
                    <form action="?view=enviar_qr" method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken ?? ''); ?>">
                        <input type="hidden" name="id" value="<?php echo (int) $ultimaInscripcion['id']; ?>">
                        <input type="hidden" name="token" value="<?php echo htmlspecialchars($qrToken); ?>">
                        <button type="submit" class="btn btn-secondary-modern">
                            <i class="bi bi-envelope-check"></i> Enviar al correo
                        </button>
                    </form>
                </div>
            </div>
        </section>

        <?php if (isset($_GET['download'])): ?>
        <iframe src="<?php echo htmlspecialchars($downloadUrl); ?>" style="display:none;" title="Descarga QR"></iframe>
        <?php endif; ?>
        <?php endif; ?>

        <?php
            // Datos del usuario logueado (autocompletado de la inscripcion)
            $miNombre = trim((string) ($usuarioInscripcion['nombre'] ?? ($_SESSION['usuario_nombre'] ?? '')));
            $miCorreo = trim((string) ($usuarioInscripcion['correo'] ?? ($_SESSION['usuario_correo'] ?? '')));
            $miDocumento = trim((string) ($usuarioInscripcion['documento_identidad'] ?? ''));
            $miTelefono = trim((string) ($usuarioInscripcion['telefono'] ?? ''));
            $perfilCompleto = (strlen($miNombre) >= 3 && strlen($miDocumento) >= 4);
        ?>
        <section class="enroll-container">
            <?php if (!$perfilCompleto): ?>
            <div class="form-alert error" style="margin-bottom:1rem;">
                <i class="bi bi-exclamation-triangle"></i>
                Para inscribirte automaticamente necesitas tener tu nombre y documento en tu perfil.
                Completa esos datos en tu cuenta e intenta de nuevo.
            </div>
            <?php endif; ?>

            <form action="?view=inscripcion" method="POST" class="enrollment-form">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken ?? ''); ?>">

                <!-- Datos del participante tomados de la cuenta logueada -->
                <input type="hidden" name="nombre_completo" value="<?php echo htmlspecialchars($miNombre, ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="correo_electronico" value="<?php echo htmlspecialchars($miCorreo, ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="documento_identidad" value="<?php echo htmlspecialchars($miDocumento, ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="telefono" value="<?php echo htmlspecialchars($miTelefono, ENT_QUOTES, 'UTF-8'); ?>">

                <div class="me-card">
                    <span class="me-avatar"><?php echo htmlspecialchars(mb_strtoupper(mb_substr($miNombre !== '' ? $miNombre : 'U', 0, 1))); ?></span>
                    <div class="me-info">
                        <span class="me-label">Te inscribes como</span>
                        <strong class="me-name"><?php echo htmlspecialchars($miNombre !== '' ? $miNombre : 'Usuario'); ?></strong>
                        <span class="me-meta">
                            <?php if ($miCorreo !== ''): ?><i class="bi bi-envelope"></i><?php echo htmlspecialchars($miCorreo); ?><?php endif; ?>
                            <?php if ($miDocumento !== ''): ?><i class="bi bi-card-text"></i><?php echo htmlspecialchars($miDocumento); ?><?php endif; ?>
                        </span>
                    </div>
                </div>

                <?php if ($puedeCrearCuentaParticipante): ?>
                <section class="participant-account-box" id="participantAccountBox" aria-labelledby="participantAccountTitle">
                    <header class="account-summary">
                        <input type="hidden" name="crear_cuenta_participante" id="crearCuentaParticipante" value="1">
                        <span class="account-icon" aria-hidden="true"><i class="bi bi-person-plus"></i></span>
                        <div class="account-copy">
                            <h3 id="participantAccountTitle">Cuenta de participante automatica</h3>
                            <p>Si no existe una cuenta, se creara con rol participante y acceso por correo.</p>
                        </div>
                    </header>

                    <div class="account-status idle" id="accountStatus" role="status" aria-live="polite">
                        <i class="bi bi-info-circle"></i>
                        <span>Ingresa correo y documento para verificar si ya existe una cuenta.</span>
                    </div>

                    <div class="account-password-fields" id="accountPasswordFields" hidden>
                        <div class="account-password-grid">
                            <div class="input-group-modern">
                                <label>Contrasena</label>
                                <div class="input-wrapper">
                                    <i class="bi bi-lock"></i>
                                    <input type="password" name="password_participante" id="passwordParticipante" class="form-control-modern" placeholder="Minimo 8 caracteres" minlength="8" autocomplete="new-password">
                                </div>
                                <small class="field-error" id="passwordError"></small>
                            </div>
                            <div class="input-group-modern">
                                <label>Confirmar contrasena</label>
                                <div class="input-wrapper">
                                    <i class="bi bi-shield-lock"></i>
                                    <input type="password" name="password_participante_confirmacion" id="passwordConfirmacionParticipante" class="form-control-modern" placeholder="Repite la contrasena" minlength="8" autocomplete="new-password">
                                </div>
                                <small class="field-error" id="passwordConfirmError"></small>
                            </div>
                        </div>
                        <div class="account-actions">
                            <button type="button" class="btn-password-generate" id="generarPasswordParticipante">
                                <i class="bi bi-magic"></i> Generar contrasena segura
                            </button>
                        </div>
                    </div>
                </section>
                <?php endif; ?>

                <div class="form-section-title">
                    <i class="bi bi-calendar-event"></i>
                    <span>Evento</span>
                    <?php if (!empty($lista_eventos)): ?>
                    <span class="section-count"><?php echo count($lista_eventos); ?> disponibles</span>
                    <?php endif; ?>
                </div>

                <?php if (empty($lista_eventos)): ?>
                <div class="event-picker-empty">
                    <i class="bi bi-calendar-x"></i>
                    <p>No hay eventos disponibles para inscripcion en este momento.</p>
                </div>
                <select name="evento_id" id="eventoSelect" class="event-select-hidden" aria-hidden="true">
                    <option value="">No hay eventos disponibles</option>
                </select>
                <?php else: ?>

                <!-- Barra de filtro: busqueda + categorias -->
                <div class="event-filter-bar">
                    <div class="event-search">
                        <i class="bi bi-search"></i>
                        <input type="text" id="eventSearch" placeholder="Busca por nombre, lugar o categoria..." autocomplete="off" aria-label="Buscar evento">
                    </div>
                    <?php if (count($categoriasDisponibles) > 1): ?>
                    <div class="event-filter-chips" id="eventFilterChips" role="group" aria-label="Filtrar por categoria">
                        <button type="button" class="event-chip is-active" data-cat="all">
                            <i class="bi bi-grid"></i> Todos
                        </button>
                        <?php foreach ($categoriasDisponibles as $catNombre): ?>
                        <?php $chipMeta = $catMeta($catNombre); ?>
                        <button type="button" class="event-chip" data-cat="<?php echo htmlspecialchars(strtolower($catNombre), ENT_QUOTES, 'UTF-8'); ?>">
                            <i class="bi <?php echo $chipMeta['icon']; ?>"></i> <?php echo htmlspecialchars($catNombre); ?>
                        </button>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Galeria de eventos seleccionables -->
                <div class="event-picker-grid" id="eventPickerGrid" role="radiogroup" aria-label="Selecciona el evento">
                    <?php foreach ($lista_eventos as $e): ?>
                    <?php
                        $eid = (int) $e['id'];
                        $cuposLibres = max(0, (int) $e['cupo_maximo'] - (int) $e['inscritos_actuales']);
                        $catEvento = trim((string) ($e['categoria'] ?? ''));
                        $meta = $catMeta($catEvento);
                        $imgReal = $resolverImagen($e['ruta_imagen'] ?? '');
                        $isSelected = ($eventoSeleccionadoId && $eventoSeleccionadoId === $eid)
                            || (!$eventoSeleccionadoId && $eventoResumen && (int) $eventoResumen['id'] === $eid);
                        $searchStr = strtolower($e['titulo'] . ' ' . ($e['ubicacion'] ?? '') . ' ' . $catEvento);
                    ?>
                    <button type="button"
                            class="event-pick-card <?php echo $isSelected ? 'is-selected' : ''; ?>"
                            data-id="<?php echo $eid; ?>"
                            data-cat="<?php echo htmlspecialchars(strtolower($catEvento), ENT_QUOTES, 'UTF-8'); ?>"
                            data-search="<?php echo htmlspecialchars($searchStr, ENT_QUOTES, 'UTF-8'); ?>"
                            role="radio"
                            aria-checked="<?php echo $isSelected ? 'true' : 'false'; ?>">
                        <span class="epc-media epc-ph-<?php echo $meta['color']; ?>">
                            <?php if ($imgReal): ?>
                            <img src="<?php echo htmlspecialchars($imgReal); ?>" alt="<?php echo htmlspecialchars($e['titulo']); ?>" loading="lazy" onerror="this.remove()">
                            <?php endif; ?>
                            <i class="bi <?php echo $meta['icon']; ?> epc-ph-icon"></i>
                            <?php if ($catEvento !== ''): ?>
                            <span class="epc-cat"><?php echo htmlspecialchars($catEvento); ?></span>
                            <?php endif; ?>
                            <span class="epc-seats-badge <?php echo $cuposLibres <= 5 ? 'is-low' : ''; ?>">
                                <i class="bi bi-ticket-perforated"></i> <?php echo $cuposLibres; ?>
                            </span>
                            <span class="epc-check"><i class="bi bi-check-lg"></i></span>
                        </span>
                        <span class="epc-body">
                            <span class="epc-title"><?php echo htmlspecialchars($e['titulo']); ?></span>
                            <span class="epc-meta">
                                <span><i class="bi bi-calendar-event"></i><?php echo htmlspecialchars($formatearFecha($e['fecha_evento'])); ?></span>
                                <span><i class="bi bi-clock"></i><?php echo htmlspecialchars($formatearHora($e['hora_evento'] ?? '')); ?></span>
                                <span><i class="bi bi-geo-alt"></i><?php echo htmlspecialchars($e['ubicacion'] ?? 'Por confirmar'); ?></span>
                            </span>
                        </span>
                    </button>
                    <?php endforeach; ?>
                </div>
                <p class="event-empty-msg" id="eventEmptyMsg" hidden>
                    <i class="bi bi-search"></i> No se encontraron eventos con ese criterio.
                </p>

                <!-- Select oculto sincronizado (mantiene la compatibilidad del formulario) -->
                <select name="evento_id" id="eventoSelect" class="event-select-hidden" required aria-hidden="true" tabindex="-1">
                    <?php foreach ($lista_eventos as $e): ?>
                    <?php
                        $cuposLibres = max(0, (int) $e['cupo_maximo'] - (int) $e['inscritos_actuales']);
                        $fechaTexto = $formatearFecha($e['fecha_evento']);
                        $horaTexto = $formatearHora($e['hora_evento'] ?? '');
                        $selected = ($eventoSeleccionadoId && $eventoSeleccionadoId === (int) $e['id']) ? 'selected' : '';
                    ?>
                    <option value="<?php echo (int) $e['id']; ?>"
                            <?php echo $selected; ?>
                            data-title="<?php echo htmlspecialchars($e['titulo'], ENT_QUOTES, 'UTF-8'); ?>"
                            data-date="<?php echo htmlspecialchars($fechaTexto, ENT_QUOTES, 'UTF-8'); ?>"
                            data-time="<?php echo htmlspecialchars($horaTexto, ENT_QUOTES, 'UTF-8'); ?>"
                            data-location="<?php echo htmlspecialchars($e['ubicacion'] ?? 'Lugar por confirmar', ENT_QUOTES, 'UTF-8'); ?>"
                            data-seats="<?php echo $cuposLibres; ?>"
                            data-campos="<?php echo htmlspecialchars($e['formulario_campos'] ?? '[]'); ?>">
                        <?php echo htmlspecialchars($e['titulo']); ?> - <?php echo $cuposLibres; ?> cupos
                    </option>
                    <?php endforeach; ?>
                </select>
                <?php endif; ?>

                <!-- Contenedor de campos dinámicos -->
                <div id="dynamicFieldsContainer" style="margin-top: 15px; display: flex; flex-direction: column; gap: 15px;"></div>

                <div class="input-group-modern">
                    <label>Categoria de participacion</label>
                    <div class="radio-group">
                        <div class="radio-item">
                            <input type="radio" name="categoria" id="cat-juv" value="Juvenil">
                            <label for="cat-juv" class="radio-label"><i class="bi bi-stars"></i> Juvenil</label>
                        </div>
                        <div class="radio-item">
                            <input type="radio" name="categoria" id="cat-adu" value="Adulto" checked>
                            <label for="cat-adu" class="radio-label"><i class="bi bi-person-check"></i> Adulto</label>
                        </div>
                        <div class="radio-item">
                            <input type="radio" name="categoria" id="cat-sen" value="Senior">
                            <label for="cat-sen" class="radio-label"><i class="bi bi-award"></i> Senior</label>
                        </div>
                    </div>
                </div>

                <button type="submit" class="submit-enrollment" <?php echo (empty($lista_eventos) || !$perfilCompleto) ? 'disabled' : ''; ?>>
                    <span>Inscribirme</span>
                    <i class="bi bi-shield-check"></i>
                </button>
            </form>
        </section>
    </div>

    <!-- Ventana flotante de confirmacion -->
    <div class="confirm-overlay" id="confirmOverlay" hidden>
        <div class="confirm-modal" role="dialog" aria-modal="true" aria-labelledby="confirmModalTitle">
            <button type="button" class="confirm-close" id="confirmClose" aria-label="Cerrar"><i class="bi bi-x-lg"></i></button>
            <div class="confirm-icon"><i class="bi bi-calendar2-check"></i></div>
            <h3 id="confirmModalTitle">Confirmar inscripcion</h3>
            <p class="confirm-sub">Revisa los datos antes de registrarte.</p>
            <div class="confirm-details">
                <div class="confirm-row">
                    <span class="confirm-key"><i class="bi bi-person"></i> Participante</span>
                    <span class="confirm-val"><?php echo htmlspecialchars($miNombre !== '' ? $miNombre : 'Usuario'); ?></span>
                </div>
                <div class="confirm-row">
                    <span class="confirm-key"><i class="bi bi-bookmark-star"></i> Evento</span>
                    <span class="confirm-val" id="confirmEvento">-</span>
                </div>
                <div class="confirm-row">
                    <span class="confirm-key"><i class="bi bi-calendar-event"></i> Fecha</span>
                    <span class="confirm-val" id="confirmFecha">-</span>
                </div>
                <div class="confirm-row">
                    <span class="confirm-key"><i class="bi bi-ticket-perforated"></i> Cupos</span>
                    <span class="confirm-val" id="confirmCupos">-</span>
                </div>
            </div>
            <div class="confirm-actions">
                <button type="button" class="confirm-btn cancel" id="confirmCancel">Cancelar</button>
                <button type="button" class="confirm-btn accept" id="confirmAccept">
                    <i class="bi bi-check-lg"></i> Si, inscribirme
                </button>
            </div>
        </div>
    </div>
</main>

<script>
    const eventoSelect = document.getElementById('eventoSelect');
    const puedeCrearCuenta = <?php echo $puedeCrearCuentaParticipante ? 'true' : 'false'; ?>;
    const correoInput = document.getElementById('correoParticipante');
    const documentoInput = document.getElementById('documentoParticipante');
    const crearCuentaCheck = document.getElementById('crearCuentaParticipante');
    const accountStatus = document.getElementById('accountStatus');
    const passwordFields = document.getElementById('accountPasswordFields');
    const passwordInput = document.getElementById('passwordParticipante');
    const passwordConfirmInput = document.getElementById('passwordConfirmacionParticipante');
    const passwordError = document.getElementById('passwordError');
    const passwordConfirmError = document.getElementById('passwordConfirmError');
    const generarPasswordBtn = document.getElementById('generarPasswordParticipante');
    const csrfToken = document.querySelector('input[name="csrf_token"]')?.value || '';
    const enrollmentForm = document.querySelector('.enrollment-form');
    let accountExists = false;
    let accountConflict = false;
    let verifyTimer = null;

    function updateEventSummary() {
        if (!eventoSelect || !eventoSelect.selectedOptions.length) return;

        const option = eventoSelect.selectedOptions[0];
        const fields = {
            summaryTitle: option.dataset.title || 'Selecciona un evento',
            summaryDate: option.dataset.date || 'Sin fecha',
            summaryTime: option.dataset.time || 'Por confirmar',
            summaryLocation: option.dataset.location || 'Lugar por confirmar',
            summarySeats: option.dataset.seats || '0'
        };

        Object.entries(fields).forEach(([id, value]) => {
            const element = document.getElementById(id);
            if (element) element.textContent = value;
        });

        const card = document.getElementById('selectedEventCard');
        if (card) {
            card.classList.remove('is-updating');
            void card.offsetWidth;
            card.classList.add('is-updating');
        }
    }

    if (eventoSelect) {
        eventoSelect.addEventListener('change', updateEventSummary);
        updateEventSummary();
    }

    function setAccountStatus(type, message) {
        if (!accountStatus) return;
        const icons = {
            idle: 'bi-info-circle',
            loading: 'bi-hourglass-split',
            success: 'bi-check-circle',
            warning: 'bi-exclamation-triangle',
            error: 'bi-x-circle'
        };
        accountStatus.className = `account-status ${type}`;
        accountStatus.innerHTML = `<i class="bi ${icons[type] || icons.idle}"></i><span>${message}</span>`;
    }

    function togglePasswordFields(forceHide = false) {
        if (!passwordFields || !crearCuentaCheck) return;
        const visible = !accountExists && !forceHide;
        passwordFields.hidden = !visible;
        [passwordInput, passwordConfirmInput].forEach(input => {
            if (!input) return;
            input.required = visible;
            if (!visible) input.value = '';
        });
    }

    async function verifyParticipantAccount() {
        if (!puedeCrearCuenta || !correoInput || !documentoInput) return;
        const correo = correoInput.value.trim();
        const documento = documentoInput.value.trim();

        if (correo.length < 5 && documento.length < 4) {
            setAccountStatus('idle', 'Ingresa correo y documento para verificar si ya existe una cuenta.');
            if (crearCuentaCheck) crearCuentaCheck.disabled = false;
            togglePasswordFields(false);
            return;
        }

        setAccountStatus('loading', 'Verificando cuenta del participante...');
        const body = new URLSearchParams();
        body.set('correo_electronico', correo);
        body.set('documento_identidad', documento);
        body.set('csrf_token', csrfToken);

        try {
            const response = await fetch('?view=verificar_participante_cuenta', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body
            });
            const data = await response.json();
            accountExists = Boolean(data.exists);
            accountConflict = Boolean(data.conflict);

            if (data.exists) {
                togglePasswordFields(true);
                setAccountStatus(data.conflict ? 'error' : 'success', data.msg || 'El participante ya tiene una cuenta registrada.');
            } else {
                setAccountStatus('warning', 'No se encontro cuenta. Se creara automaticamente con rol participante.');
                togglePasswordFields(false);
            }
        } catch (error) {
            accountExists = false;
            accountConflict = true;
            setAccountStatus('error', 'No se pudo verificar la cuenta. Intenta nuevamente.');
        }
    }

    function validatePasswordsInline() {
        if (!puedeCrearCuenta || accountExists) return true;
        let ok = true;

        passwordError.textContent = '';
        passwordConfirmError.textContent = '';

        if ((passwordInput.value || '').length < 8) {
            passwordError.textContent = 'Minimo 8 caracteres.';
            ok = false;
        }

        if (passwordInput.value !== passwordConfirmInput.value) {
            passwordConfirmError.textContent = 'Las contrasenas no coinciden.';
            ok = false;
        }

        return ok;
    }

    if (puedeCrearCuenta && correoInput && documentoInput) {
        [correoInput, documentoInput].forEach(input => {
            input.addEventListener('input', () => {
                accountExists = false;
                accountConflict = false;
                clearTimeout(verifyTimer);
                verifyTimer = setTimeout(verifyParticipantAccount, 450);
            });
            input.addEventListener('blur', verifyParticipantAccount);
        });
    }

    if (generarPasswordBtn) {
        generarPasswordBtn.addEventListener('click', () => {
            const charset = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789@$%';
            const values = new Uint32Array(14);
            crypto.getRandomValues(values);
            const password = Array.from(values, value => charset[value % charset.length]).join('');
            passwordInput.value = password;
            passwordConfirmInput.value = password;
            validatePasswordsInline();
        });
    }

    /* ── Confirmacion en ventana flotante ── */
    const confirmOverlay = document.getElementById('confirmOverlay');
    const confirmEvento = document.getElementById('confirmEvento');
    const confirmFecha = document.getElementById('confirmFecha');
    const confirmCupos = document.getElementById('confirmCupos');
    const confirmAccept = document.getElementById('confirmAccept');
    const confirmCancel = document.getElementById('confirmCancel');
    const confirmClose = document.getElementById('confirmClose');

    function openConfirmModal() {
        if (!confirmOverlay || !eventoSelect || !eventoSelect.selectedOptions.length) return;
        const opt = eventoSelect.selectedOptions[0];
        if (confirmEvento) confirmEvento.textContent = opt.dataset.title || '-';
        if (confirmFecha) confirmFecha.textContent = `${opt.dataset.date || ''} · ${opt.dataset.time || ''}`.trim();
        if (confirmCupos) confirmCupos.textContent = `${opt.dataset.seats || '0'} disponibles`;
        confirmOverlay.hidden = false;
        document.body.style.overflow = 'hidden';
    }

    function closeConfirmModal() {
        if (!confirmOverlay) return;
        confirmOverlay.hidden = true;
        document.body.style.overflow = '';
    }

    // Interceptar el envio para mostrar la confirmacion primero
    enrollmentForm?.addEventListener('submit', event => {
        event.preventDefault();
        if (!eventoSelect || !eventoSelect.value) return;
        openConfirmModal();
    });

    // Confirmar -> enviar de verdad (submit() no vuelve a disparar el evento)
    confirmAccept?.addEventListener('click', () => {
        confirmAccept.disabled = true;
        confirmAccept.innerHTML = '<i class="bi bi-hourglass-split"></i> Procesando...';
        enrollmentForm.submit();
    });

    confirmCancel?.addEventListener('click', closeConfirmModal);
    confirmClose?.addEventListener('click', closeConfirmModal);
    confirmOverlay?.addEventListener('click', event => {
        if (event.target === confirmOverlay) closeConfirmModal();
    });
    document.addEventListener('keydown', event => {
        if (event.key === 'Escape' && confirmOverlay && !confirmOverlay.hidden) closeConfirmModal();
    });

    /* ── Render dynamic fields based on selected event ── */
    const dynamicFieldsContainer = document.getElementById('dynamicFieldsContainer');

    function renderDynamicFields() {
        dynamicFieldsContainer.innerHTML = '';
        const selectedOption = eventoSelect.options[eventoSelect.selectedIndex];
        if (!selectedOption) return;

        let campos = [];
        try {
            campos = JSON.parse(selectedOption.dataset.campos || '[]');
        } catch(e) {
            campos = [];
        }

        if (campos.length > 0) {
            const sectionTitle = document.createElement('div');
            sectionTitle.className = 'form-section-title';
            sectionTitle.innerHTML = '<i class="bi bi-gear-fill"></i><span>Información Adicional Requerida</span>';
            dynamicFieldsContainer.appendChild(sectionTitle);
        }

        campos.forEach(c => {
            const group = document.createElement('div');
            group.className = 'input-group-modern';

            const label = document.createElement('label');
            label.textContent = c.label;
            if (c.required) {
                const req = document.createElement('span');
                req.style.color = 'var(--danger)';
                req.textContent = ' *';
                label.appendChild(req);
            }
            group.appendChild(label);

            const wrapper = document.createElement('div');
            wrapper.className = 'input-wrapper';

            if (c.type === 'select') {
                wrapper.classList.add('select-wrapper');
                const icon = document.createElement('i');
                icon.className = 'bi bi-list-task';
                wrapper.appendChild(icon);

                const select = document.createElement('select');
                select.name = `custom_${c.label.replace(/[^a-zA-Z0-9]/g, '_')}`;
                select.className = 'form-control-modern';
                if (c.required) select.required = true;

                const defaultOpt = document.createElement('option');
                defaultOpt.value = '';
                defaultOpt.textContent = 'Seleccionar...';
                select.appendChild(defaultOpt);

                c.options.forEach(opt => {
                    const option = document.createElement('option');
                    option.value = opt;
                    option.textContent = opt;
                    select.appendChild(option);
                });
                wrapper.appendChild(select);
            } else {
                const icon = document.createElement('i');
                icon.className = 'bi bi-pencil-square';
                wrapper.appendChild(icon);

                const input = document.createElement('input');
                input.type = 'text';
                input.name = `custom_${c.label.replace(/[^a-zA-Z0-9]/g, '_')}`;
                input.className = 'form-control-modern';
                input.placeholder = `Ingresa tu ${c.label}`;
                if (c.required) input.required = true;
                wrapper.appendChild(input);
            }

            group.appendChild(wrapper);
            dynamicFieldsContainer.appendChild(group);
        });
    }

    if (eventoSelect) {
        eventoSelect.addEventListener('change', renderDynamicFields);
        renderDynamicFields();
    }

    /* ── Galeria de eventos: seleccion por tarjeta + filtro ── */
    const eventPickerGrid = document.getElementById('eventPickerGrid');
    const eventSearch = document.getElementById('eventSearch');
    const eventFilterChips = document.getElementById('eventFilterChips');
    const eventEmptyMsg = document.getElementById('eventEmptyMsg');
    let activeCat = 'all';

    function selectEventCard(id) {
        if (!eventoSelect || !id) return;
        eventoSelect.value = String(id);
        eventoSelect.dispatchEvent(new Event('change'));
        eventPickerGrid?.querySelectorAll('.event-pick-card').forEach(card => {
            const on = card.dataset.id === String(id);
            card.classList.toggle('is-selected', on);
            card.setAttribute('aria-checked', on ? 'true' : 'false');
        });
    }

    if (eventPickerGrid) {
        eventPickerGrid.addEventListener('click', event => {
            const card = event.target.closest('.event-pick-card');
            if (!card) return;
            selectEventCard(card.dataset.id);
            document.getElementById('selectedEventCard')?.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        });
    }

    function applyEventFilter() {
        if (!eventPickerGrid) return;
        const term = (eventSearch?.value || '').trim().toLowerCase();
        let visible = 0;
        eventPickerGrid.querySelectorAll('.event-pick-card').forEach(card => {
            const matchCat = activeCat === 'all' || card.dataset.cat === activeCat;
            const matchTerm = !term || (card.dataset.search || '').includes(term);
            const show = matchCat && matchTerm;
            card.style.display = show ? '' : 'none';
            if (show) visible++;
        });
        if (eventEmptyMsg) eventEmptyMsg.hidden = visible !== 0;
    }

    eventSearch?.addEventListener('input', applyEventFilter);

    if (eventFilterChips) {
        eventFilterChips.addEventListener('click', event => {
            const chip = event.target.closest('.event-chip');
            if (!chip) return;
            activeCat = chip.dataset.cat;
            eventFilterChips.querySelectorAll('.event-chip').forEach(c => {
                c.classList.toggle('is-active', c === chip);
            });
            applyEventFilter();
        });
    }
</script>

</body>
</html>

