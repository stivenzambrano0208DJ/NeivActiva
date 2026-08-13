<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NeivActiva – Crear cuenta</title>
    <link rel="stylesheet" href="/assets/css/neivactiva-2026.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="/assets/css/views/registro.css">
</head>
<body class="rg-body">

<?php
$msgs = [
    '1'            => 'No se pudo crear la cuenta. Inténtalo nuevamente.',
    'csrf'         => 'Sesión expirada. Recarga la página.',
    'campos'       => 'Completa todos los campos requeridos.',
    'correo'       => 'Ingresa un correo electrónico válido.',
    'existe'       => 'Ya existe una cuenta con ese correo.',
    'documento'    => 'Ya existe una cuenta con ese documento.',
    'password'     => 'La contraseña debe tener mínimo 8 caracteres.',
    'confirmacion' => 'Las contraseñas no coinciden.',
    'documento_invalido' => 'El número de documento debe contener solo números.',
    'nombre_invalido'    => 'El nombre solo puede contener letras y espacios.',
];
$err = $_GET['error'] ?? '';
?>

<div class="rg-wrap">

    <!-- ── LEFT: hero ───────────────────────────── -->
    <aside class="rg-hero">
        <div class="rg-hero-img"></div>
        <div class="rg-hero-overlay"></div>
        <div class="rg-hero-glow"></div>
        <div class="rg-hero-content">
            <div class="rg-hero-line"></div>
            <span class="rg-hero-pill"><i class="bi bi-stars"></i> Plataforma cultural</span>
            <h1>Únete a<br><span>NeivActiva.</span></h1>
            <p>La plataforma oficial de eventos culturales, deportivos y educativos de Neiva. Inscríbete, obtén tu QR y descarga certificados.</p>
            <!-- Feature pills -->
            <div class="rg-hero-features">
                <span class="rg-feat"><i class="bi bi-qr-code-scan"></i> Acceso con QR</span>
                <span class="rg-feat"><i class="bi bi-award"></i> Certificados digitales</span>
                <span class="rg-feat"><i class="bi bi-calendar-check"></i> Eventos en vivo</span>
            </div>
        </div>
    </aside>

    <!-- ── RIGHT: form ──────────────────────────── -->
    <main class="rg-side">
        <div class="rg-card">

            <!-- Brand -->
            <a href="/dashboard" class="rg-brand">
                <span class="rg-brand-icon"><i class="bi bi-sun-fill"></i></span>
                <span class="rg-brand-name">Neiv<em>Activa</em></span>
            </a>

            <!-- Heading -->
            <div class="rg-heading">
                <span class="rg-kicker">Nuevo acceso</span>
                <h2>Crea tu cuenta</h2>
                <p>Completa tus datos y empieza tu experiencia cultural.</p>
            </div>

            <!-- Error alert -->
            <?php if (isset($msgs[$err])): ?>
            <div class="rg-alert">
                <i class="bi bi-exclamation-circle-fill"></i>
                <span><?php echo htmlspecialchars($msgs[$err]); ?></span>
            </div>
            <?php endif; ?>

            <!-- Form -->
            <form method="POST" action="/register" class="rg-form" id="registerForm" novalidate>
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken ?? ''); ?>">

                <!-- Nombre -->
                <div class="rg-field">
                    <label class="rg-label" for="nombre">Nombre completo</label>
                    <div class="rg-input-wrap">
                        <i class="bi bi-person rg-ico"></i>
                        <input type="text" id="nombre" name="nombre" class="rg-input"
                               placeholder="Ej. Ana García" autocomplete="name"
                               data-rule="letters" minlength="3" maxlength="100" required>
                    </div>
                </div>

                <!-- Correo -->
                <div class="rg-field">
                    <label class="rg-label" for="correo">Correo electrónico</label>
                    <div class="rg-input-wrap">
                        <i class="bi bi-envelope rg-ico"></i>
                        <input type="email" id="correo" name="correo" class="rg-input"
                               placeholder="correo@ejemplo.com" autocomplete="email"
                               maxlength="255" required>
                    </div>
                </div>

                <!-- Documento + Teléfono -->
                <div class="rg-row">
                    <div class="rg-field">
                        <label class="rg-label" for="doc">Documento</label>
                        <div style="display:flex; gap:8px; align-items:stretch;">
                            <select id="tipoDoc" name="tipo_documento" class="rg-input"
                                    aria-label="Tipo de documento"
                                    style="flex:0 0 84px; width:84px; padding-left:12px;">
                                <option value="CC">C.C.</option>
                                <option value="TI">T.I.</option>
                                <option value="CE">C.E.</option>
                            </select>
                            <div class="rg-input-wrap" style="flex:1 1 auto;">
                                <i class="bi bi-card-text rg-ico"></i>
                                <input type="text" id="doc" name="documento_identidad" class="rg-input"
                                       placeholder="Solo números" autocomplete="off"
                                       inputmode="numeric" data-rule="digits" pattern="\d+"
                                       minlength="4" maxlength="50" required>
                            </div>
                        </div>
                    </div>
                    <div class="rg-field">
                        <label class="rg-label" for="tel">Teléfono</label>
                        <div class="rg-input-wrap">
                            <i class="bi bi-phone rg-ico"></i>
                            <input type="tel" id="tel" name="telefono" class="rg-input"
                                   placeholder="300 000 0000" autocomplete="tel"
                                   inputmode="numeric" data-rule="digits"
                                   minlength="7" maxlength="20" required>
                        </div>
                    </div>
                </div>

                <!-- Contraseña + Confirmar (misma fila) -->
                <div class="rg-row">
                    <div class="rg-field">
                        <label class="rg-label" for="pwd">Contraseña</label>
                        <div class="rg-input-wrap rg-pwd-wrap">
                            <i class="bi bi-lock rg-ico"></i>
                            <input type="password" id="pwd" name="password" class="rg-input"
                                   placeholder="Mín. 8 caracteres" autocomplete="new-password"
                                   minlength="8" required>
                            <button type="button" class="rg-eye" data-target="pwd">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                        <span class="rg-feedback" id="pwdFb"></span>
                    </div>
                    <div class="rg-field">
                        <label class="rg-label" for="pwdC">Confirmar</label>
                        <div class="rg-input-wrap rg-pwd-wrap">
                            <i class="bi bi-shield-lock rg-ico"></i>
                            <input type="password" id="pwdC" name="password_confirmacion" class="rg-input"
                                   placeholder="Repite" autocomplete="new-password"
                                   minlength="8" required>
                            <button type="button" class="rg-eye" data-target="pwdC">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                        <span class="rg-feedback" id="pwdCFb"></span>
                    </div>
                </div>

                <!-- Submit -->
                <button type="submit" class="rg-submit" id="registerSubmitBtn">
                    <span>Crear cuenta</span>
                    <i class="bi bi-arrow-right-circle-fill"></i>
                </button>
            </form>

            <!-- Footer -->
            <div class="rg-footer">
                <a href="/dashboard" class="rg-guest">
                    <i class="bi bi-eye"></i> Explorar como invitado
                </a>
                <p>¿Ya tienes cuenta? <a href="/login">Inicia sesión</a></p>
            </div>

        </div>
    </main>
</div>

<script>
(function () {
    /* ── Toggle password visibility ── */
    document.querySelectorAll('.rg-eye').forEach(btn => {
        btn.addEventListener('click', () => {
            const inp = document.getElementById(btn.dataset.target);
            const show = inp.type === 'password';
            inp.type = show ? 'text' : 'password';
            btn.querySelector('i').className = 'bi ' + (show ? 'bi-eye-slash' : 'bi-eye');
        });
    });

    /* ── Password validation ── */
    const pwd  = document.getElementById('pwd');
    const pwdC = document.getElementById('pwdC');
    const fb   = document.getElementById('pwdFb');
    const fbC  = document.getElementById('pwdCFb');

    function validate() {
        fb.textContent = ''; fb.className = 'rg-feedback';
        fbC.textContent = ''; fbC.className = 'rg-feedback';
        let ok = true;

        if (pwd.value && pwd.value.length < 8) {
            fb.textContent = 'Mínimo 8 caracteres.';
            fb.classList.add('rg-fb-err');
            ok = false;
        } else if (pwd.value.length >= 8) {
            fb.textContent = '✓ Contraseña válida';
            fb.classList.add('rg-fb-ok');
        }

        if (pwdC.value && pwd.value !== pwdC.value) {
            fbC.textContent = 'No coinciden.';
            fbC.classList.add('rg-fb-err');
            ok = false;
        } else if (pwdC.value && pwd.value === pwdC.value) {
            fbC.textContent = '✓ Coincide';
            fbC.classList.add('rg-fb-ok');
        }
        return ok;
    }

    [pwd, pwdC].forEach(i => i?.addEventListener('input', validate));

    /* ── Submit ── */
    document.getElementById('registerForm')?.addEventListener('submit', e => {
        if (!validate()) { e.preventDefault(); return; }
        const btn = document.getElementById('registerSubmitBtn');
        btn.disabled = true;
        btn.classList.add('rg-loading');
    });
})();
</script>
<script src="/assets/js/input-rules.js"></script>
</body>
</html>
