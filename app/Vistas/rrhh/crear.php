<?php
use App\Core\BaseDatos;

include __DIR__ . '/../layout/sidebar.php';
include __DIR__ . '/../layout/encabezado.php';

// $niveles y $roles deben venir del controlador
// $mensaje_error también (string)
?>

<div class="dashboard-wrapper">
    <div class="dashboard">
        <a href="<?= BASE_URL ?>rrhh" class="btn-volver">← Volver</a>
        <h2>+ Registrar Nuevo Trabajador</h2>

        <!-- Mensaje de error del servidor -->
        <?php if (!empty($mensaje_error)): ?>
            <div class="mensaje-error"><?= htmlspecialchars($mensaje_error) ?></div>
        <?php endif; ?>

        <form method="POST" class="formulario" id="formRegistro" novalidate>
            <label>Nombre:</label>
            <input type="text"
                   name="nombre"
                   id="nombre"
                   maxlength="50"
                   required
                   value="<?= htmlspecialchars($_POST['nombre'] ?? '') ?>"
                   oninput="enforceName(this)">

            <label>Correo:</label>
            <input type="email" name="correo" required value="<?= htmlspecialchars($_POST['correo'] ?? '') ?>">

            <label>Tipo de documento:</label>
            <select name="tipo_doc" id="tipo_doc" required>
                <option value="DNI" <?= (($_POST['tipo_doc'] ?? '') === 'DNI') ? 'selected' : '' ?>>DNI</option>
                <option value="PASAPORTE" <?= (($_POST['tipo_doc'] ?? '') === 'PASAPORTE') ? 'selected' : '' ?>>Pasaporte</option>
            </select>

            <label id="label_dni">DNI:</label>
            <input type="text"
                   name="dni"
                   id="dni"
                   inputmode="numeric"
                   maxlength="<?= isset($_POST['tipo_doc']) && $_POST['tipo_doc'] === 'PASAPORTE' ? 9 : 8 ?>"
                   required
                   value="<?= htmlspecialchars($_POST['dni'] ?? '') ?>"
                   oninput="enforceNumericMax(this)">

            <label>Celular:</label>
            <input type="text"
                   name="celular"
                   id="celular"
                   inputmode="numeric"
                   maxlength="9"
                   required
                   value="<?= htmlspecialchars($_POST['celular'] ?? '') ?>"
                   oninput="enforceNumericMax(this)">

            <label>Nivel:</label>
            <select name="id_nivel" required>
                <option value="">-- Selecciona un nivel --</option>
                <?php foreach ($niveles as $n): ?>
                    <option value="<?= $n['id_nivel'] ?>" <?= (isset($_POST['id_nivel']) && $_POST['id_nivel'] == $n['id_nivel']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($n['nombre']) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <p style="font-size:13px;color:#666;margin-top:6px;">
                Nota: la contraseña no se solicita aquí; se podrá habilitar o enviar un enlace de configuración más tarde.
            </p>

            <button type="submit" class="btn-guardar">Guardar</button>
        </form>
    </div>
</div>

<script>
function enforceName(input) {
    // Eliminar dígitos y limitar a 50 caracteres
    const before = input.value;
    const cleaned = before.replace(/\d/g, '').slice(0,50);
    if (before.length > cleaned.length) {
        // hubo dígitos o exceso
        // Solo mostrar alerta si excedió longitud real
        if (before.length > 50) {
            alert("Nombre: máximo 50 caracteres.");
        } else {
            // opcional: notificar si escribió números
            // alert("El nombre no puede contener números.");
        }
    }
    input.value = cleaned;
}

function enforceNumericMax(input) {
    // quitar todo lo que no sea dígito
    let v = input.value.replace(/\D/g, '');
    const max = parseInt(input.getAttribute('maxlength') || 999, 10);
    if (v.length > max) {
        alert("No puedes escribir más caracteres.");
        v = v.slice(0, max);
    }
    input.value = v;
}

document.getElementById('tipo_doc').addEventListener('change', function() {
    const tipo = this.value;
    const dni = document.getElementById('dni');
    const labelDni = document.getElementById('label_dni');
    if (tipo === 'DNI') {
        dni.maxLength = 8;
        dni.setAttribute('maxlength', '8');
        labelDni.textContent = 'DNI:';
        dni.title = "El DNI debe tener exactamente 8 números";
    } else {
        dni.maxLength = 9;
        dni.setAttribute('maxlength', '9');
        labelDni.textContent = 'Pasaporte:';
        dni.title = "El pasaporte debe tener exactamente 9 números";
    }
});

// Validación antes de enviar
document.getElementById('formRegistro').addEventListener('submit', function(e) {
    const nombre = document.getElementById('nombre').value.trim();
    const tipo = document.getElementById('tipo_doc').value;
    const dni = document.getElementById('dni').value.trim();
    const celular = document.getElementById('celular').value.trim();

    if (!/^[^\d]{1,50}$/u.test(nombre)) {
        alert("El nombre solo puede contener letras y símbolos (sin números) y hasta 50 caracteres.");
        e.preventDefault();
        return;
    }

    if (tipo === 'DNI' && !/^\d{8}$/.test(dni)) {
        alert("El DNI debe tener exactamente 8 números.");
        e.preventDefault();
        return;
    }

    if (tipo === 'PASAPORTE' && !/^\d{9}$/.test(dni)) {
        alert("El pasaporte debe tener exactamente 9 números.");
        e.preventDefault();
        return;
    }

    if (!/^\d{9}$/.test(celular)) {
        alert("El celular debe tener exactamente 9 números.");
        e.preventDefault();
        return;
    }
});
</script>

<style>
/* estilos ya conocidos (mantengo los tuyos) */
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
