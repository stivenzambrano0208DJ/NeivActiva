<div class="container" style="display: flex; justify-content: center; align-items: center; min-height: 70vh;">
    <div class="card" style="width: 100%; max-width: 450px;">
        <div class="text-center mb-4">
            <h2 class="gradient-text">Iniciar Sesión</h2>
            <p class="text-muted">Ingresa tus credenciales para continuar</p>
        </div>

        <?php if(isset($error)): ?>
            <div style="background: rgba(244, 63, 94, 0.1); color: var(--accent-color); padding: 1rem; border-radius: var(--border-radius); margin-bottom: 1.5rem; border: 1px solid rgba(244, 63, 94, 0.2);">
                <i class="fa-solid fa-circle-exclamation"></i> <?= $error ?>
            </div>
        <?php endif; ?>

        <form action="/login" method="POST">
            <div class="form-group">
                <label class="form-label" for="email">Correo Electrónico</label>
                <input type="email" id="email" name="email" class="form-control" placeholder="ejemplo@correo.com" required>
            </div>
            
            <div class="form-group">
                <label class="form-label" for="password">Contraseña</label>
                <input type="password" id="password" name="password" class="form-control" placeholder="••••••••" required>
            </div>

            <div class="form-group" style="display: flex; justify-content: space-between; align-items: center;">
                <label style="display: flex; align-items: center; gap: 0.5rem; color: var(--text-secondary); cursor: pointer;">
                    <input type="checkbox" name="remember" style="accent-color: var(--primary-color);"> Recordarme
                </label>
                <a href="/recovery" style="font-size: 0.875rem;">¿Olvidaste tu contraseña?</a>
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 1rem;">
                <i class="fa-solid fa-right-to-bracket"></i> Entrar
            </button>
        </form>
        
        <div class="text-center mt-4">
            <p class="text-muted">¿No tienes cuenta? <a href="/register">Regístrate aquí</a></p>
        </div>
    </div>
</div>
