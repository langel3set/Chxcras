<?php
use App\Core\BaseDatos;

/*
  NOTA: no iniciamos session_start() aquí; lo hace el controlador.
  La vista solo muestra $trabajador, $niveles y $mensaje_error provistos por el controlador.
*/

include __DIR__ . '/../layout/sidebar.php';
include __DIR__ . '/../layout/encabezado.php';
?>

<div class="dashboard-wrapper">
    <a href="<?= BASE_URL ?>rrhh" class="btn-volver">← Volver</a>
    <div class="dashboard centrado">
        <h2>✏️ Editar Trabajador</h2>

        <!-- Mostrar mensaje de error si lo hay -->
        <?php if (!empty($mensaje_error)): ?>
            <div class="mensaje-error"><?= htmlspecialchars($mensaje_error) ?></div>
        <?php endif; ?>

        <form method="POST" class="formulario-editar" id="formEditar" novalidate>
            <label>Nombre:</label>
            <input type="text" name="nombre" value="<?= htmlspecialchars($_POST['nombre'] ?? $trabajador['nombre']) ?>" required>

            <label>Correo:</label>
            <input type="email" name="correo" value="<?= htmlspecialchars($_POST['correo'] ?? $trabajador['correo']) ?>" required>

            <label>DNI:</label>
            <input type="text"
                   name="dni"
                   maxlength="8"
                   pattern="\d{8}"
                   value="<?= htmlspecialchars($_POST['dni'] ?? ($trabajador['dni'] ?? '')) ?>"
                   required
                   title="El DNI debe tener exactamente 8 números"
                   oninput="validarMaxLength(this, 8, 'El DNI no puede tener más de 8 dígitos.')">

            <label>Celular:</label>
            <input type="text"
                   name="celular"
                   maxlength="9"
                   pattern="\d{9}"
                   value="<?= htmlspecialchars($_POST['celular'] ?? ($trabajador['celular'] ?? '')) ?>"
                   required
                   title="El celular debe tener exactamente 9 números"
                   oninput="validarMaxLength(this, 9, 'El celular no puede tener más de 9 dígitos.')">

            <label>Nivel:</label>
            <select name="id_nivel" required>
                <option value="">-- Selecciona un nivel --</option>
                <?php foreach ($niveles as $n): ?>
                    <?php $selected = (int)(($_POST['id_nivel'] ?? $trabajador['id_nivel']) ) === (int)$n['id_nivel']; ?>
                    <option value="<?= $n['id_nivel'] ?>" <?= $selected ? 'selected' : '' ?>>
                        <?= htmlspecialchars($n['nombre']) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <!-- Select de Estado: activo / inactivo -->
            <label>Estado:</label>
            <?php $selEstado = htmlspecialchars($_POST['estado'] ?? ($trabajador['estado'] ?? 'activo')); ?>
            <select name="estado" required>
                <option value="activo" <?= $selEstado === 'activo' ? 'selected' : '' ?>>Activo</option>
                <option value="inactivo" <?= $selEstado === 'inactivo' ? 'selected' : '' ?>>Inactivo</option>
            </select>

            <button type="submit" class="btn-guardar">Guardar Cambios</button>
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

document.getElementById('formEditar').addEventListener('submit', function(e) {
    const dni = document.querySelector('[name="dni"]').value.trim();
    const celular = document.querySelector('[name="celular"]').value.trim();
    const correo = document.querySelector('[name="correo"]').value.trim();
    const estado = document.querySelector('[name="estado"]').value;

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

    // Validar formato de correo
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(correo)) {
        alert("El formato del correo no es válido.");
        e.preventDefault();
        return;
    }

    // Validar estado permitido
    if (!['activo', 'inactivo'].includes(estado)) {
        alert("Estado inválido.");
        e.preventDefault();
        return;
    }
});
</script>

<style>
.dashboard-wrapper {
    margin-left: 274px;
    padding: 80px 30px;
    font-family: 'Segoe UI', sans-serif;
    background: #f5f7fa;
    min-height: 100vh;
}
.dashboard.centrado {
    display: flex;
    justify-content: center;
    align-items: center;
    flex-direction: column;
}
.dashboard h2 {
    font-size: 30px;
    margin-bottom: 30px;
    color: #104031;
    text-align: center;
}
.formulario-editar {
    background: #fff;
    padding: 35px 40px;
    width: 100%;
    max-width: 550px;
    border-radius: 12px;
    box-shadow: 0 4px 14px rgba(0,0,0,0.12);
}
.formulario-editar label {
    display: block;
    margin-top: 18px;
    font-weight: 600;
    font-size: 15px;
}
.formulario-editar input, .formulario-editar select {
    width: 100%;
    padding: 13px;
    margin-top: 8px;
    margin-bottom: 16px;
    border: 1px solid #ccc;
    border-radius: 7px;
    font-size: 15px;
    transition: border 0.3s;
}
.formulario-editar input:focus, .formulario-editar select:focus {
    border-color: #007bff;
    outline: none;
}
.btn-guardar {
    background: #007bff;
    color: white;
    padding: 14px 28px;
    border: none;
    border-radius: 7px;
    font-size: 15px;
    cursor: pointer;
    transition: background 0.3s;
    width: 100%;
}
.btn-guardar:hover {
    background: #0056b3;
}
.mensaje-error {
    background: #f8d7da;
    color: #842029;
    padding: 12px 18px;
    border-radius: 8px;
    margin-bottom: 20px;
    border: 1px solid #f5c2c7;
    font-weight: 500;
    text-align: center;
}
.btn-volver {
    display: inline-block;
    background: #6c757d;
    color: white;
    padding: 10px 20px;
    margin-bottom: 20px;
    border-radius: 6px;
    text-decoration: none;
    font-size: 14px;
    transition: background 0.3s;
}
.btn-volver:hover {
    background: #5a6268;
}
</style>
