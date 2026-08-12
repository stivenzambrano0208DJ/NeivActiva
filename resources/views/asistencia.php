<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NeivActiva – Control de Asistencia</title>
    <link rel="stylesheet" href="/assets/css/neivactiva-2026.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="/assets/css/views/asistencia.css?v=<?php echo @filemtime(ROOT_PATH . '/public/assets/css/views/asistencia.css'); ?>">
    <!-- Html5-QrCode library -->
    <script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
</head>
<body>

<?php include 'partials/sidebar.php'; ?>

<?php
    $iniciales = function($nombre) {
        $partes = preg_split('/\s+/', trim((string) $nombre));
        $texto  = '';
        foreach ($partes as $parte) {
            if ($parte !== '') $texto .= strtoupper(substr($parte, 0, 1));
            if (strlen($texto) >= 2) break;
        }
        return $texto ?: 'NA';
    };

    $totalRegistros  = count($recientes);
    $totalAsistio    = 0;
    $totalPendiente  = 0;
    foreach ($recientes as $r) {
        if (($r['estado_asistencia'] ?? 'Pendiente') === 'Asistio') $totalAsistio++;
        else $totalPendiente++;
    }
?>

<main class="main-wrapper as-page">

    <!-- ── Topbar ───────────────────────────────── -->
    <header class="as-topbar">
        <div class="as-topbar-left">
            <div class="as-page-icon"><i class="bi bi-qr-code-scan"></i></div>
            <div>
                <h1 class="as-page-title">Control de Asistencia</h1>
                <p class="as-page-sub">Escanea códigos QR en tiempo real para validar ingresos</p>
            </div>
        </div>
        <div class="as-topbar-right">
            <div class="as-stat-pill">
                <i class="bi bi-people-fill"></i>
                <span><?php echo $totalRegistros; ?> registros</span>
            </div>
            <div class="as-stat-pill as-stat-pill--green">
                <i class="bi bi-check-circle-fill"></i>
                <span><?php echo $totalAsistio; ?> asistieron</span>
            </div>
            <div class="as-stat-pill as-stat-pill--amber">
                <i class="bi bi-hourglass-split"></i>
                <span><?php echo $totalPendiente; ?> pendientes</span>
            </div>
            <button class="as-btn-camera" id="cameraToggle">
                <i class="bi bi-camera-video-fill"></i>
                <span>Activar cámara</span>
            </button>
        </div>
    </header>

    <!-- ── Main grid ─────────────────────────────── -->
    <div class="as-grid">

        <!-- ── LEFT: Scanner panel ──────────────── -->
        <div class="as-scanner-card">
            <div class="as-card-header">
                <h2 class="as-card-title"><i class="bi bi-upc-scan"></i> Escáner QR</h2>
                <div class="as-scanner-btns">
                    <button class="as-btn-sm as-btn-start" id="startScanBtn">
                        <i class="bi bi-play-fill"></i> Iniciar
                    </button>
                    <button class="as-btn-sm as-btn-stop" id="stopScanBtn" disabled>
                        <i class="bi bi-stop-fill"></i> Detener
                    </button>
                    <button class="as-btn-sm as-btn-reset" id="resetScanBtn">
                        <i class="bi bi-arrow-counterclockwise"></i>
                    </button>
                </div>
            </div>

            <!-- Viewfinder -->
            <div class="as-viewfinder" id="viewfinder">
                <div class="as-viewfinder-idle" id="viewfinderIdle">
                    <i class="bi bi-qr-code"></i>
                    <p>Activa la cámara para escanear</p>
                </div>
                <div id="qrReader"></div>
            </div>

            <!-- Feedback -->
            <div class="as-feedback" id="scanFeedback">
                <div class="as-feedback-dot as-feedback-idle" id="feedbackDot"></div>
                <div class="as-feedback-text">
                    <span class="as-feedback-status" id="feedbackStatus">Esperando</span>
                    <span class="as-feedback-msg"   id="feedbackMsg">Activa la cámara para empezar</span>
                </div>
            </div>

            <!-- Manual input -->
            <div class="as-manual">
                <p class="as-manual-label">O ingresa el código manualmente:</p>
                <div class="as-manual-row">
                    <input type="text" id="manualCode" class="as-input"
                           placeholder="Pega o escribe el código QR…" autocomplete="off">
                    <button class="as-btn-verify" id="manualVerifyBtn">
                        <i class="bi bi-send-fill"></i> Verificar
                    </button>
                </div>
            </div>
        </div>

        <!-- ── RIGHT: Records table ──────────────── -->
        <div class="as-records-card">
            <div class="as-card-header">
                <h2 class="as-card-title"><i class="bi bi-list-check"></i> Registros de hoy</h2>
                <span class="as-records-count" id="recordsCount">
                    <?php echo $totalRegistros; ?> registros
                </span>
            </div>

            <div class="as-table-wrap">
                <table class="as-table">
                    <thead>
                        <tr>
                            <th>Participante</th>
                            <th>Evento</th>
                            <th>Hora</th>
                            <th>Estado</th>
                            <th>Método</th>
                        </tr>
                    </thead>
                    <tbody id="recordsBody">
                        <?php if (empty($recientes)): ?>
                            <tr class="as-empty-row">
                                <td colspan="5">
                                    <i class="bi bi-inbox"></i>
                                    <span>Aún no hay registros de asistencia</span>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($recientes as $r):
                                $nombre  = htmlspecialchars($r['nombre_completo'] ?? '—');
                                $doc     = htmlspecialchars($r['documento_identidad'] ?? 'Sin documento');
                                $evento  = htmlspecialchars($r['evento_titulo'] ?? '—');
                                $ea      = $r['estado_asistencia'] ?? 'Pendiente';
                                $hora    = !empty($r['asistencia_en'])
                                    ? date('g:i A', strtotime($r['asistencia_en']))
                                    : '—';
                                $ini     = $iniciales($r['nombre_completo'] ?? '');
                                $badgeClass = $ea === 'Asistio' ? 'as-badge--green' : ($ea === 'Ausente' ? 'as-badge--red' : 'as-badge--amber');
                                $metodo = ($ea === 'Asistio' && !empty($r['asistencia_en'])) ? 'QR' : 'Manual';
                                $metodoIco = $metodo === 'QR' ? 'bi-qr-code' : 'bi-person-check';
                            ?>
                            <tr class="as-row" data-inscripcion-id="<?php echo (int)($r['id'] ?? 0); ?>">
                                <td class="as-td-participant">
                                    <div class="as-avatar"><?php echo $ini; ?></div>
                                    <div class="as-pinfo">
                                        <span class="as-pname"><?php echo $nombre; ?></span>
                                        <span class="as-psub"><?php echo $doc; ?></span>
                                    </div>
                                </td>
                                <td class="as-td-event"><?php echo $evento; ?></td>
                                <td class="as-td-time"><?php echo $hora; ?></td>
                                <td>
                                    <span class="as-badge <?php echo $badgeClass; ?>">
                                        <?php echo $ea === 'Asistio' ? 'Asistió' : ($ea === 'Ausente' ? 'Ausente' : 'Pendiente'); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="as-method-badge">
                                        <i class="bi <?php echo $metodoIco; ?>"></i> <?php echo $metodo; ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>

<script>
(function () {
    'use strict';

    /* ── DOM refs ─────────────────────────────────── */
    const cameraToggle   = document.getElementById('cameraToggle');
    const startScanBtn   = document.getElementById('startScanBtn');
    const stopScanBtn    = document.getElementById('stopScanBtn');
    const resetScanBtn   = document.getElementById('resetScanBtn');
    const manualCode     = document.getElementById('manualCode');
    const manualVerifyBtn= document.getElementById('manualVerifyBtn');
    const viewfinderIdle = document.getElementById('viewfinderIdle');
    const feedbackDot    = document.getElementById('feedbackDot');
    const feedbackStatus = document.getElementById('feedbackStatus');
    const feedbackMsg    = document.getElementById('feedbackMsg');
    const recordsBody    = document.getElementById('recordsBody');
    const recordsCount   = document.getElementById('recordsCount');

    /* ── State ────────────────────────────────────── */
    let qrScanner    = null;
    let scannerActive= false;
    let scanLocked   = false;
    let lastCode     = '';

    /* ── Feedback helper ──────────────────────────── */
    function setFeedback(type, status, msg) {
        const classes = { idle:'as-feedback-idle', loading:'as-feedback-loading', ok:'as-feedback-ok', error:'as-feedback-error', warn:'as-feedback-warn' };
        feedbackDot.className = 'as-feedback-dot ' + (classes[type] || classes.idle);
        feedbackStatus.textContent = status;
        feedbackMsg.textContent    = msg;
    }

    /* ── Scanner start/stop ───────────────────────── */
    function startScanner() {
        if (scannerActive) return;

        if (!qrScanner) {
            qrScanner = new Html5Qrcode('qrReader');
        }

        viewfinderIdle.style.display = 'none';
        setFeedback('loading', 'Iniciando…', 'Abriendo la cámara');
        startScanBtn.disabled = true;
        stopScanBtn.disabled  = false;
        cameraToggle.querySelector('span').textContent = 'Detener cámara';

        qrScanner.start(
            { facingMode: 'environment' },
            { fps: 10, qrbox: { width: 250, height: 250 } },
            onScanSuccess,
            () => {}
        ).then(() => {
            scannerActive = true;
            setFeedback('idle', 'Cámara activa', 'Apunta el QR al recuadro');
        }).catch(err => {
            console.error('QR start error:', err);
            setFeedback('error', 'Error', 'No se pudo acceder a la cámara. Verifica los permisos.');
            startScanBtn.disabled = false;
            stopScanBtn.disabled  = true;
            viewfinderIdle.style.display = '';
        });
    }

    function stopScanner() {
        if (!scannerActive || !qrScanner) return;
        qrScanner.stop().then(() => {
            scannerActive = false;
            scanLocked    = false;
            lastCode      = '';
            startScanBtn.disabled = false;
            stopScanBtn.disabled  = true;
            viewfinderIdle.style.display = '';
            cameraToggle.querySelector('span').textContent = 'Activar cámara';
            setFeedback('idle', 'Detenido', 'Cámara desactivada');
        }).catch(console.error);
    }

    /* ── QR scan callback ─────────────────────────── */
    function onScanSuccess(code) {
        if (scanLocked || code === lastCode) return;
        scanLocked = true;
        lastCode   = code;
        setFeedback('loading', 'Verificando…', code.substring(0, 40) + '…');
        verifyCode(code);
    }

    /* ── Verify code (AJAX) ───────────────────────── */
    function verifyCode(code) {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content
            || '<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>';

        fetch('/ajax/buscar-qr', {
            method : 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body   : 'codigo=' + encodeURIComponent(code) + '&csrf_token=' + encodeURIComponent(csrfToken)
        })
        .then(r => r.json())
        .then(data => {
            if (data.ok) {
                setFeedback('ok', '¡Acceso válido!', data.nombre + ' — ' + data.evento);
                if (data.registro) addRecordRow(data.registro, 'QR');
                setTimeout(() => {
                    scanLocked = false;
                    lastCode   = '';
                    setFeedback('idle', 'Listo', 'Escanea el siguiente QR');
                }, 2500);
            } else {
                const isWarn = data.code === 'duplicado' || data.code === 'evento_futuro';
                const statusText = data.code === 'evento_futuro' ? 'Aún no disponible'
                    : (data.code === 'duplicado' ? 'Ya registrado' : 'Acceso denegado');
                setFeedback(isWarn ? 'warn' : 'error',
                    statusText,
                    data.msg || 'QR no reconocido');
                if (data.code === 'duplicado' && data.registro) addRecordRow(data.registro, 'QR');
                setTimeout(() => {
                    scanLocked = false;
                    lastCode   = '';
                    setFeedback('idle', 'Listo', 'Escanea el siguiente QR');
                }, 3000);
            }
        })
        .catch(() => {
            setFeedback('error', 'Error de red', 'No se pudo conectar. Intenta de nuevo.');
            setTimeout(() => { scanLocked = false; lastCode = ''; }, 2500);
        });
    }

    /* ── Add or update a row (upsert by inscription id) ── */
    function addRecordRow(record, method) {
        // Remove empty-state row if present
        const emptyRow = recordsBody.querySelector('.as-empty-row');
        if (emptyRow) emptyRow.remove();

        const inscId = String(record.id || record.inscripcion_id || '');
        const nombre = esc(record.nombre || '—');
        const ini    = (nombre.trim().split(/\s+/).map(w => w[0] || '').join('').toUpperCase()).substring(0, 2) || 'NA';
        const doc    = esc(record.documento || record.doc || 'Sin documento');
        const evento = esc(record.evento || '—');
        const hora   = esc((record.hora && record.hora !== '--:--')
            ? record.hora
            : new Date().toLocaleTimeString('es-CO', { hour:'2-digit', minute:'2-digit' }));
        const estado = record.estado || 'Asistio';
        const bClass = estado === 'Asistio' ? 'as-badge--green' : (estado === 'Ausente' ? 'as-badge--red' : 'as-badge--amber');
        const label  = estado === 'Asistio' ? 'Asistió' : (estado === 'Ausente' ? 'Ausente' : 'Pendiente');
        const methIco= method === 'QR' ? 'bi-qr-code' : 'bi-person-check';

        // If a row for this inscription already exists, update it in place
        // instead of adding a duplicate.
        let tr = inscId ? recordsBody.querySelector('tr[data-inscripcion-id="' + inscId + '"]') : null;

        if (tr) {
            const badge = tr.querySelector('.as-badge');
            if (badge) { badge.className = 'as-badge ' + bClass; badge.textContent = label; }
            const timeCell = tr.querySelector('.as-td-time');
            if (timeCell) timeCell.textContent = hora;
            const methBadge = tr.querySelector('.as-method-badge');
            if (methBadge) methBadge.innerHTML = '<i class="bi ' + methIco + '"></i> ' + esc(method);
            tr.classList.add('as-row-new');
            setTimeout(() => tr.classList.remove('as-row-new'), 1200);
        } else {
            tr = document.createElement('tr');
            tr.className = 'as-row as-row-new';
            if (inscId) tr.dataset.inscripcionId = inscId;
            tr.innerHTML = `
                <td class="as-td-participant">
                    <div class="as-avatar">${ini}</div>
                    <div class="as-pinfo">
                        <span class="as-pname">${nombre}</span>
                        <span class="as-psub">${doc}</span>
                    </div>
                </td>
                <td class="as-td-event">${evento}</td>
                <td class="as-td-time">${hora}</td>
                <td><span class="as-badge ${bClass}">${label}</span></td>
                <td><span class="as-method-badge"><i class="bi ${methIco}"></i> ${esc(method)}</span></td>
            `;
            recordsBody.prepend(tr);
            setTimeout(() => tr.classList.remove('as-row-new'), 1200);
        }

        const total = recordsBody.querySelectorAll('tr:not(.as-empty-row)').length;
        if (recordsCount) recordsCount.textContent = total + ' registros';
    }

    function esc(v) {
        return String(v ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[c]));
    }

    /* ── Event listeners ──────────────────────────── */
    cameraToggle.addEventListener('click', () => scannerActive ? stopScanner() : startScanner());
    startScanBtn.addEventListener('click', startScanner);
    stopScanBtn .addEventListener('click', stopScanner);

    resetScanBtn.addEventListener('click', () => {
        scanLocked = false;
        lastCode   = '';
        setFeedback('idle', scannerActive ? 'Listo' : 'Esperando', scannerActive ? 'Escanea el siguiente QR' : 'Activa la cámara');
    });

    manualVerifyBtn.addEventListener('click', () => {
        const code = manualCode.value.trim();
        if (!code) return;
        setFeedback('loading', 'Verificando…', code.substring(0, 40));
        verifyCode(code);
        manualCode.value = '';
    });

    manualCode.addEventListener('keydown', e => {
        if (e.key === 'Enter') manualVerifyBtn.click();
    });

    window.addEventListener('beforeunload', () => {
        if (qrScanner && scannerActive) qrScanner.stop().catch(() => {});
    });

})();
</script>
</body>
</html>
