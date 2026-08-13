<?php
$ok = [
    'preview' => 'Archivo leido. Revisa el preview antes de importar.',
    'importado' => 'Importacion finalizada.'
];
$err = [
    'csrf' => 'Sesion expirada.',
    'archivo' => 'Sube un archivo CSV o XLSX.',
    'procesar' => 'No se pudo procesar el archivo.'
];

$totalPreview = count($preview);
$validos = 0;
$advertencias = 0;
$erroresPreview = 0;
foreach ($preview as $row) {
    $estadoRow = $row['estado'] ?? (empty($row['errores'] ?? []) ? 'valid' : 'error');
    if ($estadoRow === 'valid') {
        $validos++;
    } elseif ($estadoRow === 'warning') {
        $advertencias++;
    } else {
        $erroresPreview++;
    }
}
$importables = $validos + $advertencias;
$incidencias = $advertencias + $erroresPreview;
$estadoConfig = [
    'valid' => ['label' => 'Valido', 'icon' => 'bi-check2-circle', 'class' => 'valid'],
    'warning' => ['label' => 'Advertencia', 'icon' => 'bi-exclamation-triangle', 'class' => 'warning'],
    'error' => ['label' => 'Error', 'icon' => 'bi-x-circle', 'class' => 'invalid'],
];
$toastType = isset($_GET['error']) ? 'error' : (isset($_GET['msg']) ? 'success' : '');
$toastText = '';
if (isset($_GET['msg'], $ok[$_GET['msg']])) {
    $toastText = $ok[$_GET['msg']];
    if (isset($_GET['ok'])) {
        $toastText .= ' Importados: ' . (int) $_GET['ok'] . '. Omitidos: ' . (int) ($_GET['omitidos'] ?? 0) . '.';
    }
}
if (isset($_GET['error'], $err[$_GET['error']])) {
    $toastText = $err[$_GET['error']] . ' ' . ($errorDetalle ?? '');
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NeivActiva - Carga Masiva</title>
    <link rel="stylesheet" href="/assets/css/neivactiva-2026.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="/assets/css/views/carga_masiva.css">
</head>
<body>
<?php include 'partials/sidebar.php'; ?>

<main class="main-wrapper cm-page">
    <header class="cm-topbar">
        <div class="cm-topbar-left">
            <button class="sidebar-toggle" type="button" data-sidebar-toggle aria-label="Abrir menu">
                <i class="bi bi-list"></i>
            </button>
            <div class="cm-page-icon"><i class="bi bi-cloud-arrow-up-fill"></i></div>
            <div>
                <h1 class="cm-page-title">Importar participantes</h1>
                <p class="cm-page-sub">Carga masiva de participantes desde CSV o Excel</p>
            </div>
        </div>
        <div class="cm-topbar-right">
            <div class="cm-stat-pill"><i class="bi bi-list-ol"></i> <?php echo $totalPreview; ?> total</div>
            <div class="cm-stat-pill cm-stat-pill--green"><i class="bi bi-check2-circle"></i> <?php echo $validos; ?> válidos</div>
            <div class="cm-stat-pill cm-stat-pill--warn"><i class="bi bi-exclamation-triangle"></i> <?php echo $advertencias; ?> adv.</div>
            <div class="cm-stat-pill cm-stat-pill--red"><i class="bi bi-x-circle"></i> <?php echo $erroresPreview; ?> err.</div>
            <a class="btn btn-secondary" href="?view=participantes">
                <i class="bi bi-people"></i> Participantes
            </a>
            <a class="btn btn-primary" href="?view=descargar_plantilla_participantes">
                <i class="bi bi-file-earmark-spreadsheet"></i> Descargar plantilla
            </a>
        </div>
    </header>

    <div class="dashboard-content bulk-page">
        <section class="bulk-layout">
            <aside class="panel-card upload-panel">
                <div class="panel-title">
                    <span class="panel-icon"><i class="bi bi-cloud-arrow-up"></i></span>
                    <div>
                        <h3>Subir archivo</h3>
                        <p>Formatos permitidos: CSV y XLSX.</p>
                    </div>
                </div>

                <form class="upload-form" method="POST" enctype="multipart/form-data" id="previewForm">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                    <input type="hidden" name="accion" value="preview">

                    <label class="dropzone" for="archivoCarga" id="dropzone">
                        <input type="file" name="archivo" id="archivoCarga" accept=".csv,.xlsx" required>
                        <span class="dropzone-icon"><i class="bi bi-file-earmark-arrow-up"></i></span>
                        <strong>Arrastra tu archivo aqui</strong>
                        <small>o haz clic para seleccionarlo</small>
                        <span class="file-pill" id="fileName">Ningun archivo seleccionado</span>
                    </label>

                    <button class="btn btn-primary wide-btn" type="submit" id="previewButton">
                        <span class="btn-label"><i class="bi bi-eye"></i> Generar Preview</span>
                        <span class="btn-spinner"></span>
                    </button>
                </form>

                <div class="progress-shell" id="progressShell" hidden>
                    <div class="progress-header">
                        <span>Procesando archivo</span>
                        <strong id="progressLabel">0%</strong>
                    </div>
                    <div class="progress-track"><span id="progressBar"></span></div>
                </div>

            </aside>

            <section class="panel-card preview-panel">
                <div class="preview-header">
                    <div>
                        <span class="eyebrow">Preview</span>
                        <h3>Registros detectados</h3>
                    </div>
                    <?php if (!empty($preview)): ?>
                    <form method="POST" class="import-toolbar" id="importForm">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                        <input type="hidden" name="accion" value="importar">
                        <select name="evento_id">
                            <option value="0">Solo registrar participantes</option>
                            <?php foreach ($lista_eventos as $e): ?>
                                <option value="<?php echo (int) $e['id']; ?>"><?php echo htmlspecialchars($e['titulo']); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <label class="toggle-option">
                            <input type="hidden" name="incluir_advertencias" value="0">
                            <input type="checkbox" name="incluir_advertencias" value="1" checked>
                            <span>Incluir advertencias</span>
                        </label>
                        <button class="btn btn-primary" type="submit" <?php echo $importables === 0 ? 'disabled' : ''; ?>>
                            <span class="btn-label"><i class="bi bi-upload"></i> Importar validos</span>
                            <span class="btn-spinner"></span>
                        </button>
                    </form>
                    <form method="POST" class="download-errors-form">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                        <input type="hidden" name="accion" value="descargar_errores">
                        <button class="btn btn-secondary" type="submit" <?php echo $incidencias === 0 ? 'disabled' : ''; ?>>
                            <i class="bi bi-download"></i> Descargar errores
                        </button>
                    </form>
                    <?php endif; ?>
                </div>

                <div class="preview-tools">
                    <div class="segmented-filter" role="group" aria-label="Filtrar preview">
                        <button type="button" class="active" data-filter="all">Todos</button>
                        <button type="button" data-filter="valid">Validos</button>
                        <button type="button" data-filter="warning">Advertencias</button>
                        <button type="button" data-filter="error">Errores</button>
                    </div>
                    <div class="table-search">
                        <i class="bi bi-search"></i>
                        <input type="search" id="previewSearch" placeholder="Buscar en preview">
                    </div>
                </div>

                <div class="preview-table-shell">
                    <table class="modern-table" id="previewTable">
                        <thead>
                            <tr>
                                <th>Estado</th>
                                <th>Fila</th>
                                <th>Nombre</th>
                                <th>Documento</th>
                                <th>Correo</th>
                                <th>Telefono</th>
                                <th>Ciudad</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (empty($preview)): ?>
                            <tr class="empty-row">
                                <td colspan="7">
                                    <div class="empty-preview">
                                        <i class="bi bi-table"></i>
                                        <strong>No hay datos para previsualizar</strong>
                                        <span>Sube un archivo CSV o XLSX para revisar los registros aqui.</span>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>

                        <?php foreach ($preview as $row): ?>
                            <?php
                                $errores = $row['errores'] ?? [];
                                $advertenciasRow = $row['advertencias'] ?? [];
                                $estado = $row['estado'] ?? (empty($errores) ? 'valid' : 'error');
                                $configEstado = $estadoConfig[$estado] ?? $estadoConfig['error'];
                                $detalles = array_merge($errores, $advertenciasRow);
                                $textoTooltip = empty($detalles) ? 'Registro listo para importar.' : implode(' | ', $detalles);
                                $detallesJson = htmlspecialchars(json_encode($detalles, JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8');
                                $textoBusqueda = strtolower(implode(' ', [
                                    $row['nombre'] ?? '',
                                    $row['documento'] ?? '',
                                    $row['correo'] ?? '',
                                    $row['telefono'] ?? '',
                                    $row['ciudad'] ?? '',
                                    $estado
                                ]));
                            ?>
                            <tr class="preview-row is-<?php echo htmlspecialchars($estado); ?>"
                                data-status="<?php echo htmlspecialchars($estado); ?>"
                                data-search="<?php echo htmlspecialchars($textoBusqueda, ENT_QUOTES, 'UTF-8'); ?>">
                                <td>
                                    <div class="status-cell">
                                        <span class="status-badge <?php echo htmlspecialchars($configEstado['class']); ?>" title="<?php echo htmlspecialchars($textoTooltip); ?>">
                                            <i class="bi <?php echo htmlspecialchars($configEstado['icon']); ?>"></i>
                                            <?php echo htmlspecialchars($configEstado['label']); ?>
                                        </span>
                                        <?php if (!empty($detalles)): ?>
                                            <button type="button"
                                                    class="icon-btn detail-trigger"
                                                    title="Ver detalles"
                                                    data-row="<?php echo (int) $row['fila']; ?>"
                                                    data-status="<?php echo htmlspecialchars($configEstado['label']); ?>"
                                                    data-details="<?php echo $detallesJson; ?>">
                                                <i class="bi bi-list-ul"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td><?php echo (int) $row['fila']; ?></td>
                                <td><?php echo htmlspecialchars($row['nombre'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($row['documento'] ?? ''); ?></td>
                                <td class="email-cell"><?php echo htmlspecialchars($row['correo'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($row['telefono'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($row['ciudad'] ?? ''); ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <?php if (!empty($preview)): ?>
                <div class="pagination-bar">
                    <span id="visibleRowsLabel"><?php echo $totalPreview; ?> registros visibles</span>
                    <div class="pagination-actions">
                        <button type="button" class="icon-btn" id="prevPage"><i class="bi bi-chevron-left"></i></button>
                        <span id="pageLabel">1</span>
                        <button type="button" class="icon-btn" id="nextPage"><i class="bi bi-chevron-right"></i></button>
                    </div>
                </div>
                <?php endif; ?>
            </section>
        </section>
    </div>
</main>

<div class="bulk-modal" id="detailsModal" hidden>
    <div class="bulk-modal-backdrop" data-close-details></div>
    <section class="bulk-modal-card" role="dialog" aria-modal="true" aria-labelledby="detailsTitle">
        <header>
            <div>
                <span class="eyebrow" id="detailsStatus">Detalle</span>
                <h3 id="detailsTitle">Fila</h3>
            </div>
            <button type="button" class="icon-btn" data-close-details aria-label="Cerrar"><i class="bi bi-x-lg"></i></button>
        </header>
        <ul id="detailsList"></ul>
    </section>
</div>

<?php if ($toastText !== ''): ?>
<div class="toast-notice <?php echo htmlspecialchars($toastType); ?>" id="toastNotice">
    <i class="bi <?php echo $toastType === 'error' ? 'bi-x-circle' : 'bi-check-circle'; ?>"></i>
    <span><?php echo htmlspecialchars($toastText); ?></span>
    <button type="button" aria-label="Cerrar"><i class="bi bi-x"></i></button>
</div>
<?php endif; ?>

<script>
(() => {
    const dropzone = document.getElementById('dropzone');
    const fileInput = document.getElementById('archivoCarga');
    const fileName = document.getElementById('fileName');
    const previewForm = document.getElementById('previewForm');
    const importForm = document.getElementById('importForm');
    const progressShell = document.getElementById('progressShell');
    const progressBar = document.getElementById('progressBar');
    const progressLabel = document.getElementById('progressLabel');
    const toast = document.getElementById('toastNotice');
    const rows = Array.from(document.querySelectorAll('#previewTable tbody tr.preview-row'));
    const filterButtons = Array.from(document.querySelectorAll('[data-filter]'));
    const searchInput = document.getElementById('previewSearch');
    const visibleRowsLabel = document.getElementById('visibleRowsLabel');
    const pageLabel = document.getElementById('pageLabel');
    const prevPage = document.getElementById('prevPage');
    const nextPage = document.getElementById('nextPage');
    const detailsModal = document.getElementById('detailsModal');
    const detailsTitle = document.getElementById('detailsTitle');
    const detailsStatus = document.getElementById('detailsStatus');
    const detailsList = document.getElementById('detailsList');
    const pageSize = 25;
    let filter = 'all';
    let page = 1;

    function showProgress(form) {
        form?.classList.add('is-loading');
        if (!progressShell) return;
        progressShell.hidden = false;
        let value = 0;
        const timer = setInterval(() => {
            value = Math.min(value + 14, 92);
            progressBar.style.width = value + '%';
            progressLabel.textContent = value + '%';
            if (value >= 92) clearInterval(timer);
        }, 120);
    }

    fileInput?.addEventListener('change', () => {
        const file = fileInput.files?.[0];
        fileName.textContent = file ? file.name : 'Ningun archivo seleccionado';
        dropzone?.classList.toggle('has-file', Boolean(file));
    });

    ['dragenter', 'dragover'].forEach(eventName => {
        dropzone?.addEventListener(eventName, event => {
            event.preventDefault();
            dropzone.classList.add('is-dragging');
        });
    });

    ['dragleave', 'drop'].forEach(eventName => {
        dropzone?.addEventListener(eventName, event => {
            event.preventDefault();
            dropzone.classList.remove('is-dragging');
        });
    });

    dropzone?.addEventListener('drop', event => {
        const files = event.dataTransfer?.files;
        if (files?.length) {
            fileInput.files = files;
            fileInput.dispatchEvent(new Event('change'));
        }
    });

    previewForm?.addEventListener('submit', () => showProgress(previewForm));
    importForm?.addEventListener('submit', () => showProgress(importForm));

    function rowMatches(row) {
        const matchesFilter = filter === 'all' || row.dataset.status === filter;
        const query = (searchInput?.value || '').toLowerCase().trim();
        const matchesSearch = !query || (row.dataset.search || '').includes(query);
        return matchesFilter && matchesSearch;
    }

    function applyTableState() {
        const matchingRows = rows.filter(rowMatches);
        const totalPages = Math.max(1, Math.ceil(matchingRows.length / pageSize));
        page = Math.min(page, totalPages);
        const start = (page - 1) * pageSize;
        const visibleSet = new Set(matchingRows.slice(start, start + pageSize));

        rows.forEach(row => row.hidden = !visibleSet.has(row));

        if (visibleRowsLabel) visibleRowsLabel.textContent = `${matchingRows.length} registros visibles`;
        if (pageLabel) pageLabel.textContent = `${page} / ${totalPages}`;
        if (prevPage) prevPage.disabled = page <= 1;
        if (nextPage) nextPage.disabled = page >= totalPages;
    }

    filterButtons.forEach(button => {
        button.addEventListener('click', () => {
            filterButtons.forEach(btn => btn.classList.remove('active'));
            button.classList.add('active');
            filter = button.dataset.filter;
            page = 1;
            applyTableState();
        });
    });

    searchInput?.addEventListener('input', () => {
        page = 1;
        applyTableState();
    });
    prevPage?.addEventListener('click', () => { page--; applyTableState(); });
    nextPage?.addEventListener('click', () => { page++; applyTableState(); });
    applyTableState();

    document.querySelectorAll('.detail-trigger').forEach(button => {
        button.addEventListener('click', () => {
            const details = JSON.parse(button.dataset.details || '[]');
            detailsTitle.textContent = `Fila ${button.dataset.row || ''}`;
            detailsStatus.textContent = button.dataset.status || 'Detalle';
            detailsList.replaceChildren(...details.map(item => {
                const li = document.createElement('li');
                li.textContent = item;
                return li;
            }));
            detailsModal.hidden = false;
            document.body.classList.add('modal-open');
        });
    });

    document.querySelectorAll('[data-close-details]').forEach(button => {
        button.addEventListener('click', () => {
            detailsModal.hidden = true;
            document.body.classList.remove('modal-open');
        });
    });

    toast?.querySelector('button')?.addEventListener('click', () => toast.remove());
    if (toast) setTimeout(() => toast.classList.add('is-hiding'), 4200);
})();
</script>
<script src="/assets/js/input-rules.js"></script>
</body>
</html>

