<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NeivActiva - Mi QR</title>
    <link rel="stylesheet" href="/assets/css/neivactiva-2026.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="/assets/css/views/mis_qr.css">
</head>
<body>

<?php include 'partials/sidebar.php'; ?>

<main class="main-wrapper">
    <nav class="top-navbar">
        <h1 class="page-title">Mi QR</h1>
        <div class="header-actions">
            <a href="?view=inscripcion" class="btn btn-primary">
                <i class="bi bi-ticket-perforated"></i> Nueva inscripcion
            </a>
        </div>
    </nav>

    <div class="dashboard-content">
        <section class="qr-hero">
            <div>
                <span class="qr-eyebrow">Acceso digital</span>
                <h2>Tus codigos QR de eventos</h2>
                <p>Cada QR esta asociado a una inscripcion y puede validarse en el control de asistencia del evento.</p>
            </div>
            <div class="qr-hero-icon">
                <i class="bi bi-qr-code"></i>
            </div>
        </section>

        <?php if (isset($_GET['correo'])): ?>
            <div class="qr-alert success">QR enviado al correo registrado.</div>
        <?php elseif (isset($_GET['correo_error'])): ?>
            <div class="qr-alert error">No se pudo enviar el correo. Tu QR sigue disponible para descarga.</div>
        <?php endif; ?>

        <?php if (empty($mis_qr)): ?>
            <section class="qr-empty">
                <i class="bi bi-qr-code-scan"></i>
                <h3>Aun no tienes QR disponibles</h3>
                <p>Cuando te inscribas a un evento, tu codigo QR aparecera aqui automaticamente.</p>
                <a href="?view=calendario" class="btn btn-primary">
                    <i class="bi bi-calendar3"></i> Explorar eventos
                </a>
            </section>
        <?php else: ?>
            <section class="qr-grid">
                <?php foreach ($mis_qr as $qr): ?>
                    <?php
                        $tokenQr = $qr['token_qr'] ?? $qr['datos_qr'];
                        $verQrUrl = '?view=ver_qr&id=' . (int) $qr['id'] . '&token=' . urlencode($tokenQr);
                        $descargarQrUrl = '?view=descargar_qr&id=' . (int) $qr['id'] . '&token=' . urlencode($tokenQr);
                        $estadoInscripcion = $qr['estado_inscripcion'] ?? 'Confirmada';
                        $estadoClase = strtolower($estadoInscripcion) === 'confirmada' ? 'confirmada' : 'cancelada';
                    ?>
                    <article class="qr-card">
                        <div class="qr-card-main">
                            <div class="qr-image-box">
                                <img src="<?php echo htmlspecialchars($verQrUrl); ?>" alt="QR de <?php echo htmlspecialchars($qr['evento_titulo']); ?>">
                            </div>
                            <div class="qr-card-info">
                                <span class="qr-status <?php echo $estadoClase; ?>"><?php echo htmlspecialchars($estadoInscripcion); ?></span>
                                <h3><?php echo htmlspecialchars($qr['evento_titulo']); ?></h3>
                                <div class="qr-meta">
                                    <span><i class="bi bi-calendar-event"></i> <?php echo date('d/m/Y', strtotime($qr['fecha_evento'])); ?></span>
                                    <span><i class="bi bi-person-badge"></i> Inscripcion #<?php echo (int) $qr['id']; ?></span>
                                </div>
                                <p class="qr-note">Presenta este codigo en la entrada del evento. El escaner validara la inscripcion, el evento asociado y el estado del participante.</p>
                            </div>
                        </div>

                        <div class="qr-actions">
                            <a class="btn-qr primary" href="<?php echo htmlspecialchars($descargarQrUrl); ?>">
                                <i class="bi bi-download"></i> Descargar QR
                            </a>
                            <form action="?view=enviar_qr" method="POST">
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken ?? ''); ?>">
                                <input type="hidden" name="id" value="<?php echo (int) $qr['id']; ?>">
                                <input type="hidden" name="token" value="<?php echo htmlspecialchars($tokenQr); ?>">
                                <input type="hidden" name="volver" value="mis_qr">
                                <button type="submit" class="btn-qr">
                                    <i class="bi bi-envelope-check"></i> Enviar al correo
                                </button>
                            </form>
                            <a class="btn-qr subtle" href="?view=detalle_evento&id=<?php echo (int) $qr['evento_id']; ?>">
                                <i class="bi bi-info-circle"></i> Ver detalles del evento
                            </a>
                        </div>
                    </article>
                <?php endforeach; ?>
            </section>
        <?php endif; ?>
    </div>
</main>

</body>
</html>

