<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NeivActiva – Gestionar Inscripciones</title>
    <link rel="stylesheet" href="/assets/css/neivactiva-2026.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="/assets/css/views/gestionar_inscripciones.css">
</head>
<body>

<?php include 'partials/sidebar.php'; ?>

<?php
    $totalParticipantes = count($recientes);
    $totalAsistio   = 0;
    $totalPendiente = 0;
    $totalAusente   = 0;

    foreach ($recientes as $registro) {
        $ea = $registro['estado_asistencia'] ?? 'Pendiente';
        if ($ea === 'Asistio')      $totalAsistio++;
        elseif ($ea === 'Ausente')  $totalAusente++;
        else                        $totalPendiente++;
    }

    $iniciales = function($nombre) {
        $partes = preg_split('/\s+/', trim((string) $nombre));
        $ini = '';
        foreach ($partes as $p) {
            if ($p !== '') $ini .= strtoupper(substr($p, 0, 1));
            if (strlen($ini) >= 2) break;
        }
        return $ini ?: 'NA';
    };
?>

<main class="main-wrapper gi-page">

    <!-- ── Topbar ───────────────────────────────── -->
    <header class="gi-topbar">
        <div class="gi-topbar-left">
            <div class="gi-page-icon"><i class="bi bi-person-lines-fill"></i></div>
            <div>
                <h1 class="gi-page-title">Gestionar Inscripciones</h1>
                <p class="gi-page-sub">Control de asistencia y participantes por evento</p>
            </div>
        </div>
        <div class="gi-topbar-right">
            <div class="gi-stat-pill">
                <i class="bi bi-people-fill"></i>
                <span><?php echo $totalParticipantes; ?> inscritos</span>
            </div>
            <div class="gi-stat-pill gi-stat-pill--green">
                <i class="bi bi-check-circle-fill"></i>
                <span><?php echo $totalAsistio; ?> asistieron</span>
            </div>
            <div class="gi-stat-pill gi-stat-pill--amber">
                <i class="bi bi-hourglass-split"></i>
                <span><?php echo $totalPendiente; ?> pendientes</span>
            </div>
            <button class="gi-btn-export" id="exportExcelBtn">
                <i class="bi bi-file-earmark-excel"></i> Exportar Excel
            </button>
        </div>
    </header>

    <!-- ── Filters bar ──────────────────────────── -->
    <div class="gi-filters-bar">
        <div class="gi-filter-group gi-filter-search">
            <i class="bi bi-search"></i>
            <input type="text" id="searchFilter" class="gi-input" placeholder="Buscar por nombre, correo o documento…">
        </div>
        <div class="gi-filter-group">
            <select id="eventFilter" class="gi-select">
                <option value="">Todos los eventos</option>
                <?php foreach ($lista_eventos as $ev): ?>
                    <option value="<?php echo (int)($ev['id'] ?? 0); ?>">
                        <?php echo htmlspecialchars($ev['titulo'] ?? 'Evento'); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="gi-filter-group">
            <select id="statusFilter" class="gi-select">
                <option value="">Todos los estados</option>
                <option value="Asistio">Asistió</option>
                <option value="Pendiente">Pendiente</option>
                <option value="Ausente">Ausente</option>
            </select>
        </div>
        <button class="gi-btn-apply" id="applyFiltersBtn">
            <i class="bi bi-funnel-fill"></i> Filtrar
        </button>
    </div>

    <!-- ── Table card ────────────────────────────── -->
    <div class="gi-card">
        <?php if (empty($recientes)): ?>
            <div class="gi-empty">
                <i class="bi bi-inbox"></i>
                <p>No hay inscripciones registradas todavía.</p>
            </div>
        <?php else: ?>
            <div class="gi-table-wrap">
                <table class="gi-table" id="inscripcionesTable">
                    <thead>
                        <tr>
                            <th>Participante</th>
                            <th>Evento</th>
                            <th>Fecha inscripción</th>
                            <th>Categoría</th>
                            <th>Asistencia</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recientes as $r):
                            $inscId    = (int)($r['id'] ?? 0);
                            $nombre    = htmlspecialchars($r['nombre_completo'] ?? '—');
                            $correo    = htmlspecialchars($r['correo_electronico'] ?? '');
                            $doc       = htmlspecialchars($r['documento_identidad'] ?? '—');
                            $eventoTit = htmlspecialchars($r['evento_titulo'] ?? '—');
                            $eventoId  = (int)($r['evento_id'] ?? 0);
                            $cat       = htmlspecialchars($r['categoria_participacion'] ?? 'General');
                            $ea        = $r['estado_asistencia'] ?? 'Pendiente';
                            $ei        = $r['estado_inscripcion'] ?? 'Confirmada';
                            $fechaInsc = !empty($r['created_at']) ? date('d/m/Y', strtotime($r['created_at'])) : '—';
                            $ini       = $iniciales($r['nombre_completo'] ?? '');
                            $eaClass   = match($ea) {
                                'Asistio' => 'gi-badge--green',
                                'Ausente' => 'gi-badge--red',
                                default   => 'gi-badge--amber'
                            };
                        ?>
                        <tr class="gi-row"
                            data-nombre="<?php echo strtolower($r['nombre_completo'] ?? ''); ?>"
                            data-correo="<?php echo strtolower($r['correo_electronico'] ?? ''); ?>"
                            data-doc="<?php echo $r['documento_identidad'] ?? ''; ?>"
                            data-evento-id="<?php echo $eventoId; ?>"
                            data-estado="<?php echo $ea; ?>">

                            <td class="gi-td-participant">
                                <div class="gi-avatar"><?php echo $ini; ?></div>
                                <div class="gi-participant-info">
                                    <span class="gi-participant-name"><?php echo $nombre; ?></span>
                                    <?php if ($correo): ?>
                                        <span class="gi-participant-sub"><?php echo $correo; ?></span>
                                    <?php endif; ?>
                                    <span class="gi-participant-sub"><?php echo $doc; ?></span>
                                </div>
                            </td>

                            <td>
                                <span class="gi-event-name"><?php echo $eventoTit; ?></span>
                            </td>

                            <td class="gi-td-date"><?php echo $fechaInsc; ?></td>

                            <td>
                                <span class="gi-badge gi-badge--neutral"><?php echo $cat; ?></span>
                            </td>

                            <td>
                                <span class="gi-badge <?php echo $eaClass; ?>">
                                    <?php echo match($ea) { 'Asistio' => 'Asistió', 'Ausente' => 'Ausente', default => 'Pendiente' }; ?>
                                </span>
                            </td>

                            <td class="gi-td-actions">
                                    <form method="POST" action="/admin/inscripciones" class="gi-inline-form">
                                    <input type="hidden" name="accion"          value="actualizar_asistencia">
                                    <input type="hidden" name="inscripcion_id"  value="<?php echo $inscId; ?>">
                                    <input type="hidden" name="csrf_token"      value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">
                                    <select name="estado_asistencia" class="gi-select-sm" onchange="this.form.submit()">
                                        <option value="Pendiente" <?php echo $ea === 'Pendiente' ? 'selected' : ''; ?>>Pendiente</option>
                                        <option value="Asistio"   <?php echo $ea === 'Asistio'   ? 'selected' : ''; ?>>Asistió</option>
                                        <option value="Ausente"   <?php echo $ea === 'Ausente'   ? 'selected' : ''; ?>>Ausente</option>
                                    </select>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="gi-table-footer">
                <span class="gi-count-label" id="countLabel">
                    Mostrando <strong><?php echo $totalParticipantes; ?></strong> inscripciones
                </span>
            </div>
        <?php endif; ?>
    </div>

</main>

<script>
(function () {
    const searchFilter  = document.getElementById('searchFilter');
    const eventFilter   = document.getElementById('eventFilter');
    const statusFilter  = document.getElementById('statusFilter');
    const applyBtn      = document.getElementById('applyFiltersBtn');
    const exportBtn     = document.getElementById('exportExcelBtn');
    const countLabel    = document.getElementById('countLabel');
    const rows          = document.querySelectorAll('#inscripcionesTable tbody .gi-row');

    function applyFilters() {
        const search = (searchFilter?.value || '').toLowerCase().trim();
        const evId   = eventFilter?.value  || '';
        const status = statusFilter?.value || '';
        let visible  = 0;

        rows.forEach(row => {
            const matchSearch = !search ||
                (row.dataset.nombre || '').includes(search) ||
                (row.dataset.correo || '').includes(search) ||
                (row.dataset.doc    || '').includes(search);
            const matchEvent  = !evId   || row.dataset.eventoId === evId;
            const matchStatus = !status || row.dataset.estado    === status;

            const show = matchSearch && matchEvent && matchStatus;
            row.style.display = show ? '' : 'none';
            if (show) visible++;
        });

        if (countLabel) {
            countLabel.innerHTML = 'Mostrando <strong>' + visible + '</strong> inscripciones';
        }
    }

    searchFilter?.addEventListener('input', applyFilters);
    eventFilter?.addEventListener('change', applyFilters);
    statusFilter?.addEventListener('change', applyFilters);
    applyBtn?.addEventListener('click', applyFilters);

    /* ── Excel export ────────────────────────────── */
    exportBtn?.addEventListener('click', function () {
        exportBtn.disabled = true;
        exportBtn.innerHTML = '<i class="bi bi-hourglass-split"></i> Exportando…';

        const headers = ['Participante', 'Correo', 'Documento', 'Evento', 'Fecha', 'Categoría', 'Asistencia'];
        const dataRows = [];

        document.querySelectorAll('#inscripcionesTable tbody .gi-row').forEach(row => {
            const cells = row.querySelectorAll('td');
            if (cells.length < 6) return;
            const name  = cells[0].querySelector('.gi-participant-name')?.textContent.trim() || '';
            const subs  = cells[0].querySelectorAll('.gi-participant-sub');
            const email = subs[0]?.textContent.trim() || '';
            const doc   = subs[1]?.textContent.trim() || '';
            const event = cells[1]?.textContent.trim() || '';
            const date  = cells[2]?.textContent.trim() || '';
            const cat   = cells[3]?.textContent.trim() || '';
            const att   = cells[4]?.textContent.trim() || '';
            dataRows.push([name, email, doc, event, date, cat, att]);
        });

        const escape = s => String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
        const thead  = '<tr>' + headers.map(h => '<th>' + escape(h) + '</th>').join('') + '</tr>';
        const tbody  = dataRows.map(r => '<tr>' + r.map(c => '<td>' + escape(c) + '</td>').join('') + '</tr>').join('');

        const html = '<!DOCTYPE html><html><head><meta charset="UTF-8"></head><body><table border="1">' + thead + tbody + '</table></body></html>';
        const blob = new Blob([html], { type: 'application/vnd.ms-excel;charset=utf-8;' });
        const url  = URL.createObjectURL(blob);
        const a    = document.createElement('a');
        a.href     = url;
        a.download = 'inscripciones_neivactiva_' + new Date().toISOString().slice(0,10) + '.xls';
        document.body.appendChild(a);
        a.click();
        a.remove();
        URL.revokeObjectURL(url);

        setTimeout(() => {
            exportBtn.disabled = false;
            exportBtn.innerHTML = '<i class="bi bi-file-earmark-excel"></i> Exportar Excel';
        }, 600);
    });
})();
</script>
</body>
</html>
