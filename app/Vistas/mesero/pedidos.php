<?php include __DIR__ . '/../layout/sidebar.php'; ?> 
<?php include __DIR__ . '/../layout/encabezado.php'; ?>
<div class="pedido-wrapper">
    <div class="pedido-header">
        <h2>📝 Gestión de Pedidos</h2>

        <!-- SELECCIONAR ÁREA -->
        <div class="mesa-select">
            <label for="area-select">Seleccionar área:</label>
            <select id="area-select" required>
                <option value="">-- Elegir área --</option>
                <?php foreach ($areas as $area): ?>
                    <option value="<?= htmlspecialchars($area) ?>">
                        <?= htmlspecialchars($area) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- SELECCIONAR MESA -->
        <div class="mesa-select">
            <label for="mesa-select">Seleccionar mesa:</label>
            <select name="mesa" id="mesa-select" form="form-orden" required disabled>
                <option value="">-- Primero elige un área --</option>
            </select>
        </div>
    </div>

    <form method="POST" action="<?= BASE_URL ?>mesero/prepararOrden" id="form-orden" onsubmit="return validarFormulario();">

        <?php foreach ($agrupados as $categoria => $items): ?>
            <h3 class="categoria"><?= $categoria ?> 
                <span class="globo" id="contador-<?= strtolower(str_replace(' ', '_', $categoria)) ?>">0</span>
            </h3>

            <div class="grid-platos">
                <?php foreach ($items as $p): ?>
                    <div class="plato-card">
                        
                        <!-- Imagen -->
                        <img src="<?= $p['imagen'] 
                            ? BASE_URL . ltrim($p['imagen'],'/') 
                            : 'https://via.placeholder.com/180x120?text=' . urlencode($p['nombre']) ?>" 
                            alt="<?= htmlspecialchars($p['nombre']) ?>">

                        <div class="nombre"><?= htmlspecialchars($p['nombre']) ?></div>

                        <!-- Botones + / - -->
                        <div class="contador-box">
                            <button type="button" class="btn-restar"
                                onclick="restar(<?= $p['id_plato'] ?>, '<?= strtolower(str_replace(' ', '_', $categoria)) ?>')">–</button>

                            <div class="contador" id="count-<?= $p['id_plato'] ?>">0</div>

                            <button type="button" class="btn-sumar"
                                onclick="sumar(<?= $p['id_plato'] ?>, '<?= strtolower(str_replace(' ', '_', $categoria)) ?>')">+</button>
                        </div>

                        <!-- Inputs ocultos -->
                        <input type="hidden" name="plato[<?= $p['id_plato'] ?>]" value="0" id="plato-<?= $p['id_plato'] ?>" 
                            data-categoria="<?= strtolower(str_replace(' ', '_', $categoria)) ?>">

                        <input type="hidden" name="orden_entrega[<?= $p['id_plato'] ?>]" value="0" id="orden-<?= $p['id_plato'] ?>">

                        <!-- <div class="orden-badge" id="orden-badge-<?= $p['id_plato'] ?>">-</div>-->

                    </div>
                <?php endforeach; ?>
            </div>

        <?php endforeach; ?>

        <div class="botonera">
            <button type="submit" class="btn-enviar">✅ Enviar Orden</button>
        </div>

    </form>
</div>

<!-- Scripts -->
<link rel="stylesheet" href="<?= BASE_URL ?>assets/css/mesero.css">
<script src="<?= BASE_URL ?>assets/js/mesero.js"></script>

<!-- SCRIPT PARA ÁREAS + MESAS -->
<script>
    const mesasPorArea = <?= json_encode($mesasPorArea) ?>;

    const areaSelect = document.getElementById("area-select");
    const mesaSelect = document.getElementById("mesa-select");

    areaSelect.addEventListener("change", function() {
        const area = this.value;

        mesaSelect.innerHTML = "";
        mesaSelect.disabled = true;

        if (area === "") {
            mesaSelect.innerHTML = '<option value="">-- Primero elige un área --</option>';
            return;
        }

        const mesas = mesasPorArea[area];

        mesaSelect.disabled = false;
        mesaSelect.innerHTML = '<option value="">-- Elegir mesa --</option>';

        mesas.forEach(mesa => {
            const opt = document.createElement("option");
            opt.value = mesa.id_mesa;
            opt.textContent = mesa.nombre;
            mesaSelect.appendChild(opt);
        });
    });
</script>

</body>
</html>