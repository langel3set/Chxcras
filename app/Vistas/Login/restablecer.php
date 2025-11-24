<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Restablecer contraseña</title>
</head>
<body>

<div class="login-container">
    <h2>Restablecer contraseña</h2>

    <?php if (!empty($error)): ?>
        <p style="color:red;"><?= $error ?></p>
    <?php endif; ?>

    <form action="<?= BASE_URL . 'login/restablecer' ?>" method="POST">
        <label for="pass1">Nueva contraseña</label>
        <input type="password" name="pass1" required>

        <label for="pass2">Repetir nueva contraseña</label>
        <input type="password" name="pass2" required>

        <button type="submit">Guardar nueva contraseña</button>
    </form>
</div>

</body>
</html>