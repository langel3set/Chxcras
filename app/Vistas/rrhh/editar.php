<?php
use App\Core\BaseDatos;

/*
  Vista de editar: recibe $trabajador (array), $niveles (array) y $mensaje_error (string)
*/

include __DIR__ . '/../layout/sidebar.php';
include __DIR__ . '/../layout/encabezado.php';
?>

<div class="dashboard-wrapper">
    <a href="<?= BASE_URL ?>rrhh" class="btn-volver">← Volver</a>
    <div class="dashboard centrado">
        <h2>✏️ Editar Trabajador</h2>

        <?php if (!empty($mensaje_error)): ?>
            <div class="mensaje-error"><?= htmlspecialchars($mensaje_error) ?></div>
        <?php endif; ?>

        <form method="POST" class="formulario-editar" id="formEditar" novalidate>
            <label>Nombre:</label>
            <input type="text"
                   name="nombre"
                   id="nombre"
                   maxlength="50"
                   value="<?= htmlspecialchars($_POST['nombre'] ?? $trabajador['nombre']) ?>"
                   required
                   oninput="enforceName(this)">

            <label>Correo:</label>
            <input type="email" name="correo" id="correo" value="<?= htmlspecialchars($_POST['correo'] ?? $trabajador['correo']) ?>" required>

            <label>DNI / Pasaporte (no editable):</label>
            <input type="text" name="dni" id="dni" value="<?= htmlspecialchars($trabajador['dni'] ?? '') ?>" readonly>

            <label>Celular:</label>
            <input type="text"
                   name="celular"
                   id="celular"
                   maxlength="9"
                   value="<?= htmlspecialchars($_POST['celular'] ?? $trabajador['celular'] ?? '') ?>"
                   required
                   oninput="enforceNumericMax(this)">

            <label>Nivel:</label>
            <select name="id_nivel" required>
                <option value="">-- Selecciona un nivel --</option>
                <?php foreach ($niveles as $n): ?>
                    <?php $sel = (int)(($_POST['id_nivel'] ?? $trabajador['id_nivel'])) === (int)$n['id_nivel']; ?>
                    <option value="<?= $n['id_nivel'] ?>" <?= $sel ? 'selected' : '' ?>>
                        <?= htmlspecialchars($n['nombre']) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label>Estado:</label>
            <select name="estado" required>
                <option value="activo" <?= ($trabajador['estado'] ?? 'activo') === 'activo' ? 'selected' : '' ?>>Activo</option>
                <option value="inactivo" <?= ($trabajador['estado'] ?? '') === 'inactivo' ? 'selected' : '' ?>>Inactivo</option>
            </select>

            <button type="submit" class="btn-guardar">Guardar Cambios</button>
        </form>
    </div>
</div>

<script>
function enforceName(input) {
    const before = input.value;
    const cleaned = before.replace(/\d/g, '').slice(0,50);
    if (before.length > 50) {
        alert("Nombre: máximo 50 caracteres.");
    }
    input.value = cleaned;
}

function enforceNumericMax(input) {
    let v = input.value.replace(/\D/g, '');
    const max = parseInt(input.getAttribute('maxlength') || 999, 10);
    if (v.length > max) {
        alert("No puedes escribir más caracteres.");
        v = v.slice(0, max);
    }
    input.value = v;
}

document.getElementById('formEditar').addEventListener('submit', function(e) {
    const nombre = document.getElementById('nombre').value.trim();
    const celular = document.getElementById('celular').value.trim();
    const correo = document.getElementById('correo').value.trim();

    if (!/^[^\d]{1,50}$/u.test(nombre)) {
        alert("El nombre solo puede contener letras y símbolos (sin números) y hasta 50 caracteres.");
        e.preventDefault();
        return;
    }

    if (!/^\d{9}$/.test(celular)) {
        alert("El celular debe tener exactamente 9 números.");
        e.preventDefault();
        return;
    }

    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(correo)) {
        alert("El formato del correo no es válido.");
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
.btn-guardar:hover { background: #0056b3; }
.mensaje-error { background: #f8d7da; color: #842029; padding: 12px 18px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #f5c2c7; font-weight: 500; text-align: center;}
</style>
