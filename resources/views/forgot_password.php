<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NeivActiva – Recuperar contraseña</title>
    <link rel="stylesheet" href="/NeivActiva/public/assets/css/neivactiva-2026.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="/NeivActiva/public/assets/css/views/login.css">
</head>
<body class="lg-body">

<?php
$errores = [
    'csrf'   => 'Sesión expirada. Recarga la página.',
    'correo' => 'Ingresa un correo electrónico válido.',
];
$err = $_GET['error'] ?? '';
$enviado = ($_GET['msg'] ?? '') === 'enviado';
?>

<div class="lg-wrap">

    <!-- ── LEFT: hero ───────────────────────────── -->
    <aside class="lg-hero">
        <div class="lg-hero-img"></div>
        <div class="lg-hero-overlay"></div>
        <div class="lg-hero-glow"></div>
        <div class="lg-hero-content">
            <div class="lg-hero-line"></div>
            <span class="lg-hero-pill"><i class="bi bi-shield-lock"></i> Acceso seguro</span>
            <h1>Recupera tu<br><span>acceso.</span></h1>
            <p>Te enviaremos un enlace a tu correo para que crees una nueva contraseña de forma segura.</p>
            <div class="lg-hero-features">
                <span class="lg-feat"><i class="bi bi-envelope-check"></i> Enlace por correo</span>
                <span class="lg-feat"><i class="bi bi-clock-history"></i> Válido 1 hora</span>
                <span class="lg-feat"><i class="bi bi-lock"></i> Un solo uso</span>
            </div>
        </div>
    </aside>

    <!-- ── RIGHT: form ──────────────────────────── -->
    <main class="lg-side">
        <div class="lg-card">

            <a href="/login" class="lg-brand">
                <span class="lg-brand-icon"><i class="bi bi-sun-fill"></i></span>
                <span class="lg-brand-name">Neiv<em>Activa</em></span>
            </a>

            <div class="lg-heading">
                <span class="lg-kicker">Recuperación</span>
                <h2>¿Olvidaste tu contraseña?</h2>
                <p>Ingresa el correo con el que te registraste y te enviaremos un enlace para restablecerla.</p>
            </div>

            <?php if ($enviado): ?>
            <div class="lg-alert lg-alert--ok">
                <i class="bi bi-check-circle-fill"></i>
                <span>Si el correo está registrado, te enviamos un enlace para restablecer tu contraseña. Revisa tu bandeja de entrada y spam.</span>
            </div>
            <?php elseif (isset($errores[$err])): ?>
            <div class="lg-alert">
                <i class="bi bi-exclamation-circle-fill"></i>
                <span><?php echo htmlspecialchars($errores[$err]); ?></span>
            </div>
            <?php endif; ?>

            <form method="POST" action="/forgot-password" class="lg-form" id="forgotForm">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken ?? ''); ?>">

                <div class="lg-field">
                    <label class="lg-label" for="correo">Correo electrónico</label>
                    <div class="lg-input-wrap">
                        <i class="bi bi-envelope lg-ico"></i>
                        <input type="email" id="correo" name="correo" class="lg-input"
                               placeholder="correo@dominio.com"
                               autocomplete="email" required>
                    </div>
                </div>

                <button type="submit" class="lg-submit" id="forgotSubmitBtn">
                    <span>Enviar enlace</span>
                    <i class="bi bi-send-fill"></i>
                </button>
            </form>

            <div class="lg-footer">
                <a href="/login" class="lg-guest">
                    <i class="bi bi-arrow-left"></i> Volver a iniciar sesión
                </a>
                <p>¿Eres nuevo? <a href="/register">Crear cuenta</a></p>
            </div>

        </div>
    </main>
</div>

<script>
document.getElementById('forgotForm')?.addEventListener('submit', () => {
    const btn = document.getElementById('forgotSubmitBtn');
    btn.disabled = true;
    btn.classList.add('lg-loading');
});
</script>
</body>
</html>
