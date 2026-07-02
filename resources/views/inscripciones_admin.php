<?php
$msgOk  = ['inscrito' => 'Participante inscrito y QR generado correctamente.'];
$msgErr = ['csrf'=>'Sesión expirada.','datos'=>'Selecciona participante y evento válidos.','duplicado'=>'El participante ya está inscrito en ese evento.','cupo'=>'El evento no tiene cupos disponibles.','bd'=>'No se pudo crear la inscripción.'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NeivActiva – Inscripción Directa</title>
    <link rel="stylesheet" href="/NeivActiva/public/assets/css/neivactiva-2026.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="/NeivActiva/public/assets/css/views/inscripciones_admin.css">
</head>
<body>
<?php include 'partials/sidebar.php'; ?>

<main class="main-wrapper ia-page">

    <!-- ── Topbar ───────────────────────────────── -->
    <header class="ia-topbar">
        <div class="ia-topbar-left">
            <div class="ia-page-icon"><i class="bi bi-qr-code"></i></div>
            <div>
                <h1 class="ia-page-title">Inscripción Directa</h1>
                <p class="ia-page-sub">Inscribe participantes manualmente y genera su QR al instante</p>
            </div>
        </div>
        <div class="ia-topbar-right">
            <a href="/NeivActiva/public/admin/participantes" class="ia-btn-secondary">
                <i class="bi bi-people"></i> Participantes
            </a>
            <a href="/NeivActiva/public/admin/carga-masiva" class="ia-btn-secondary">
                <i class="bi bi-upload"></i> Carga masiva
            </a>
        </div>
    </header>

    <!-- ── Toast ────────────────────────────────── -->
    <?php if (!empty($_GET['msg']) && isset($msgOk[$_GET['msg']])): ?>
        <div class="ia-toast ia-toast--ok" id="toastMsg">
            <i class="bi bi-check-circle-fill"></i>
            <span><?php echo $msgOk[$_GET['msg']]; ?></span>
            <button onclick="this.parentElement.remove()"><i class="bi bi-x"></i></button>
        </div>
    <?php elseif (!empty($_GET['error']) && isset($msgErr[$_GET['error']])): ?>
        <div class="ia-toast ia-toast--err" id="toastMsg">
            <i class="bi bi-exclamation-circle-fill"></i>
            <span><?php echo $msgErr[$_GET['error']]; ?></span>
            <button onclick="this.parentElement.remove()"><i class="bi bi-x"></i></button>
        </div>
    <?php endif; ?>

    <!-- ── Grid ─────────────────────────────────── -->
    <div class="ia-grid">

        <!-- ── LEFT: Form ───────────────────────── -->
        <aside class="ia-card ia-form-card">
            <div class="ia-card-header">
                <h2 class="ia-card-title">
                    <i class="bi bi-person-plus-fill"></i> Inscribir participante
                </h2>
            </div>
            <form method="POST" action="/NeivActiva/public/admin/inscripciones-directas" class="ia-form">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">

                <div class="ia-field">
                    <label class="ia-label">Participante <span class="ia-req">*</span></label>
                    <select name="participante_id" class="ia-select" required>
                        <option value="">Seleccionar participante…</option>
                        <?php foreach ($lista_participantes as $p):
                            $pNombre = htmlspecialchars($p['nombre'] ?? $p['nombre_completo'] ?? '');
                            $pDoc    = htmlspecialchars($p['documento'] ?? $p['documento_identidad'] ?? '');
                        ?>
                            <option value="<?php echo (int)$p['id']; ?>">
                                <?php echo $pNombre; ?><?php echo $pDoc ? ' — '.$pDoc : ''; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="ia-field">
                    <label class="ia-label">Evento <span class="ia-req">*</span></label>
                    <select name="evento_id" class="ia-select" required>
                        <option value="">Seleccionar evento…</option>
                        <?php foreach ($lista_eventos as $e): ?>
                            <option value="<?php echo (int)$e['id']; ?>">
                                <?php echo htmlspecialchars($e['titulo']); ?>
                                — <?php echo date('d/m/Y', strtotime($e['fecha_evento'])); ?>
                                (<?php echo max(0,(int)$e['cupo_maximo']-(int)$e['inscritos_actuales']); ?> cupos)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="ia-info-box">
                    <i class="bi bi-info-circle-fill"></i>
                    <p>Se generará automáticamente un código QR y se enviará por correo al participante.</p>
                </div>

                <button type="submit" class="ia-btn-submit">
                    <i class="bi bi-qr-code"></i> Inscribir y generar QR
                </button>
            </form>
        </aside>

        <!-- ── RIGHT: Recent table ──────────────── -->
        <section class="ia-card ia-table-card">
            <div class="ia-card-header">
                <h2 class="ia-card-title">
                    <i class="bi bi-list-check"></i> Inscripciones recientes
                </h2>
                <span class="ia-count-badge"><?php echo count($recientes); ?></span>
            </div>

            <?php if (empty($recientes)): ?>
                <div class="ia-empty">
                    <i class="bi bi-inbox"></i>
                    <p>Aún no hay inscripciones registradas.</p>
                </div>
            <?php else: ?>
                <div class="ia-table-wrap">
                    <table class="ia-table">
                        <thead>
                            <tr>
                                <th>Participante</th>
                                <th>Evento</th>
                                <th>Fecha</th>
                                <th>Asistencia</th>
                                <th>QR</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recientes as $r):
                                $rNombre = htmlspecialchars($r['nombre'] ?? $r['nombre_completo'] ?? '—');
                                $rCorreo = htmlspecialchars($r['correo'] ?? $r['correo_electronico'] ?? '');
                                $rDoc    = htmlspecialchars($r['documento'] ?? $r['documento_identidad'] ?? '');
                                $rEvento = htmlspecialchars($r['evento_titulo'] ?? '—');
                                $rFecha  = !empty($r['fecha_inscripcion']) ? date('d/m/Y', strtotime($r['fecha_inscripcion'])) : '—';
                                $rAsist  = $r['asistencia'] ?? $r['estado_asistencia'] ?? 'Pendiente';
                                $token   = $r['token_qr'] ?? $r['codigo_qr'] ?? $r['datos_qr'] ?? '';
                                $rId     = (int)($r['id'] ?? 0);
                                $aBadge  = $rAsist==='Asistio' ? 'ia-badge--green' : ($rAsist==='Ausente' ? 'ia-badge--red' : 'ia-badge--amber');
                                // initials
                                $parts = preg_split('/\s+/', trim($rNombre));
                                $ini = '';
                                foreach($parts as $pw) { if($pw!=='') $ini.=strtoupper($pw[0]); if(strlen($ini)>=2) break; }
                                $ini = $ini ?: 'NA';
                            ?>
                            <tr class="ia-row">
                                <td class="ia-td-user">
                                    <div class="ia-avatar"><?php echo $ini; ?></div>
                                    <div class="ia-uinfo">
                                        <span class="ia-uname"><?php echo $rNombre; ?></span>
                                        <span class="ia-usub"><?php echo $rCorreo ?: $rDoc; ?></span>
                                    </div>
                                </td>
                                <td class="ia-td-event"><?php echo $rEvento; ?></td>
                                <td class="ia-td-date"><?php echo $rFecha; ?></td>
                                <td>
                                    <span class="ia-badge <?php echo $aBadge; ?>">
                                        <?php echo $rAsist==='Asistio' ? 'Asistió' : ($rAsist==='Ausente' ? 'Ausente' : 'Pendiente'); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if (!empty($r['ruta_qr']) && $token): ?>
                                        <a class="ia-btn-icon ia-btn-dl" title="Descargar QR"
                                           href="?view=descargar_qr&id=<?php echo $rId; ?>&token=<?php echo urlencode($token); ?>">
                                            <i class="bi bi-download"></i>
                                        </a>
                                    <?php else: ?>
                                        <span class="ia-no-qr">Sin QR</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>
    </div>
</main>

<script>
(function(){
    const toast = document.getElementById('toastMsg');
    if (toast) setTimeout(()=>{ toast.style.opacity='0'; toast.style.transition='opacity .3s'; setTimeout(()=>toast.remove(),350); }, 4000);
})();
</script>
</body>
</html>
