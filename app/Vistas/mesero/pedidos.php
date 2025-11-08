<?php include __DIR__ . '/../layout/sidebar.php'; ?>
<?php include __DIR__ . '/../layout/encabezado.php'; ?>

<div class="pedido-wrapper">
    <div class="pedido-header">
        <h2>📝 Gestión de Pedidos</h2>
        <div class="mesa-select">
            <label for="mesa">Seleccionar mesa:</label>
            <select name="mesa" form="form-orden" required>
                <option value="">-- Elegir mesa --</option>
                <?php foreach ($mesas as $mesa): ?>
                    <option value="<?= $mesa['id_mesa'] ?>"><?= htmlspecialchars($mesa['nombre']) ?></option>
                <?php endforeach; ?>
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
                        <img src="<?= $p['imagen'] 
                            ? BASE_URL . ltrim($p['imagen'],'/') 
                            : 'https://via.placeholder.com/180x120?text=' . urlencode($p['nombre']) ?>" 
                            alt="<?= htmlspecialchars($p['nombre']) ?>">

                        <div class="nombre"><?= htmlspecialchars($p['nombre']) ?></div>

                        <!-- Botones de control -->
                        <div class="contador-box">
                            <button type="button" class="btn-restar"
                                onclick="restar(<?= $p['id_plato'] ?>, '<?= strtolower(str_replace(' ', '_', $categoria)) ?>')">–</button>
                            <div class="contador" id="count-<?= $p['id_plato'] ?>">0</div>
                            <button type="button" class="btn-sumar"
                                onclick="sumar(<?= $p['id_plato'] ?>, '<?= strtolower(str_replace(' ', '_', $categoria)) ?>')">+</button>
                        </div>

                        <input type="hidden" name="plato[<?= $p['id_plato'] ?>]" value="0" id="plato-<?= $p['id_plato'] ?>" data-categoria="<?= strtolower(str_replace(' ', '_', $categoria)) ?>">
                        <input type="hidden" name="orden_entrega[<?= $p['id_plato'] ?>]" value="0" id="orden-<?= $p['id_plato'] ?>">
                        <div class="orden-badge" id="orden-badge-<?= $p['id_plato'] ?>">-</div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endforeach; ?>

        <div class="botonera">
            <button type="submit" class="btn-enviar">✅ Enviar Orden</button>
        </div>
    </form>
</div>

<!-- ✅ Importa los archivos al final del body -->
<link rel="stylesheet" href="<?= BASE_URL ?>assets/css/mesero.css">
<script src="<?= BASE_URL ?>assets/js/mesero.js"></script>

</body>
</html>