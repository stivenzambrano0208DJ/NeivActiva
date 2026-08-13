<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($title) ? $title . ' - ' . ($_ENV['APP_NAME'] ?? 'NeivActiva') : ($_ENV['APP_NAME'] ?? 'NeivActiva') ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Outfit:wght@400;600;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/neivactiva-2026.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body class="public-shell">
    <nav class="navbar">
        <div class="container navbar-content">
            <a href="/" class="brand-logo">
                <i class="bi bi-lightning-charge-fill"></i>
                <span>NeivActiva</span>
            </a>
            <ul class="nav-links">
                <?php if (isset($_SESSION['usuario_id'])): ?>
                    <li><a href="/dashboard">Dashboard</a></li>
                    <li><a href="/eventos">Eventos</a></li>
                    <li><a href="/logout" class="btn btn-secondary btn-sm">Salir</a></li>
                <?php else: ?>
                    <li><a href="/eventos">Eventos</a></li>
                    <li><a href="/login">Iniciar sesion</a></li>
                    <li><a href="/register" class="btn btn-primary btn-sm">Registrarse</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </nav>

    <main class="main-content">
        <?= $content ?>
    </main>

    <footer class="footer">
        <div class="container text-center">
            <p>&copy; <?= date('Y') ?> NeivActiva. Todos los derechos reservados.</p>
        </div>
    </footer>
<script src="/assets/js/input-rules.js"></script>
</body>
</html>
