<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NeivActiva – Nueva contraseña</title>
    <link rel="stylesheet" href="/NeivActiva/public/assets/css/neivactiva-2026.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="/NeivActiva/public/assets/css/views/login.css">
</head>
<body class="lg-body">

<?php
$errores = [
    'csrf'         => 'Sesión expirada. Recarga la página.',
    'password'     => 'La contraseña debe tener mínimo 8 caracteres.',
    'confirmacion' => 'Las contraseñas no coinciden.',
    'token'        => 'El enlace no es válido o ya fue utilizado.',
];
$err = $_GET['error'] ?? '';
$token = $token ?? ($_GET['token'] ?? '');
$tokenValido = $tokenValido ?? false;
?>

<div class="lg-wrap">

    <!-- ── LEFT: hero ───────────────────────────── -->
    <aside class="lg-hero">
        <div class="lg-hero-img"></div>
        <div class="lg-hero-overlay"></div>
        <div class="lg-hero-glow"></div>
        <div class="lg-hero-content">
            <div class="lg-hero-line"></div>
            <span class="lg-hero-pill"><i class="bi bi-shield-check"></i> Casi listo</span>
            <h1>Crea una<br><span>nueva clave.</span></h1>
            <p>Elige una contraseña segura de al menos 8 caracteres. Después podrás iniciar sesión con ella.</p>
            <div class="lg-hero-features">
                <span class="lg-feat"><i class="bi bi-lock-fill"></i> Cifrada</span>
                <span class="lg-feat"><i class="bi bi-check2-circle"></i> Inmediata</span>
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
                <span class="lg-kicker">Restablecer</span>
                <h2>Nueva contraseña</h2>
                <p>Define tu nueva contraseña para acceder a NeivActiva.</p>
            </div>

            <?php if (!$tokenValido): ?>
                <div class="lg-alert">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                    <span>El enlace no es válido, expiró o ya fue utilizado. Solicita uno nuevo.</span>
                </div>
                <div class="lg-footer" style="margin-top:1.25rem;">
                    <a href="/forgot-password" class="lg-guest">
                        <i class="bi bi-arrow-repeat"></i> Solicitar un nuevo enlace
                    </a>
                    <p><a href="/login">Volver a iniciar sesión</a></p>
                </div>
            <?php else: ?>
                <?php if (isset($errores[$err])): ?>
                <div class="lg-alert">
                    <i class="bi bi-exclamation-circle-fill"></i>
                    <span><?php echo htmlspecialchars($errores[$err]); ?></span>
                </div>
                <?php endif; ?>

                <form method="POST" action="/reset-password" class="lg-form" id="resetForm" novalidate>
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken ?? ''); ?>">
                    <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">

                    <div class="lg-field">
                        <label class="lg-label" for="password">Nueva contraseña</label>
                        <div class="lg-input-wrap lg-pwd-wrap">
                            <i class="bi bi-lock lg-ico"></i>
                            <input type="password" id="password" name="password" class="lg-input"
                                   placeholder="Mín. 8 caracteres" autocomplete="new-password"
                                   minlength="8" required>
                            <button type="button" class="lg-eye" data-target="password">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                    </div>

                    <div class="lg-field">
                        <label class="lg-label" for="password_confirmacion">Confirmar contraseña</label>
                        <div class="lg-input-wrap lg-pwd-wrap">
                            <i class="bi bi-shield-lock lg-ico"></i>
                            <input type="password" id="password_confirmacion" name="password_confirmacion" class="lg-input"
                                   placeholder="Repite la contraseña" autocomplete="new-password"
                                   minlength="8" required>
                            <button type="button" class="lg-eye" data-target="password_confirmacion">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                    </div>

                    <button type="submit" class="lg-submit" id="resetSubmitBtn">
                        <span>Guardar contraseña</span>
                        <i class="bi bi-check-circle-fill"></i>
                    </button>
                </form>

                <div class="lg-footer">
                    <a href="/login" class="lg-guest">
                        <i class="bi bi-arrow-left"></i> Volver a iniciar sesión
                    </a>
                </div>
            <?php endif; ?>

        </div>
    </main>
</div>

<script>
(function () {
    document.querySelectorAll('.lg-eye').forEach(btn => {
        btn.addEventListener('click', () => {
            const inp = document.getElementById(btn.dataset.target);
            const show = inp.type === 'password';
            inp.type = show ? 'text' : 'password';
            btn.querySelector('i').className = 'bi ' + (show ? 'bi-eye-slash' : 'bi-eye');
        });
    });

    document.getElementById('resetForm')?.addEventListener('submit', e => {
        const p = document.getElementById('password').value;
        const c = document.getElementById('password_confirmacion').value;
        if (p.length < 8 || p !== c) { return; } // el backend valida y muestra el error
        const btn = document.getElementById('resetSubmitBtn');
        btn.disabled = true;
        btn.classList.add('lg-loading');
    });
})();
</script>
</body>
</html>
