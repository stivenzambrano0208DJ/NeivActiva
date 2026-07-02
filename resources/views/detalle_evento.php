<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NeivActiva - Detalle del Evento</title>
    <link rel="stylesheet" href="/NeivActiva/public/assets/css/neivactiva-2026.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="/NeivActiva/public/assets/css/views/detalle_evento.css">
</head>
<body>

<?php include 'partials/sidebar.php'; ?>

<main class="main-wrapper">
    <nav class="top-navbar">
        <h1 class="page-title">Detalle del Evento</h1>
        <div class="header-actions">
            <a href="?view=mis_qr" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Volver a Mi QR
            </a>
        </div>
    </nav>

    <div class="dashboard-content">
        <?php
            $imagen = !empty($evento['ruta_imagen']) ? $evento['ruta_imagen'] : '/NeivActiva/public/assets/img/Neiva,_La_Gaitana_monumento_emblematico_de_la_ciudad.jpg';
            $cuposLibres = max(0, (int) $evento['cupo_maximo'] - (int) $evento['inscritos_actuales']);
            $horaEvento = !empty($evento['hora_evento']) ? date('g:i A', strtotime($evento['hora_evento'])) : 'Por confirmar';
        ?>
        <article class="detalle-evento">
            <img src="<?php echo htmlspecialchars($imagen); ?>" alt="<?php echo htmlspecialchars($evento['titulo']); ?>">
            <section class="detalle-contenido">
                <span class="detalle-categoria"><?php echo htmlspecialchars($evento['categoria']); ?></span>
                <h2><?php echo htmlspecialchars($evento['titulo']); ?></h2>
                <div class="detalle-meta">
                    <span><i class="bi bi-calendar-event"></i> <?php echo date('d/m/Y', strtotime($evento['fecha_evento'])); ?></span>
                    <span><i class="bi bi-clock"></i> <?php echo htmlspecialchars($horaEvento); ?></span>
                    <span><i class="bi bi-geo-alt-fill"></i> <?php echo htmlspecialchars($evento['ubicacion'] ?? 'Lugar por confirmar'); ?></span>
                    <span><i class="bi bi-people-fill"></i> <?php echo $cuposLibres; ?> cupos libres</span>
                    <span><i class="bi bi-activity"></i> <?php echo htmlspecialchars($evento['estado_evento']); ?></span>
                </div>
                <p><?php echo nl2br(htmlspecialchars($evento['descripcion'] ?? 'Sin descripcion disponible.')); ?></p>
            </section>
        </article>
    </div>
</main>

</body>
</html>

