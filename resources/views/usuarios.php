<?php
$editando  = is_array($usuarioEditar ?? null);
$formData  = $oldInput ?: ($editando ? $usuarioEditar : []);
$val       = fn($f, $d='') => htmlspecialchars((string)($formData[$f] ?? $d), ENT_QUOTES, 'UTF-8');

$msgOk  = ['creado'=>'Usuario creado correctamente.','actualizado'=>'Usuario actualizado.','eliminado'=>'Usuario eliminado.'];
$msgErr = ['csrf'=>'Sesión expirada.','validacion'=>'Revisa el formulario.','bd'=>'Error en base de datos.','no_encontrado'=>'Usuario no encontrado.','self_delete'=>'No puedes eliminar tu propio usuario.'];

$totalUsuarios  = count($lista_usuarios);
$rolIcons       = ['admin'=>'bi-shield-fill','organizador'=>'bi-person-gear','cliente'=>'bi-person'];
$rolColors      = ['admin'=>'usr-role--red','organizador'=>'usr-role--blue','cliente'=>'usr-role--green'];

$iniciales = function($nombre) {
    $p = preg_split('/\s+/', trim((string)$nombre));
    $i = '';
    foreach ($p as $w) { if ($w!=='') $i.=strtoupper($w[0]); if(strlen($i)>=2) break; }
    return $i ?: 'NA';
};
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NeivActiva – Usuarios</title>
    <link rel="stylesheet" href="/NeivActiva/public/assets/css/neivactiva-2026.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="/NeivActiva/public/assets/css/views/usuarios.css">
</head>
<body>
<?php include 'partials/sidebar.php'; ?>

<main class="main-wrapper usr-page">

    <!-- ── Topbar ───────────────────────────────── -->
    <header class="usr-topbar">
        <div class="usr-topbar-left">
            <div class="usr-page-icon"><i class="bi bi-people-fill"></i></div>
            <div>
                <h1 class="usr-page-title">Gestión de Usuarios</h1>
                <p class="usr-page-sub">Administra cuentas, roles y permisos de la plataforma</p>
            </div>
        </div>
        <div class="usr-topbar-right">
            <div class="usr-stat-pill">
                <i class="bi bi-people"></i>
                <span><?php echo $totalUsuarios; ?> usuarios</span>
            </div>
            <button class="usr-btn-primary" id="openCreateBtn">
                <i class="bi bi-person-plus-fill"></i> Nuevo usuario
            </button>
        </div>
    </header>

    <!-- ── Toast ────────────────────────────────── -->
    <?php if (!empty($_GET['msg']) && isset($msgOk[$_GET['msg']])): ?>
        <div class="usr-toast usr-toast--ok" id="toastMsg">
            <i class="bi bi-check-circle-fill"></i>
            <span><?php echo $msgOk[$_GET['msg']]; ?></span>
            <button onclick="this.parentElement.remove()"><i class="bi bi-x"></i></button>
        </div>
    <?php elseif (!empty($_GET['error']) && isset($msgErr[$_GET['error']])): ?>
        <div class="usr-toast usr-toast--err" id="toastMsg">
            <i class="bi bi-exclamation-circle-fill"></i>
            <span><?php echo $msgErr[$_GET['error']]; ?></span>
            <button onclick="this.parentElement.remove()"><i class="bi bi-x"></i></button>
        </div>
    <?php endif; ?>

    <!-- ── Grid ─────────────────────────────────── -->
    <div class="usr-grid">

        <!-- ── LEFT: Users table ────────────────── -->
        <div class="usr-card usr-list-card">
            <div class="usr-toolbar">
                <form method="GET" action="" class="usr-search-form">
                    <input type="hidden" name="view" value="usuarios">
                    <div class="usr-search-wrap">
                        <i class="bi bi-search"></i>
                        <input type="text" name="q" class="usr-input"
                               placeholder="Buscar por nombre, correo o rol…"
                               value="<?php echo htmlspecialchars($busqueda); ?>"
                               id="searchInput" autocomplete="off">
                        <?php if ($busqueda): ?>
                            <a href="?view=usuarios" class="usr-clear" title="Limpiar">
                                <i class="bi bi-x-circle-fill"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                    <button type="submit" class="usr-btn-primary usr-btn-sm">
                        <i class="bi bi-search"></i> Buscar
                    </button>
                </form>
                <span class="usr-count-badge"><?php echo $totalUsuarios; ?> usuarios</span>
            </div>

            <?php if (empty($lista_usuarios)): ?>
                <div class="usr-empty">
                    <i class="bi bi-person-x"></i>
                    <p><?php echo $busqueda ? 'Sin resultados para "' . htmlspecialchars($busqueda) . '".' : 'No hay usuarios registrados.'; ?></p>
                </div>
            <?php else: ?>
                <div class="usr-table-wrap">
                    <table class="usr-table">
                        <thead>
                            <tr>
                                <th>Usuario</th>
                                <th>Documento</th>
                                <th>Rol</th>
                                <th>Registrado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($lista_usuarios as $u):
                                $uId    = (int)($u['id'] ?? 0);
                                $nombre = htmlspecialchars($u['nombre'] ?? '—');
                                $correo = htmlspecialchars($u['correo'] ?? '');
                                $doc    = htmlspecialchars($u['documento_identidad'] ?? '—');
                                $rol    = $u['rol'] ?? 'cliente';
                                $fecha  = !empty($u['creado_en']) ? date('d/m/Y', strtotime($u['creado_en'])) : '—';
                                $ini    = $iniciales($u['nombre'] ?? '');
                                $esYo   = $uId === (int)($_SESSION['usuario_id'] ?? 0);
                                $esEdit = $editando && (int)($usuarioEditar['id'] ?? 0) === $uId;
                                $rolIco = $rolIcons[$rol] ?? 'bi-person';
                                $rolCol = $rolColors[$rol] ?? 'usr-role--green';
                            ?>
                            <tr class="usr-row<?php echo $esEdit ? ' usr-row-editing' : ''; ?>">
                                <td class="usr-td-user">
                                    <div class="usr-avatar usr-avatar-<?php echo htmlspecialchars($rol); ?>"><?php echo $ini; ?></div>
                                    <div class="usr-uinfo">
                                        <span class="usr-uname"><?php echo $nombre; ?></span>
                                        <span class="usr-usub"><?php echo $correo; ?></span>
                                    </div>
                                </td>
                                <td class="usr-td-doc"><?php echo $doc; ?></td>
                                <td>
                                    <span class="usr-role-badge <?php echo $rolCol; ?>">
                                        <i class="bi <?php echo $rolIco; ?>"></i>
                                        <?php echo ucfirst($rol); ?>
                                    </span>
                                </td>
                                <td class="usr-td-date"><?php echo $fecha; ?></td>
                                <td class="usr-td-actions">
                                    <a href="?view=usuarios&editar=<?php echo $uId; ?>#form-panel"
                                       class="usr-btn-icon usr-btn-edit" title="Editar">
                                        <i class="bi bi-pencil-square"></i>
                                    </a>
                                    <?php if (!$esYo): ?>
                                    <form method="POST" action="/NeivActiva/public/admin/usuarios" class="usr-inline-form"
                                          onsubmit="return confirm('¿Eliminar a <?php echo addslashes($nombre); ?>? Esta acción no se puede deshacer.')">
                                        <input type="hidden" name="accion"     value="eliminar">
                                        <input type="hidden" name="usuario_id" value="<?php echo $uId; ?>">
                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                                        <button type="submit" class="usr-btn-icon usr-btn-del" title="Eliminar">
                                            <i class="bi bi-trash3"></i>
                                        </button>
                                    </form>
                                    <?php else: ?>
                                        <span class="usr-me-badge"><i class="bi bi-person-check-fill"></i> Tú</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="usr-table-footer">
                    <span class="usr-footer-count"><?php echo $totalUsuarios; ?> usuario<?php echo $totalUsuarios!==1?'s':''; ?></span>
                </div>
            <?php endif; ?>
        </div>

        <!-- ── RIGHT: Form ──────────────────────── -->
        <div class="usr-right-col" id="form-panel">
            <div class="usr-card usr-form-card">
                <div class="usr-card-header">
                    <h2 class="usr-card-title">
                        <i class="bi bi-<?php echo $editando ? 'pencil-square' : 'person-plus-fill'; ?>"></i>
                        <?php echo $editando ? 'Editar usuario' : 'Nuevo usuario'; ?>
                    </h2>
                    <?php if ($editando): ?>
                        <a href="?view=usuarios" class="usr-btn-icon usr-btn-cancel" title="Cancelar">
                            <i class="bi bi-x-lg"></i>
                        </a>
                    <?php endif; ?>
                </div>

                <?php if (!empty($formErrors)): ?>
                    <div class="usr-form-errors">
                        <i class="bi bi-exclamation-triangle-fill"></i>
                        <ul><?php foreach ($formErrors as $e): ?><li><?php echo htmlspecialchars($e); ?></li><?php endforeach; ?></ul>
                    </div>
                <?php endif; ?>

                <form method="POST" action="/NeivActiva/public/admin/usuarios" class="usr-form">
                    <input type="hidden" name="csrf_token"  value="<?php echo htmlspecialchars($csrfToken); ?>">
                    <input type="hidden" name="accion"      value="<?php echo $editando ? 'actualizar' : 'crear'; ?>">
                    <?php if ($editando): ?>
                        <input type="hidden" name="usuario_id"  value="<?php echo (int)($usuarioEditar['id']??0); ?>">
                    <?php endif; ?>

                    <div class="usr-field">
                        <label class="usr-label">Nombre completo <span class="usr-req">*</span></label>
                        <input type="text" name="nombre" class="usr-input" value="<?php echo $val('nombre'); ?>"
                               placeholder="Nombre y apellidos" required maxlength="120">
                    </div>

                    <div class="usr-field">
                        <label class="usr-label">Correo electrónico <span class="usr-req">*</span></label>
                        <input type="email" name="correo" class="usr-input" value="<?php echo $val('correo'); ?>"
                               placeholder="correo@ejemplo.com" required maxlength="120">
                    </div>

                    <div class="usr-field-row">
                        <div class="usr-field">
                            <label class="usr-label">Documento de identidad</label>
                            <input type="text" name="documento_identidad" class="usr-input"
                                   value="<?php echo $val('documento_identidad'); ?>"
                                   placeholder="CC / TI / CE" maxlength="30">
                        </div>
                        <div class="usr-field">
                            <label class="usr-label">Teléfono</label>
                            <input type="text" name="telefono" class="usr-input"
                                   value="<?php echo $val('telefono'); ?>"
                                   placeholder="3101234567" maxlength="20">
                        </div>
                    </div>

                    <div class="usr-field">
                        <label class="usr-label">Rol <span class="usr-req">*</span></label>
                        <select name="rol" class="usr-select" required>
                            <?php foreach ($rolesPermitidos as $r): ?>
                                <option value="<?php echo htmlspecialchars($r); ?>"
                                    <?php echo ($formData['rol'] ?? 'cliente') === $r ? 'selected' : ''; ?>>
                                    <?php echo ucfirst($r); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="usr-field">
                        <label class="usr-label">
                            <?php echo $editando ? 'Nueva contraseña' : 'Contraseña'; ?>
                            <?php if (!$editando): ?><span class="usr-req">*</span><?php endif; ?>
                            <?php if ($editando): ?><small class="usr-hint">(dejar vacío para no cambiar)</small><?php endif; ?>
                        </label>
                        <input type="password" name="password" class="usr-input"
                               <?php echo $editando ? '' : 'required'; ?>
                               minlength="8" autocomplete="new-password"
                               placeholder="<?php echo $editando ? 'Nueva contraseña…' : 'Mínimo 8 caracteres'; ?>">
                    </div>

                    <button type="submit" class="usr-btn-submit">
                        <i class="bi bi-<?php echo $editando ? 'check-lg' : 'person-plus-fill'; ?>"></i>
                        <?php echo $editando ? 'Guardar cambios' : 'Crear usuario'; ?>
                    </button>
                </form>
            </div>
        </div>
    </div>
</main>

<script>
(function(){
    const toast = document.getElementById('toastMsg');
    if (toast) setTimeout(()=>{ toast.style.opacity='0'; toast.style.transition='opacity .3s'; setTimeout(()=>toast.remove(),350); }, 4000);
    document.getElementById('searchInput')?.addEventListener('keydown', e=>{ if(e.key==='Escape') e.target.value=''; });
    document.getElementById('openCreateBtn')?.addEventListener('click', ()=>{
        document.getElementById('form-panel')?.scrollIntoView({behavior:'smooth',block:'start'});
    });
})();
</script>
</body>
</html>
