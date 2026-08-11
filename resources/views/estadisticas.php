<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NeivActiva - Análisis de Impacto</title>
    <link rel="stylesheet" href="/assets/css/neivactiva-2026.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="/assets/css/views/estadisticas.css">
</head>
<body class="dashboard-body">

<?php include 'partials/sidebar.php'; ?>

<main class="main-wrapper">
    <nav class="top-navbar">
        <div class="navbar-left">
            <h1 class="page-title">Análisis de Impacto</h1>
            <span class="page-subtitle">Métricas en tiempo real</span>
        </div>
        <div class="header-actions">
            <div class="filter-group">
                <select id="dateFilter" class="filter-select">
                    <option value="30">Últimos 30 días</option>
                    <option value="90">Últimos 3 meses</option>
                    <option value="180">Últimos 6 meses</option>
                    <option value="365">Último año</option>
                </select>
                <select id="eventTypeFilter" class="filter-select">
                    <option value="">Todos los eventos</option>
                    <option value="Deportivo">Deportivo</option>
                    <option value="Cultural">Cultural</option>
                    <option value="Educativo">Educativo</option>
                    <option value="Otro">Otro</option>
                </select>
            </div>
            <button class="btn btn-secondary" onclick="exportData('excel')">
                <i class="bi bi-file-earmark-excel"></i> Excel
            </button>
            <button class="btn btn-secondary" onclick="exportData('pdf')">
                <i class="bi bi-file-earmark-pdf"></i> PDF
            </button>
        </div>
    </nav>

    <div class="dashboard-content">
        <!-- KPIs Principales -->
        <div class="kpi-grid">
            <div class="kpi-card skeleton">
                <div class="kpi-header">
                    <div class="kpi-icon events">
                        <i class="bi bi-calendar-event"></i>
                    </div>
                    <span class="kpi-badge <?php echo ($metrics['comparison']['events_growth'] ?? 0) >= 0 ? 'positive' : 'negative'; ?>">
                        <?php echo $metrics['comparison']['events_growth'] > 0 ? '+' : ''; ?><?php echo $metrics['comparison']['events_growth'] ?? 0; ?>%
                    </span>
                </div>
                <div class="kpi-value"><?php echo number_format($metrics['total_events'] ?? 0); ?></div>
                <div class="kpi-label">Total Eventos</div>
                <div class="kpi-trend">vs mes anterior</div>
            </div>

            <div class="kpi-card skeleton">
                <div class="kpi-header">
                    <div class="kpi-icon participants">
                        <i class="bi bi-people"></i>
                    </div>
                    <span class="kpi-badge <?php echo ($metrics['comparison']['participants_growth'] ?? 0) >= 0 ? 'positive' : 'negative'; ?>">
                        <?php echo $metrics['comparison']['participants_growth'] > 0 ? '+' : ''; ?><?php echo $metrics['comparison']['participants_growth'] ?? 0; ?>%
                    </span>
                </div>
                <div class="kpi-value"><?php echo number_format($metrics['total_participants'] ?? 0); ?></div>
                <div class="kpi-label">Total Participantes</div>
                <div class="kpi-trend">vs mes anterior</div>
            </div>

            <div class="kpi-card skeleton">
                <div class="kpi-header">
                    <div class="kpi-icon users">
                        <i class="bi bi-person-plus"></i>
                    </div>
                    <span class="kpi-badge <?php echo ($metrics['comparison']['users_growth'] ?? 0) >= 0 ? 'positive' : 'negative'; ?>">
                        <?php echo $metrics['comparison']['users_growth'] > 0 ? '+' : ''; ?><?php echo $metrics['comparison']['users_growth'] ?? 0; ?>%
                    </span>
                </div>
                <div class="kpi-value"><?php echo number_format($metrics['new_users_month'] ?? 0); ?></div>
                <div class="kpi-label">Usuarios Nuevos</div>
                <div class="kpi-trend">este mes</div>
            </div>

            <div class="kpi-card skeleton">
                <div class="kpi-header">
                    <div class="kpi-icon retention">
                        <i class="bi bi-arrow-repeat"></i>
                    </div>
                    <span class="kpi-badge neutral">--</span>
                </div>
                <div class="kpi-value"><?php echo $metrics['retention_rate'] ?? 0; ?>%</div>
                <div class="kpi-label">Tasa de Retención</div>
                <div class="kpi-trend">participantes recurrentes</div>
            </div>

            <div class="kpi-card skeleton">
                <div class="kpi-header">
                    <div class="kpi-icon satisfaction">
                        <i class="bi bi-emoji-smile"></i>
                    </div>
                    <span class="kpi-badge neutral">--</span>
                </div>
                <div class="kpi-value"><?php echo $metrics['satisfaction_level'] ?? 0; ?>%</div>
                <div class="kpi-label">Satisfacción</div>
                <div class="kpi-trend">basado en asistencia</div>
            </div>

            <div class="kpi-card skeleton">
                <div class="kpi-header">
                    <div class="kpi-icon attendance">
                        <i class="bi bi-check-circle"></i>
                    </div>
                    <span class="kpi-badge neutral">--</span>
                </div>
                <div class="kpi-value"><?php echo $metrics['attendance_rate'] ?? 0; ?>%</div>
                <div class="kpi-label">Asistencia</div>
                <div class="kpi-trend">promedio general</div>
            </div>

            <div class="kpi-card skeleton">
                <div class="kpi-header">
                    <div class="kpi-icon certificates">
                        <i class="bi bi-award"></i>
                    </div>
                    <span class="kpi-badge neutral">--</span>
                </div>
                <div class="kpi-value"><?php echo number_format($metrics['certificates_issued'] ?? 0); ?></div>
                <div class="kpi-label">Certificados</div>
                <div class="kpi-trend">emitidos</div>
            </div>

            <div class="kpi-card skeleton">
                <div class="kpi-header">
                    <div class="kpi-icon growth">
                        <i class="bi bi-graph-up-arrow"></i>
                    </div>
                    <span class="kpi-badge neutral">--</span>
                </div>
                <div class="kpi-value"><?php echo count($metrics['monthly_participation'] ?? []); ?></div>
                <div class="kpi-label">Meses Activos</div>
                <div class="kpi-trend">con participación</div>
            </div>
        </div>

        <!-- Gráficas Principales -->
        <div class="charts-row">
            <div class="chart-card large skeleton">
                <div class="chart-header">
                    <h3>Crecimiento de Participación</h3>
                    <div class="chart-legend">
                        <span class="legend-item"><span class="legend-color primary"></span> Participantes</span>
                    </div>
                </div>
                <div class="chart-container">
                    <canvas id="participationChart"></canvas>
                </div>
            </div>

            <div class="chart-card medium skeleton">
                <div class="chart-header">
                    <h3>Satisfacción del Usuario</h3>
                </div>
                <div class="chart-container">
                    <canvas id="satisfactionChart"></canvas>
                </div>
                <div class="chart-insight">
                    <i class="bi bi-lightbulb"></i>
                    <span>Basado en tasa de asistencia real</span>
                </div>
            </div>
        </div>

        <div class="charts-row">
            <div class="chart-card medium skeleton">
                <div class="chart-header">
                    <h3>Eventos Más Populares</h3>
                </div>
                <div class="chart-container">
                    <canvas id="popularEventsChart"></canvas>
                </div>
            </div>

            <div class="chart-card medium skeleton">
                <div class="chart-header">
                    <h3>Actividad Semanal</h3>
                </div>
                <div class="chart-container">
                    <canvas id="weeklyActivityChart"></canvas>
                </div>
            </div>

            <div class="chart-card medium skeleton">
                <div class="chart-header">
                    <h3>Crecimiento de Usuarios</h3>
                </div>
                <div class="chart-container">
                    <canvas id="userGrowthChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Tablas y Resúmenes -->
        <div class="tables-row">
            <div class="table-card skeleton">
                <div class="table-header">
                    <h3>Eventos Más Populares</h3>
                    <button class="btn btn-sm btn-secondary">Ver todos</button>
                </div>
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Evento</th>
                                <th>Categoría</th>
                                <th>Asistentes</th>
                                <th>Cupo</th>
                                <th>Ocupación</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($metrics['popular_events'])): ?>
                                <?php foreach ($metrics['popular_events'] as $event): ?>
                                    <?php 
                                        $ocupacion = $event['cupo_maximo'] > 0 
                                            ? round(($event['asistentes'] / $event['cupo_maximo']) * 100, 1) 
                                            : 0;
                                    ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($event['titulo']); ?></td>
                                        <td><span class="badge badge-<?php echo strtolower($event['categoria']); ?>"><?php echo $event['categoria']; ?></span></td>
                                        <td><?php echo number_format($event['asistentes']); ?></td>
                                        <td><?php echo number_format($event['cupo_maximo']); ?></td>
                                        <td>
                                            <div class="progress-bar">
                                                <div class="progress-fill" style="width: <?php echo min($ocupacion, 100); ?>%"></div>
                                            </div>
                                            <span class="progress-text"><?php echo $ocupacion; ?>%</span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="empty-state">
                                        <i class="bi bi-inbox"></i>
                                        <p>No hay eventos registrados</p>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="insights-card skeleton">
                <div class="insights-header">
                    <h3>Insights Automáticos</h3>
                    <i class="bi bi-magic"></i>
                </div>
                <div class="insights-list">
                    <div class="insight-item">
                        <div class="insight-icon success">
                            <i class="bi bi-trending-up"></i>
                        </div>
                        <div class="insight-content">
                            <h4>Participación en aumento</h4>
                            <p>El crecimiento de participantes es positivo en los últimos meses.</p>
                        </div>
                    </div>
                    <div class="insight-item">
                        <div class="insight-icon warning">
                            <i class="bi bi-exclamation-triangle"></i>
                        </div>
                        <div class="insight-content">
                            <h4>Mejorar retención</h4>
                            <p>La tasa de retención podría mejorar con estrategias de fidelización.</p>
                        </div>
                    </div>
                    <div class="insight-item">
                        <div class="insight-icon info">
                            <i class="bi bi-info-circle"></i>
                        </div>
                        <div class="insight-content">
                            <h4>Eventos destacados</h4>
                            <p>Los eventos de categoría <?php echo !empty($metrics['popular_events']) ? htmlspecialchars($metrics['popular_events'][0]['categoria']) : '--'; ?> tienen mayor asistencia.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
// Datos desde PHP
const metrics = <?php echo json_encode($metrics); ?>;

// Configuración de Chart.js
Chart.defaults.font.family = 'Inter, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif';
Chart.defaults.color = '#64748b';

// Gráfica de Participación Mensual
const participationCtx = document.getElementById('participationChart').getContext('2d');
const participationLabels = metrics.monthly_participation?.map(m => {
    const date = new Date(m.mes + '-01');
    return date.toLocaleDateString('es-ES', { month: 'short', year: '2-digit' });
}) || [];
const participationData = metrics.monthly_participation?.map(m => m.participantes) || [];

new Chart(participationCtx, {
    type: 'line',
    data: {
        labels: participationLabels,
        datasets: [{
            label: 'Participantes',
            data: participationData,
            borderColor: '#f5b400',
            backgroundColor: 'rgba(245, 180, 0, 0.1)',
            borderWidth: 3,
            tension: 0.4,
            fill: true,
            pointBackgroundColor: '#f5b400',
            pointBorderColor: '#fff',
            pointBorderWidth: 2,
            pointRadius: 5,
            pointHoverRadius: 7
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false },
            tooltip: {
                backgroundColor: '#1e293b',
                titleColor: '#fff',
                bodyColor: '#fff',
                padding: 12,
                cornerRadius: 8,
                displayColors: false
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                grid: { color: '#f1f5f9' },
                ticks: { callback: value => value.toLocaleString() }
            },
            x: {
                grid: { display: false }
            }
        }
    }
});

// Gráfica de Satisfacción (Donut)
const satisfactionCtx = document.getElementById('satisfactionChart').getContext('2d');
const satisfactionLevel = metrics.satisfaction_level || 0;

new Chart(satisfactionCtx, {
    type: 'doughnut',
    data: {
        labels: ['Satisfechos', 'Por mejorar'],
        datasets: [{
            data: [satisfactionLevel, 100 - satisfactionLevel],
            backgroundColor: ['#f5b400', '#e2e8f0'],
            borderWidth: 0,
            cutout: '70%'
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false },
            tooltip: { enabled: false }
        }
    },
    plugins: [{
        id: 'centerText',
        beforeDraw: function(chart) {
            const ctx = chart.ctx;
            const centerX = chart.getDatasetMeta(0).data[0].x;
            const centerY = chart.getDatasetMeta(0).data[0].y;
            
            ctx.save();
            ctx.textAlign = 'center';
            ctx.textBaseline = 'middle';
            ctx.font = 'bold 24px Inter';
            ctx.fillStyle = '#1e293b';
            ctx.fillText(satisfactionLevel + '%', centerX, centerY);
            ctx.restore();
        }
    }]
});

// Gráfica de Eventos Populares (Barras)
const popularEventsCtx = document.getElementById('popularEventsChart').getContext('2d');
const popularEvents = metrics.popular_events || [];
const popularEventsLabels = popularEvents.map(e => e.titulo.substring(0, 20) + '...');
const popularEventsData = popularEvents.map(e => e.asistentes);

new Chart(popularEventsCtx, {
    type: 'bar',
    data: {
        labels: popularEventsLabels,
        datasets: [{
            label: 'Asistentes',
            data: popularEventsData,
            backgroundColor: '#f5b400',
            borderRadius: 8,
            borderSkipped: false
        }]
    },
    options: {
        indexAxis: 'y',
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false }
        },
        scales: {
            x: {
                beginAtZero: true,
                grid: { color: '#f1f5f9' }
            },
            y: {
                grid: { display: false }
            }
        }
    }
});

// Gráfica de Actividad Semanal
const weeklyActivityCtx = document.getElementById('weeklyActivityChart').getContext('2d');
const weeklyActivity = metrics.weekly_activity || [];
const weekDays = ['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'];
const weeklyData = new Array(7).fill(0);

weeklyActivity.forEach(item => {
    const dayIndex = weekDays.findIndex(d => d === item.dia.substring(0, 3));
    if (dayIndex !== -1) weeklyData[dayIndex] = item.cantidad;
});

new Chart(weeklyActivityCtx, {
    type: 'bar',
    data: {
        labels: weekDays,
        datasets: [{
            label: 'Inscripciones',
            data: weeklyData,
            backgroundColor: '#f5b400',
            borderRadius: 8,
            borderSkipped: false
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false }
        },
        scales: {
            y: {
                beginAtZero: true,
                grid: { color: '#f1f5f9' }
            },
            x: {
                grid: { display: false }
            }
        }
    }
});

// Gráfica de Crecimiento de Usuarios
const userGrowthCtx = document.getElementById('userGrowthChart').getContext('2d');
const userGrowth = metrics.user_growth || [];
const userGrowthLabels = userGrowth.map(u => {
    const date = new Date(u.mes + '-01');
    return date.toLocaleDateString('es-ES', { month: 'short' });
});
const userGrowthData = userGrowth.map(u => u.usuarios);

new Chart(userGrowthCtx, {
    type: 'line',
    data: {
        labels: userGrowthLabels,
        datasets: [{
            label: 'Usuarios',
            data: userGrowthData,
            borderColor: '#f5b400',
            backgroundColor: 'rgba(245, 180, 0, 0.1)',
            borderWidth: 3,
            tension: 0.4,
            fill: true,
            pointBackgroundColor: '#f5b400',
            pointBorderColor: '#fff',
            pointBorderWidth: 2,
            pointRadius: 4
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false }
        },
        scales: {
            y: {
                beginAtZero: true,
                grid: { color: '#f1f5f9' }
            },
            x: {
                grid: { display: false }
            }
        }
    }
});

// Remover skeleton loading después de cargar
window.addEventListener('load', function() {
    setTimeout(() => {
        document.querySelectorAll('.skeleton').forEach(el => {
            el.classList.remove('skeleton');
        });
    }, 300);
});

// Funciones de utilidad

function exportData(format) {
    // Implementar exportación real
    if (format === 'excel') {
        exportToExcel();
    } else if (format === 'pdf') {
        exportToPDF();
    }
}

function exportToExcel() {
    // Crear contenido CSV
    let csvContent = "data:text/csv;charset=utf-8,";
    csvContent += "Métrica,Valor\n";
    csvContent += `Total Eventos,${metrics.total_events}\n`;
    csvContent += `Total Participantes,${metrics.total_participants}\n`;
    csvContent += `Usuarios Nuevos,${metrics.new_users_month}\n`;
    csvContent += `Tasa de Retención,${metrics.retention_rate}%\n`;
    csvContent += `Satisfacción,${metrics.satisfaction_level}%\n`;
    csvContent += `Asistencia,${metrics.attendance_rate}%\n`;
    csvContent += `Certificados Emitidos,${metrics.certificates_issued}\n`;
    
    // Agregar eventos populares
    csvContent += "\nEvento,Categoría,Asistentes,Cupo\n";
    if (metrics.popular_events && metrics.popular_events.length > 0) {
        metrics.popular_events.forEach(event => {
            csvContent += `"${event.titulo}","${event.categoria}",${event.asistentes},${event.cupo_maximo}\n`;
        });
    }
    
    // Descargar archivo
    const encodedUri = encodeURI(csvContent);
    const link = document.createElement("a");
    link.setAttribute("href", encodedUri);
    link.setAttribute("download", `neivactiva_estadisticas_${new Date().toISOString().split('T')[0]}.csv`);
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

function exportToPDF() {
    // Para PDF real, necesitarías una librería como jsPDF o html2canvas
    // Por ahora, mostramos un mensaje y creamos una vista imprimible
    const printContent = document.querySelector('.dashboard-content').innerHTML;
    const printWindow = window.open('', '_blank');
    printWindow.document.write(`
        <!DOCTYPE html>
        <html>
        <head>
            <title>NeivActiva - Estadísticas</title>
            <style>
                body { font-family: Arial, sans-serif; padding: 20px; }
                h1 { color: #1e293b; }
                .kpi-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 30px; }
                .kpi-card { border: 1px solid #e2e8f0; padding: 20px; border-radius: 10px; }
                .kpi-value { font-size: 24px; font-weight: bold; color: #1e293b; }
                .kpi-label { font-size: 14px; color: #64748b; }
                table { width: 100%; border-collapse: collapse; margin-top: 20px; }
                th, td { border: 1px solid #e2e8f0; padding: 10px; text-align: left; }
                th { background: #f8fafc; }
            </style>
        </head>
        <body>
            <h1>NeivActiva - Análisis de Impacto</h1>
            <p>Generado: ${new Date().toLocaleString('es-ES')}</p>
            ${printContent}
        </body>
        </html>
    `);
    printWindow.document.close();
    printWindow.print();
}

// Filtros dinámicos
document.getElementById('dateFilter')?.addEventListener('change', function() {
    applyFilters();
});

document.getElementById('eventTypeFilter')?.addEventListener('change', function() {
    applyFilters();
});

function applyFilters() {
    const days = document.getElementById('dateFilter')?.value || '30';
    const eventType = document.getElementById('eventTypeFilter')?.value || '';
    
    // Calcular fechas
    const endDate = new Date().toISOString().split('T')[0];
    const startDate = new Date(Date.now() - (parseInt(days) * 24 * 60 * 60 * 1000)).toISOString().split('T')[0];
    
    // Recargar página con filtros
    const url = new URL(window.location);
    url.searchParams.set('start_date', startDate);
    url.searchParams.set('end_date', endDate);
    if (eventType) {
        url.searchParams.set('event_type', eventType);
    } else {
        url.searchParams.delete('event_type');
    }
    
    // Mostrar loading
    document.querySelectorAll('.kpi-card, .chart-card, .table-card, .insights-card').forEach(el => {
        el.classList.add('skeleton');
    });
    
    // Recargar
    window.location.href = url.toString();
}
</script>
</body>
</html>

