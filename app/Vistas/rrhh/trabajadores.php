<?php include __DIR__ . '/../layout/sidebar.php'; ?>
<?php include __DIR__ . '/../layout/encabezado.php'; ?>

<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>

<?php if (isset($_SESSION['mensaje_exito'])): ?>
    <div class="mensaje-exito" id="mensajeExito">
        <?= $_SESSION['mensaje_exito']; unset($_SESSION['mensaje_exito']); ?>
    </div>
<?php endif; ?>

<div class="contenedor">
    <h2 class="titulo">👥 Gestión de Trabajadores</h2>

    <div class="acciones">
        <a href="<?= BASE_URL ?>rrhh/crear" class="btn crear">+ Crear trabajador</a>
        <!-- Botón para exportar -->
        <a href="<?= BASE_URL ?>rrhh/exportar" class="btn crear" style="background:#4b73ff; margin-left:8px;">⬇️ Exportar (CSV)</a>
    </div>

    <div class="tabla-contenedor">
        <table class="tabla-trabajadores">
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Correo</th>
                    <th>Rol</th>
                    <th>Nivel</th>
                    <th>DNI / Pasaporte</th>
                    <th>Celular</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($trabajadores as $t): ?>
                    <tr>
                        <td><?= htmlspecialchars($t['nombre']) ?></td>
                        <td><?= htmlspecialchars($t['correo']) ?></td>
                        <td><?= htmlspecialchars($t['rol']) ?></td>
                        <td><?= htmlspecialchars($t['nivel']) ?></td>
                        <td><?= htmlspecialchars($t['dni'] ?? '') ?></td>
                        <td><?= htmlspecialchars($t['celular'] ?? '') ?></td>
                        <td>
                            <?php if (($t['estado'] ?? 'activo') === 'activo'): ?>
                                <span style="color:#155724;background:#d4edda;padding:6px 10px;border-radius:8px;font-weight:600;">Activo</span>
                            <?php else: ?>
                                <span style="color:#721c24;background:#f8d7da;padding:6px 10px;border-radius:8px;font-weight:600;">Inactivo</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="<?= BASE_URL ?>rrhh/editar/<?= $t['id_usuario'] ?>" class="btn editar">✏️ Editar</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<style>
.contenedor {
    margin-left: 274px;
    padding: 80px 30px;
    font-family: 'Segoe UI', sans-serif;
    background: #f4f7f6;
    min-height: 100vh;
}
.titulo { margin-bottom: 35px; font-size: 32px; color: #0d3d2e; text-align: center;}
.acciones { text-align: right; margin-bottom: 18px; }
.acciones .crear { background: #17a2b8; color: white; padding: 10px 22px; border-radius: 8px; font-size: 15px; text-decoration: none; transition: background 0.3s, transform 0.2s;}
.acciones .crear:hover { background: #117a8b; transform: scale(1.05); }
.tabla-contenedor { overflow-x: auto; border-radius: 12px; box-shadow: 0 5px 15px rgba(0,0,0,0.12); }
.tabla-trabajadores { width: 100%; border-collapse: separate; border-spacing: 0; }
.tabla-trabajadores thead tr { background: #0d3d2e; color: white; }
.tabla-trabajadores th, .tabla-trabajadores td { padding: 16px 20px; font-size: 15px; border-bottom: 1px solid #e1e5ea; text-align: left; }
.tabla-trabajadores tbody tr { background: #ffffff; transition: background 0.3s, transform 0.2s; }
.tabla-trabajadores tbody tr:hover { background: #e8f6f0; transform: scale(1.005); }
.btn { display: inline-block; padding: 8px 18px; border-radius: 8px; text-decoration: none; font-size: 14px; font-weight: 500; cursor: pointer; transition: background 0.3s, transform 0.2s; }
.btn.editar { background:rgb(0, 224, 49); color: white; }
.btn.editar:hover { background:rgb(70, 238, 92); transform: translateY(-2px); }
.mensaje-exito { position: fixed; top: 80px; left: 50%; transform: translateX(-50%); background: #d1e7dd; color: #0f5132; padding: 14px 22px; border-radius: 8px; font-weight: 500; box-shadow: 0 4px 10px rgba(0,0,0,0.2); z-index: 1000; opacity: 1; transition: opacity 0.5s ease-out, top 0.5s ease-out; }
.mensaje-exito.ocultar { opacity: 0; top: 60px; }
</style>

<script>
document.addEventListener("DOMContentLoaded", () => {
    const mensaje = document.getElementById("mensajeExito");
    if (mensaje) {
        setTimeout(() => { mensaje.classList.add("ocultar"); }, 2500);
        setTimeout(() => { mensaje.remove(); }, 3000);
    }
});
</script>
