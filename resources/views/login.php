<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NeivActiva – Iniciar sesión</title>
    <link rel="stylesheet" href="/assets/css/neivactiva-2026.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="/assets/css/views/login.css">
</head>
<body class="lg-body">

<?php
$msgs = [
    '1'      => 'Credenciales inválidas.',
    'csrf'   => 'Sesión expirada. Recarga la página.',
    'campos' => 'Ingresa correo o documento y contraseña.',
    'limite' => 'Demasiados intentos. Inténtalo en unos minutos.',
];
$err = $_GET['error'] ?? '';

$successMsgs = [
    'cuenta_creada'        => '¡Ya estás registrado! Te enviamos un correo de bienvenida. Inicia sesión para continuar.',
    'password_actualizada' => 'Tu contraseña se actualizó correctamente. Ya puedes iniciar sesión.',
];
$okMsg = $_GET['msg'] ?? '';
?>

<div class="lg-wrap">

    <!-- ── LEFT: hero ───────────────────────────── -->
    <aside class="lg-hero">
        <div class="lg-hero-img"></div>
        <div class="lg-hero-overlay"></div>
        <div class="lg-hero-glow"></div>
        <div class="lg-hero-content">
            <div class="lg-hero-line"></div>
            <span class="lg-hero-pill"><i class="bi bi-stars"></i> Plataforma cultural</span>
            <h1>Cultura que<br><span>nos une.</span></h1>
            <p>Accede a la plataforma oficial de eventos de Neiva. Gestiona tus entradas, certificados y mantente al día con nuestra tradición.</p>
            <div class="lg-hero-features">
                <span class="lg-feat"><i class="bi bi-qr-code-scan"></i> Acceso con QR</span>
                <span class="lg-feat"><i class="bi bi-award"></i> Certificados</span>
                <span class="lg-feat"><i class="bi bi-calendar-check"></i> Eventos</span>
            </div>
        </div>
    </aside>

    <!-- ── RIGHT: form ──────────────────────────── -->
    <main class="lg-side">
        <div class="lg-card">

            <!-- Brand -->
            <a href="/dashboard" class="lg-brand">
                <span class="lg-brand-icon"><i class="bi bi-sun-fill"></i></span>
                <span class="lg-brand-name">Neiv<em>Activa</em></span>
            </a>

            <!-- Heading -->
            <div class="lg-heading">
                <span class="lg-kicker">Acceso universal</span>
                <h2>Bienvenido de nuevo</h2>
                <p>Ingresa con tu correo o documento. NeivActiva detectará tu rol automáticamente.</p>
            </div>

            <!-- Success alert -->
            <?php if (isset($successMsgs[$okMsg])): ?>
            <div class="lg-alert lg-alert--ok">
                <i class="bi bi-check-circle-fill"></i>
                <span><?php echo htmlspecialchars($successMsgs[$okMsg]); ?></span>
            </div>
            <?php endif; ?>

            <!-- Error alert -->
            <?php if (isset($msgs[$err])): ?>
            <div class="lg-alert">
                <i class="bi bi-exclamation-circle-fill"></i>
                <span><?php echo htmlspecialchars($msgs[$err]); ?></span>
            </div>
            <?php endif; ?>

            <!-- Form -->
            <form method="POST" action="/login" class="lg-form" id="loginForm">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken ?? ''); ?>">

                <!-- Identificador -->
                <div class="lg-field">
                    <label class="lg-label" for="identificador">Correo o documento</label>
                    <div class="lg-input-wrap">
                        <i class="bi bi-person-badge lg-ico"></i>
                        <input type="text" id="identificador" name="identificador" class="lg-input"
                               placeholder="correo@dominio.com o documento"
                               autocomplete="username" required>
                    </div>
                </div>

                <!-- Contraseña -->
                <div class="lg-field">
                    <div class="lg-label-row">
                        <label class="lg-label" for="password">Contraseña</label>
                        <a href="/forgot-password" class="lg-forgot">¿Olvidaste tu contraseña?</a>
                    </div>
                    <div class="lg-input-wrap lg-pwd-wrap">
                        <i class="bi bi-lock lg-ico"></i>
                        <input type="password" id="password" name="password" class="lg-input"
                               placeholder="••••••••••••"
                               autocomplete="current-password" required>
                        <button type="button" class="lg-eye" data-target="password">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                </div>

                <!-- Submit -->
                <button type="submit" class="lg-submit" id="loginSubmitBtn">
                    <span>Iniciar sesión</span>
                    <i class="bi bi-arrow-right-circle-fill"></i>
                </button>
            </form>

            <!-- Footer -->
            <div class="lg-footer">
                <a href="/dashboard" class="lg-guest">
                    <i class="bi bi-eye"></i> Explorar como invitado
                </a>
                <p>¿Eres nuevo? <a href="/register">Crear cuenta</a></p>
            </div>

        </div>
    </main>
</div>

<script>
(function () {
    /* Toggle password */
    document.querySelectorAll('.lg-eye').forEach(btn => {
        btn.addEventListener('click', () => {
            const inp = document.getElementById(btn.dataset.target);
            const show = inp.type === 'password';
            inp.type = show ? 'text' : 'password';
            btn.querySelector('i').className = 'bi ' + (show ? 'bi-eye-slash' : 'bi-eye');
        });
    });

    /* Loading state on submit */
    document.getElementById('loginForm')?.addEventListener('submit', () => {
        const btn = document.getElementById('loginSubmitBtn');
        btn.disabled = true;
        btn.classList.add('lg-loading');
    });
})();
</script>
<script src="/assets/js/input-rules.js"></script>
</body>
</html>
