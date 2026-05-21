<?php
// Conexión a la base de datos
$conn = new mysqli("localhost", "root", "", "sistemabiblioteca");
if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

$msgOk = "";
$msgErr = "";

// Obtener ID del usuario a editar
$id_usuario = $_GET['id'] ?? null;
$busqueda = $_GET['busqueda'] ?? '';
$tipo_busqueda = $_GET['tipo_busqueda'] ?? 'nombre';

// Obtener datos del usuario
$usuario = null;

if ($id_usuario) {
    // Consulta con LEFT JOIN para obtener datos de alumnado si existen
    $stmt = $conn->prepare("SELECT u.*, a.id_alumnado, a.clase, a.edad, a.estado_de_sancion 
                            FROM usuario u
                            LEFT JOIN alumnado a ON a.codigo_de_carnet = u.codigo_de_carnet
                            WHERE u.id_usuario = ?");
    $stmt->bind_param("i", $id_usuario);
    $stmt->execute();
    $result = $stmt->get_result();
    $usuario = $result->fetch_assoc();
    $stmt->close();
    
    if (!$usuario) {
        $msgErr = "Usuario no encontrado.";
    }
} else {
    $msgErr = "ID de usuario no proporcionado.";
}

// Procesar actualización al enviar el formulario
if ($_SERVER["REQUEST_METHOD"] == "POST" && $usuario) {
    $nombre = $_POST['nombre'] ?? '';
    $username = $_POST['username'] ?? '';
    $contrasenia = $_POST['contrasenia'] ?? '';
    $id_rol = $_POST['id_rol'] ?? $usuario['id_rol'];
    $clase = $_POST['clase'] ?? '';
    $edad = $_POST['edad'] ?? null;
    $estado_de_sancion = $_POST['estado_de_sancion'] ?? 'Sin sanción';
    $codigo_de_carnet = $_POST['codigo_de_carnet'] ?? '';
    
    // Validación básica
    if (empty($nombre) || empty($username)) {
        $msgErr = "El nombre y username son requeridos.";
    } else {
        try {
            // Actualizar usuario en la tabla principal
            if (!empty($contrasenia)) {
                // Si hay nueva contraseña, actualizarla
                $stmt_update = $conn->prepare("UPDATE usuario SET nombre = ?, username = ?, contrasenia = ?, id_rol = ?, codigo_de_carnet = ? WHERE id_usuario = ?");
                $stmt_update->bind_param("ssissi", $nombre, $username, $contrasenia, $id_rol, $codigo_de_carnet, $id_usuario);
            } else {
                // Si no, mantener la actual
                $stmt_update = $conn->prepare("UPDATE usuario SET nombre = ?, username = ?, id_rol = ?, codigo_de_carnet = ? WHERE id_usuario = ?");
                $stmt_update->bind_param("ssisi", $nombre, $username, $id_rol, $codigo_de_carnet, $id_usuario);
            }
            $stmt_update->execute();
            $stmt_update->close();
            
            // Si es alumno (rol 1) o miniprofesor (rol 2), actualizar tabla alumnado
            if ($id_rol == 1 || $id_rol == 2) {
                // Verificar si existe registro en alumnado
                if ($usuario['id_alumnado']) {
                    $stmt_al = $conn->prepare("UPDATE alumnado SET clase = ?, edad = ?, estado_de_sancion = ?, codigo_de_carnet = ? WHERE id_alumnado = ?");
                    $stmt_al->bind_param("sissi", $clase, $edad, $estado_de_sancion, $codigo_de_carnet, $usuario['id_alumnado']);
                    $stmt_al->execute();
                    $stmt_al->close();
                } else {
                    // Si no existe (ej. cambio de rol desde otro tipo), insertar
                    $stmt_ins = $conn->prepare("INSERT INTO alumnado (edad, clase, estado_de_sancion, codigo_de_carnet) VALUES (?, ?, ?, ?)");
                    $stmt_ins->bind_param("isss", $edad, $clase, $estado_de_sancion, $codigo_de_carnet);
                    $stmt_ins->execute();
                    $stmt_ins->close();
                }
            }
            
            $msgOk = "Usuario actualizado correctamente.";
            // Recargar datos después de 1 segundo
            header("Refresh:1; url=buscar_usuarios.php?busqueda=" . urlencode($busqueda) . "&tipo_busqueda=" . urlencode($tipo_busqueda));
            
        } catch (Exception $e) {
            $msgErr = "Error al actualizar: " . $e->getMessage();
        }
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Editar Usuario - Biblioteca</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/estilo.css">
    <style>
        /* Estilos CSS internos */
        .edit-container { max-width: 600px; margin: 20px auto; background: rgba(255,255,255,0.9); padding: 30px; border-radius: 10px; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; }
        .form-group input, .form-group select { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; }
        .btn-actualizar { background: #4CAF50; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; width: 100%; font-size: 16px; }
        .btn-volver { display: inline-block; margin-bottom: 20px; text-decoration: none; color: #333; }
    </style>
</head>
<body>
    <section class="header">
        <img src="../img/logoA.png">
        <section class="text">
            <h4 id="header1">CEIP Andrés Manjón</h4>
            <h4 id="header2">Biblioteca</h4>
        </section>
        <h2 id="title">Editar Usuario</h2>
    </section>

    <main>
        <section class="edit-container">
            <a href="buscar_usuarios.php?busqueda=<?= urlencode($busqueda) ?>&tipo_busqueda=<?= urlencode($tipo_busqueda) ?>" class="btn-volver">← Volver a la búsqueda</a>
            
            <?php if ($msgErr): ?>
                <p style="color: red;"><?= $msgErr ?></p>
            <?php endif; ?>
            
            <?php if ($msgOk): ?>
                <p style="color: green;"><?= $msgOk ?></p>
            <?php endif; ?>

            <?php if ($usuario): ?>
                <form method="POST">
                    <div class="form-group">
                        <label>Nombre Completo</label>
                        <input type="text" name="nombre" value="<?= htmlspecialchars($usuario['nombre']) ?>" required>
                    </div>

                    <div class="form-group">
                        <label>Nombre de Usuario (Login)</label>
                        <input type="text" name="username" value="<?= htmlspecialchars($usuario['username']) ?>" required>
                    </div>

                    <div class="form-group">
                        <label>Código de Carnet</label>
                        <input type="text" name="codigo_de_carnet" value="<?= htmlspecialchars($usuario['codigo_de_carnet']) ?>" required>
                    </div>

                    <div class="form-group">
                        <label>Contraseña (Dejar en blanco para no cambiar)</label>
                        <input type="password" name="contrasenia" placeholder="Nueva contraseña">
                    </div>

                    <div class="form-group">
                        <label>Rol</label>
                        <select name="id_rol">
                            <option value="1" <?= $usuario['id_rol'] == 1 ? 'selected' : '' ?>>Alumno</option>
                            <option value="2" <?= $usuario['id_rol'] == 2 ? 'selected' : '' ?>>Mini Profesor</option>
                            <option value="3" <?= $usuario['id_rol'] == 3 ? 'selected' : '' ?>>Profesor</option>
                            <option value="4" <?= $usuario['id_rol'] == 4 ? 'selected' : '' ?>>Administrador</option>
                        </select>
                    </div>

                    <?php if ($usuario['id_rol'] == 1 || $usuario['id_rol'] == 2): ?>
                        <div class="form-group">
                            <label>Clase</label>
                            <input type="text" name="clase" value="<?= htmlspecialchars($usuario['clase']) ?>">
                        </div>
                        <div class="form-group">
                            <label>Edad</label>
                            <input type="number" name="edad" value="<?= htmlspecialchars($usuario['edad']) ?>">
                        </div>
                        <div class="form-group">
                            <label>Estado de Sanción</label>
                            <select name="estado_de_sancion">
                                <option value="Sin sanción" <?= ($usuario['estado_de_sancion'] == 'Sin sanción') ? 'selected' : '' ?>>Sin sanción</option>
                                <option value="Activo" <?= ($usuario['estado_de_sancion'] == 'Activo') ? 'selected' : '' ?>>Activo</option>
                                <option value="No Activo" <?= ($usuario['estado_de_sancion'] == 'No Activo') ? 'selected' : '' ?>>No Activo</option>
                            </select>
                        </div>
                    <?php endif; ?>

                    <div class="btn-container">
                        <button type="submit" class="btn btn-actualizar">Actualizar Usuario</button>
                    </div>
                </form>
            <?php endif; ?>
        </section>
    </main>

    <footer class="estilo-footer" role="contentinfo">
        <div class="estilo-footer-container">
            <div class="estilo-footer-logo"><img src="../img/footerlogo.png" alt="Logo"></div>
            <div class="estilo-footer-column">
                <h4>SOBRE NOSOTROS</h4>
                <ul><li><a href="https://ceipandresmanjon.catedu.aragon.es/">Web del centro</a></li></ul>
            </div>
            <div class="estilo-footer-column">
                <h4>DIRECCIÓN</h4>
                <p>CEIP Andrés Manjón.<br>C/ Delicias, 90, 50017, Zaragoza</p>
            </div>
            <div class="estilo-footer-column">
                <h4>TELÉFONO</h4>
                <p>976 331 728</p>
                <h4>E-MAIL</h4>
                <p>cpamanzaragoza@educa.aragon.es</p>
            </div>
            <div class="estilo-footer-column">
                <h4>HORARIO</h4>
                <p>De lunes a viernes de 9 a 14 h</p>
            </div>
        </div>
    </footer>
</body>
</html>