<h2>Verificar código</h2>

<form method="POST">
    <label>Código recibido:</label>
    <input type="text" name="codigo" maxlength="6" required>

    <button type="submit">Verificar</button>

    <?php if (!empty($error)): ?>
        <p style="color:red"><?= $error ?></p>
    <?php endif; ?>
</form>
