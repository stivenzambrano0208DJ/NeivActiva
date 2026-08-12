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

        <section class="enroll-container">
            <div class="enroll-header">
                <div>
                    <span class="enroll-kicker">Registro</span>
                    <h2>Datos de inscripcion</h2>
                </div>
                <span class="secure-chip"><i class="bi bi-shield-check"></i> Proceso seguro</span>
            </div>

            <?php if (!empty($lista_eventos)): ?>
            <article class="selected-event-card" id="selectedEventCard">
                <div class="event-card-icon"><i class="bi bi-calendar2-check"></i></div>
                <div class="event-card-content">
                    <span class="event-card-label">Evento seleccionado</span>
                    <h3 id="summaryTitle"><?php echo htmlspecialchars($eventoResumen['titulo'] ?? 'Selecciona un evento'); ?></h3>
                    <div class="event-card-meta">
                        <span><i class="bi bi-calendar-event"></i><strong id="summaryDate"><?php echo htmlspecialchars($formatearFecha($eventoResumen['fecha_evento'] ?? '')); ?></strong></span>
                        <span><i class="bi bi-clock"></i><strong id="summaryTime"><?php echo htmlspecialchars($formatearHora($eventoResumen['hora_evento'] ?? '')); ?></strong></span>
                        <span><i class="bi bi-geo-alt"></i><strong id="summaryLocation"><?php echo htmlspecialchars($eventoResumen['ubicacion'] ?? 'Lugar por confirmar'); ?></strong></span>
                        <span><i class="bi bi-ticket-perforated"></i><strong id="summarySeats"><?php echo max(0, (int) ($eventoResumen['cupo_maximo'] ?? 0) - (int) ($eventoResumen['inscritos_actuales'] ?? 0)); ?></strong> cupos</span>
                    </div>
                </div>
            </article>
            <?php endif; ?>

            <form action="?view=inscripcion" method="POST" class="enrollment-form">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken ?? ''); ?>">
                <div class="form-section-title">
                    <i class="bi bi-person-badge"></i>
                    <span>Participante</span>
                </div>
                
                <div class="form-grid-two">
                    <div class="input-group-modern">
                        <label>Nombre</label>
                        <div class="input-wrapper">
                            <i class="bi bi-person"></i>
                            <input type="text" name="nombre_completo" class="form-control-modern" placeholder="Juan Perez" minlength="2" maxlength="255" required>
                        </div>
                    </div>
                    <div class="input-group-modern">
                        <label>Correo electronico</label>
                        <div class="input-wrapper">
                            <i class="bi bi-envelope"></i>
                            <input type="email" name="correo_electronico" id="correoParticipante" class="form-control-modern" placeholder="juan@correo.com" maxlength="255">
                        </div>
                    </div>
                </div>

                <div class="form-grid-two">
                    <div class="input-group-modern">
                        <label>Documento</label>
                        <div class="input-wrapper">
                            <i class="bi bi-card-text"></i>
                            <input type="text" name="documento_identidad" id="documentoParticipante" class="form-control-modern" placeholder="C.C. / T.I." minlength="4" maxlength="50" required>
                        </div>
                    </div>
                    <div class="input-group-modern">
                        <label>Telefono</label>
                        <div class="input-wrapper">
                            <i class="bi bi-phone"></i>
                            <input type="tel" name="telefono" class="form-control-modern" placeholder="+57 300 000 0000" minlength="7" maxlength="20" required>
                        </div>
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
                </div>

                <div class="input-group-modern">
                    <label>Selecciona el evento</label>
                    <div class="input-wrapper select-wrapper">
                        <i class="bi bi-bookmark-star"></i>
                        <select name="evento_id" id="eventoSelect" class="form-control-modern" required>
                            <?php if (empty($lista_eventos)): ?>
                            <option value="">No hay eventos disponibles para inscripcion</option>
                            <?php else: ?>
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
                            <?php endif; ?>
                        </select>
                    </div>
                </div>

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

                <button type="submit" class="submit-enrollment" <?php echo empty($lista_eventos) ? 'disabled' : ''; ?>>
                    <span>Confirmar Inscripcion Segura</span>
                    <i class="bi bi-shield-check"></i>
                </button>
            </form>
        </section>
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

    enrollmentForm?.addEventListener('submit', event => {
        const correoValido = correoInput?.checkValidity() ?? true;
        const documentoValido = (documentoInput?.value.trim().length || 0) >= 4;

        if (!correoValido || !documentoValido) {
            event.preventDefault();
            setAccountStatus('error', 'Verifica correo y documento antes de continuar.');
            return;
        }

        if (accountConflict) {
            event.preventDefault();
            setAccountStatus('error', 'El correo o documento pertenecen a otra cuenta registrada.');
            return;
        }

        if (!validatePasswordsInline()) {
            event.preventDefault();
            setAccountStatus('error', 'Corrige los datos de contrasena antes de continuar.');
        }
    });

    /* ── Render dynamic fields based on selected event ── */
    const eventoSelect = document.getElementById('eventoSelect');
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
</script>

</body>
</html>

