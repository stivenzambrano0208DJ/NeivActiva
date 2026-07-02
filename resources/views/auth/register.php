<div class="container" style="display: flex; justify-content: center; align-items: center; min-height: 80vh; padding: 2rem 0;">
    <div class="card" style="width: 100%; max-width: 550px;">
        <div class="text-center mb-4">
            <h2 class="gradient-text">Crear Cuenta</h2>
            <p class="text-muted">Únete a NeivActiva y gestiona tus eventos</p>
        </div>

        <?php if(isset($error)): ?>
            <div style="background: rgba(244, 63, 94, 0.1); color: var(--accent-color); padding: 1rem; border-radius: var(--border-radius); margin-bottom: 1.5rem; border: 1px solid rgba(244, 63, 94, 0.2);">
                <i class="fa-solid fa-circle-exclamation"></i> <?= $error ?>
            </div>
        <?php endif; ?>

        <form action="/NeivActiva/register" method="POST">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div class="form-group">
                    <label class="form-label" for="nombres">Nombres</label>
                    <input type="text" id="nombres" name="nombres" class="form-control" required>
                </div>
                <div class="form-group">
                    <label class="form-label" for="apellidos">Apellidos</label>
                    <input type="text" id="apellidos" name="apellidos" class="form-control" required>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label" for="email">Correo Electrónico</label>
                <input type="email" id="email" name="email" class="form-control" required>
            </div>
            
            <div class="form-group">
                <label class="form-label" for="identificacion">Cédula / Identificación</label>
                <input type="text" id="identificacion" name="identificacion" class="form-control" required>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div class="form-group">
                    <label class="form-label" for="password">Contraseña</label>
                    <input type="password" id="password" name="password" class="form-control" required>
                </div>
                <div class="form-group">
                    <label class="form-label" for="password_confirm">Confirmar Contraseña</label>
                    <input type="password" id="password_confirm" name="password_confirm" class="form-control" required>
                </div>
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 1rem;">
                <i class="fa-solid fa-user-plus"></i> Registrarse
            </button>
        </form>
        
        <div class="text-center mt-4">
            <p class="text-muted">¿Ya tienes cuenta? <a href="/NeivActiva/login">Inicia sesión</a></p>
        </div>
    </div>
</div>
