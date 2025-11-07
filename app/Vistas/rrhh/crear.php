<?php
use App\Core\BaseDatos;

include __DIR__ . '/../layout/sidebar.php';
include __DIR__ . '/../layout/encabezado.php';
?>

<div class="dashboard-wrapper">
    <div class="dashboard">
        <a href="<?= BASE_URL ?>rrhh" class="btn-volver">← Volver</a>
        <h2>+ Registrar Nuevo Trabajador</h2>

        <!-- Mostrar mensaje de error (viene del controlador) -->
        <?php if (!empty($mensaje_error)): ?>
            <div class="mensaje-error"><?= htmlspecialchars($mensaje_error) ?></div>
        <?php endif; ?>

        <form method="POST" class="formulario" id="formRegistro" novalidate>
            <label>Nombre:</label>
            <input type="text" name="nombre" required value="<?= htmlspecialchars($_POST['nombre'] ?? '') ?>">

            <label>Correo:</label>
            <input type="email" name="correo" required value="<?= htmlspecialchars($_POST['correo'] ?? '') ?>">

            <label>Contraseña:</label>
            <input type="password" name="contraseña" minlength="5" required
                title="Debe tener al menos 5 caracteres con letras y números">

            <label>DNI:</label>
            <input type="text" name="dni" maxlength="8" pattern="\d{8}" required
                title="El DNI debe tener exactamente 8 números"
                value="<?= htmlspecialchars($_POST['dni'] ?? '') ?>"
                oninput="validarMaxLength(this, 8, 'El DNI no puede tener más de 8 dígitos.')">

            <label>Celular:</label>
            <input type="text" name="celular" maxlength="9" pattern="\d{9}" required
                title="El celular debe tener exactamente 9 números"
                value="<?= htmlspecialchars($_POST['celular'] ?? '') ?>"
                oninput="validarMaxLength(this, 9, 'El celular no puede tener más de 9 dígitos.')">

            <label>Nivel:</label>
            <select name="id_nivel" required>
                <option value="">-- Selecciona un nivel --</option>
                <?php foreach ($niveles as $n): ?>
                    <option value="<?= $n['id_nivel'] ?>" <?= (isset($_POST['id_nivel']) && $_POST['id_nivel'] == $n['id_nivel']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($n['nombre']) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <button type="submit" class="btn-guardar">Guardar</button>
        </form>
    </div>
</div>

<script>
function validarMaxLength(input, max, mensaje) {
    if (input.value.length > max) {
        alert(mensaje);
        input.value = input.value.slice(0, max);
    }
}

document.getElementById('formRegistro').addEventListener('submit', function(e) {
    const dni = document.querySelector('[name="dni"]').value.trim();
    const celular = document.querySelector('[name="celular"]').value.trim();
    const contrasena = document.querySelector('[name="contraseña"]').value.trim();

    if (!/^\d{8}$/.test(dni)) {
        alert("El DNI debe tener exactamente 8 números.");
        e.preventDefault();
        return;
    }

    if (!/^\d{9}$/.test(celular)) {
        alert("El celular debe tener exactamente 9 números.");
        e.preventDefault();
        return;
    }

    if (contrasena.length < 5) {
        alert("La contraseña debe tener al menos 5 caracteres.");
        e.preventDefault();
        return;
    }

    if (!/(?=.*[A-Za-z])(?=.*\d)/.test(contrasena)) {
        alert("La contraseña debe incluir al menos una letra y un número.");
        e.preventDefault();
        return;
    }
});
</script>

<style>
.dashboard-wrapper { margin-left: 274px; padding: 100px 30px; font-family: 'Segoe UI', sans-serif; background: #f5f7fa; min-height: 100vh;}
.dashboard h2 { font-size: 28px; margin-bottom: 25px; color: #104031; text-align: center;}
.formulario { background: #fff; padding: 30px; max-width: 600px; margin: 0 auto; border-radius: 10px; box-shadow: 0 3px 10px rgba(0,0,0,0.1);}
.formulario label { display: block; margin-top: 15px; font-weight: bold; font-size: 14px;}
.formulario input, .formulario select { width: 100%; padding: 12px; margin-top: 6px; margin-bottom: 14px; border: 1px solid #ccc; border-radius: 6px; font-size: 14px;}
.btn-guardar { background: #28a745; color: white; padding: 12px 24px; border: none; border-radius: 6px; font-size: 14px; cursor: pointer; transition: background 0.3s;}
.btn-guardar:hover { background: #1e7e34;}
.btn-volver { display: inline-block; background: #6c757d; color: white; padding: 10px 20px; margin-bottom: 20px; border-radius: 6px; text-decoration: none; font-size: 14px; transition: background 0.3s;}
.btn-volver:hover { background: #5a6268;}
.mensaje-error { background: #f8d7da; color: #842029; padding: 12px 18px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #f5c2c7; font-weight: 500; text-align: center;}
</style>

<?php ob_end_flush(); ?>
