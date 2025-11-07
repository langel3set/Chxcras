<?php include __DIR__ . '/../layout/sidebar.php'; ?>
<?php include __DIR__ . '/../layout/encabezado.php'; ?>

<?php
// $logs provisto por el controlador
?>

<div class="contenedor">
    <h2 class="titulo">🔎 Auditoría de cambios</h2>

    <div class="filtros">
        <form method="GET" class="form-filtros">
            <input type="text" name="entity" placeholder="Entidad (Usuarios)" value="<?= htmlspecialchars($_GET['entity'] ?? '') ?>">
            <input type="text" name="entity_id" placeholder="ID" value="<?= htmlspecialchars($_GET['entity_id'] ?? '') ?>">
            <input type="text" name="user_name" placeholder="Usuario que realizó" value="<?= htmlspecialchars($_GET['user_name'] ?? '') ?>">
            <select name="action">
                <option value="">-- Acción --</option>
                <option value="crear" <?= (($_GET['action'] ?? '') === 'crear') ? 'selected' : '' ?>>Crear</option>
                <option value="editar" <?= (($_GET['action'] ?? '') === 'editar') ? 'selected' : '' ?>>Editar</option>
                <option value="eliminar" <?= (($_GET['action'] ?? '') === 'eliminar') ? 'selected' : '' ?>>Eliminar</option>
            </select>
            <input type="date" name="date_from" value="<?= htmlspecialchars($_GET['date_from'] ?? '') ?>">
            <input type="date" name="date_to" value="<?= htmlspecialchars($_GET['date_to'] ?? '') ?>">
            <button type="submit" class="btn">Filtrar</button>
            <a href="<?= BASE_URL ?>audit/exportar?<?= http_build_query($_GET) ?>" class="btn" style="background:#4b73ff; margin-left:8px;">⬇️ Exportar CSV</a>
        </form>
    </div>

    <div class="tabla-contenedor">
        <table class="tabla-audit">
            <thead>
                <tr>
                    <th>Usuario</th>
                    <th>Acción</th>
                    <th>Entidad</th>
                    <th>Entity ID</th>
                    <th>Campo</th>
                    <th>Antiguo</th>
                    <th>Nuevo</th>
                    <th>Fecha</th>
                    <th>Hora</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($logs as $l): 
                    $dt = new DateTime($l['created_at']);
                ?>
                    <tr>
                        <td><?= htmlspecialchars($l['user_name'] ?? 'Sistema') ?></td>
                        <td><?= htmlspecialchars($l['action']) ?></td>
                        <td><?= htmlspecialchars($l['entity']) ?></td>
                        <td><?= htmlspecialchars($l['entity_id'] ?? '') ?></td>
                        <td><?= htmlspecialchars($l['field_name'] ?? '') ?></td>
                        <td><?= nl2br(htmlspecialchars($l['old_value'] ?? '')) ?></td>
                        <td><?= nl2br(htmlspecialchars($l['new_value'] ?? '')) ?></td>
                        <td><?= $dt->format('Y-m-d') ?></td>
                        <td><?= $dt->format('H:i:s') ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($logs)): ?>
                    <tr><td colspan="9" style="text-align:center; padding:20px;">No hay registros</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<style>
.contenedor { margin-left: 274px; padding: 40px 30px; font-family: 'Segoe UI', sans-serif; background:#f9fafb; min-height:100vh;}
.titulo { font-size:28px; color:#0d3d2e; margin-bottom:14px; text-align:left;}
.form-filtros { display:flex; gap:8px; flex-wrap:wrap; align-items:center; margin-bottom:12px;}
.form-filtros input, .form-filtros select { padding:8px 10px; border:1px solid #ccc; border-radius:6px;}
.btn { background:#17a2b8; color:#fff; padding:8px 12px; border-radius:6px; text-decoration:none; border:none; cursor:pointer;}
.tabla-audit { width:100%; border-collapse:collapse; }
.tabla-audit th { background:#0d3d2e; color:#fff; padding:12px; text-align:left; }
.tabla-audit td { padding:10px; border-bottom:1px solid #e6e9ee; vertical-align:top; font-size:14px; }
</style>
