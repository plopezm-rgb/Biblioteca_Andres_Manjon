<form class="alumno-form" method="POST">
<?php
// =========================
// CONFIG BD
// =========================
$DB_HOST = "localhost";
$DB_NAME = "sistemabiblioteca";
$DB_USER = "root";
$DB_PASS = "";

$msgOk = "";
$msgErr = "";

try {
  $pdo = new PDO(
    "mysql:host=$DB_HOST;dbname=$DB_NAME;charset=utf8mb4",
    $DB_USER,
    $DB_PASS,
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
  );
} catch (Exception $e) {
  $msgErr = "Error de conexión con la base de datos";
}

// Obtener ID del alumno
$id_alumnado = $_GET['id'] ?? null;

// --- CAMBIO 1: Usamos $_REQUEST para mantener la búsqueda tras el POST ---
$busqueda = $_REQUEST['busqueda'] ?? '';
$tipo_busqueda = $_REQUEST['tipo_busqueda'] ?? 'nombre';

if (!$id_alumnado) {
  $msgErr = "ID de alumno no proporcionado.";
  $alumno = null;
} else {
  // Obtener datos del alumno
  $stmt = $pdo->prepare("SELECT * FROM alumnado WHERE id_alumnado = ?");
  $stmt->execute([$id_alumnado]);
  $alumno = $stmt->fetch(PDO::FETCH_ASSOC);
  if (!$alumno) {
    $msgErr = "Alumno no encontrado.";
  } else {
    // Obtener datos de usuario (rol y contraseña)
    $stmt_usuario = $pdo->prepare("SELECT id_rol, contrasenia FROM usuario WHERE codigo_de_carnet = ?");
    $stmt_usuario->execute([$alumno['codigo_de_carnet']]);
    $usuario_data = $stmt_usuario->fetch(PDO::FETCH_ASSOC);
    if ($usuario_data) {
      $alumno['id_rol'] = $usuario_data['id_rol'];
      $alumno['contrasenia'] = $usuario_data['contrasenia'];
    }
  }
}

// =========================
// UPDATE SI POST
// =========================
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($pdo) && $alumno) {

  $nombreCompleto = trim($_POST["nombre_completo"] ?? "");
  $edad = trim($_POST["edad"] ?? "");
  $clase = trim($_POST["clase"] ?? "");
  $codigoCarnet = trim($_POST["codigo_de_carnet"] ?? "");
  $estado = trim($_POST["estado_de_sancion"] ?? "");
  $id_rol = trim($_POST["id_rol"] ?? null);
  $passwordPlano = trim($_POST["contrasenia"] ?? "");

  // Validaciones:
  if ($nombreCompleto === "" || $codigoCarnet === "") {
    $msgErr = "Rellena: nombre y código de carnet.";
  } else {

    // dividir nombre / apellidos
    $partes = preg_split('/\s+/', $nombreCompleto);
    $nombre = $partes[0] ?? $nombreCompleto;
    $apellidos = trim(substr($nombreCompleto, strlen($nombre)));
    if ($apellidos === "") $apellidos = "-";

    // edad -> NULL si vacía
    $edadVal = ($edad === "" ? null : (int)$edad);

    // estado -> si vacío, por defecto
    if ($estado === "") $estado = "Sin sanción";

    try {
      $pdo->beginTransaction();
      
      $sql = "UPDATE alumnado SET nombre = :nombre, apellidos = :apellidos, clase = :clase, edad = :edad, codigo_de_carnet = :carnet, estado_de_sancion = :estado WHERE id_alumnado = :id";
      $stmt = $pdo->prepare($sql);
      $stmt->execute([
        ":nombre" => $nombre,
        ":apellidos" => $apellidos,
        ":clase" => $clase,
        ":edad" => $edadVal,
        ":carnet" => $codigoCarnet,
        ":estado" => $estado,
        ":id" => $id_alumnado
      ]);

      // Actualizar usuario si existe
      if ($id_rol !== null) {
        $sql_usuario = "UPDATE usuario SET id_rol = :id_rol";
        $params = [":id_rol" => $id_rol];
        
        if ($passwordPlano !== "") {
          $sql_usuario .= ", contrasenia = :contrasenia";
          $params[":contrasenia"] = $passwordPlano;
        }
        
        $sql_usuario .= " WHERE codigo_de_carnet = :carnet";
        $params[":carnet"] = $codigoCarnet;
        
        $stmt_usuario = $pdo->prepare($sql_usuario);
        $stmt_usuario->execute($params);
      }
      
      $pdo->commit();
      $msgOk = "Alumno actualizado correctamente.";
      
      // Recargar datos
      $stmt = $pdo->prepare("SELECT * FROM alumnado WHERE id_alumnado = ?");
      $stmt->execute([$id_alumnado]);
      $alumno = $stmt->fetch(PDO::FETCH_ASSOC);
      if ($alumno) {
        $stmt_usuario = $pdo->prepare("SELECT id_rol, contrasenia FROM usuario WHERE codigo_de_carnet = ?");
        $stmt_usuario->execute([$alumno['codigo_de_carnet']]);
        $usuario_data = $stmt_usuario->fetch(PDO::FETCH_ASSOC);
        if ($usuario_data) {
          $alumno['id_rol'] = $usuario_data['id_rol'];
          $alumno['contrasenia'] = $usuario_data['contrasenia'];
        }
      }

    } catch (Exception $e) {
      $pdo->rollBack();
      $msgErr = "Error al actualizar: " . $e->getMessage();
    }
  }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Editar Alumno - Biblioteca</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/estilo.css">
    <style>
        .registro-main {
            padding: 20px;
        }
        .registro-card {
            max-width: 600px;
            margin: 0 auto;
            background-color: rgba(255, 255, 255, 0.9);
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .registro-title {
            text-align: center;
            margin-bottom: 20px;
        }
        .registro-alert {
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 5px;
        }
        .registro-alert.ok {
            background-color: #d4edda;
            color: #155724;
        }
        .registro-alert.err {
            background-color: #f8d7da;
            color: #721c24;
        }
        .alumno-form {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        .form-group {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }
        .form-label {
            font-size: 12px;
            color: #333;
            font-weight: 600;
            margin-left: 30px;
        }
        .field-row {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .field-row.two-cols {
            justify-content: space-between;
        }
        .field-ico {
            width: 20px;
            height: 20px;
            flex-shrink: 0;
        }
        .field-select, input[type="text"], textarea {
            flex: 1;
            padding: 12px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 14px;
            min-height: 40px;
        }
        .registro-btn {
            padding: 10px;
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        .center-btn {
            align-self: center;
            width: 100px;
        }
        .btn-volver {
            display: inline-block;
            margin-bottom: 20px;
            color: #007bff;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <section class="header">
        <img src="../img/logoA.png">
        <section class="text">
            <h4>CEIP Andrés Manjón</h4>
            <h4>Biblioteca</h4>
        </section>
        <h2 style="margin-right: 15%;">Editar Alumno</h2>
    </section>

    <main class="registro-main">
        <section class="registro-card">
            <a href="buscar_alumno.php?busqueda=<?php echo urlencode($busqueda); ?>&tipo_busqueda=<?php echo urlencode($tipo_busqueda); ?>" class="btn-volver">← Volver</a>
            <h1 class="registro-title">Editar Alumno</h1>

            <?php if ($msgOk): ?>
                <div class="registro-alert ok"><?= htmlspecialchars($msgOk) ?></div>
            <?php endif; ?>

            <?php if ($msgErr): ?>
                <div class="registro-alert err"><?= htmlspecialchars($msgErr) ?></div>
            <?php endif; ?>

            <?php if ($alumno): ?>
                <form class="alumno-form" method="POST">
    <input type="hidden" name="busqueda" value="<?php echo htmlspecialchars($busqueda); ?>">
    <input type="hidden" name="tipo_busqueda" value="<?php echo htmlspecialchars($tipo_busqueda); ?>">
                    
                    <input type="hidden" name="busqueda" value="<?php echo htmlspecialchars($busqueda); ?>">
                    <input type="hidden" name="tipo_busqueda" value="<?php echo htmlspecialchars($tipo_busqueda); ?>">

                    <div class="form-group">
                        <label class="form-label">Nombre y Apellido</label>
                        <div class="field-row">

                            <input type="text" name="nombre_completo" placeholder="Nombre y apellido de alumno" value="<?php echo htmlspecialchars($alumno['nombre'] . ' ' . $alumno['apellidos']); ?>" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Edad</label>
                        <div class="field-row">

                            <select name="edad" class="field-select" required>
                                <option value="" disabled>Selecciona edad</option>
                                <?php for ($i = 3; $i <= 12; $i++): ?>
                                    <option value="<?php echo $i; ?>" <?php echo ($alumno['edad'] == $i) ? 'selected' : ''; ?>><?php echo $i; ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Curso</label>
                        <div class="field-row">
                            <select name="clase" class="field-select" required>
                                <option value="" disabled>Selecciona curso</option>
                                <option value="1º" <?php echo ($alumno['clase'] == '1º') ? 'selected' : ''; ?>>1º</option>
                                <option value="2º" <?php echo ($alumno['clase'] == '2º') ? 'selected' : ''; ?>>2º</option>
                                <option value="3º" <?php echo ($alumno['clase'] == '3º') ? 'selected' : ''; ?>>3º</option>
                                <option value="4º" <?php echo ($alumno['clase'] == '4º') ? 'selected' : ''; ?>>4º</option>
                                <option value="5º" <?php echo ($alumno['clase'] == '5º') ? 'selected' : ''; ?>>5º</option>
                                <option value="6º" <?php echo ($alumno['clase'] == '6º') ? 'selected' : ''; ?>>6º</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Código de Carnet</label>
                        <div class="field-row">
                            <input type="text" name="codigo_de_carnet" placeholder="Código de carnet" value="<?php echo htmlspecialchars($alumno['codigo_de_carnet']); ?>" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Estado</label>
                        <div class="field-row">
                            <select name="estado_de_sancion" class="field-select" required>
                                <option value="<?php echo htmlspecialchars($alumno['estado_de_sancion']);?>" selected><?php echo htmlspecialchars($alumno['estado_de_sancion']);?></option>
                                <option value="Activo">Activo</option>
                                <option value="No Activo">No Activo</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Rol</label>
                        <div class="field-row">
                            <select name="id_rol" class="field-select">
                                <option value="">Sin rol</option>
                                <option value="1" <?php echo (isset($alumno['id_rol']) && $alumno['id_rol'] == 1) ? 'selected' : ''; ?>>Alumno</option>
                                <option value="2" <?php echo (isset($alumno['id_rol']) && $alumno['id_rol'] == 2) ? 'selected' : ''; ?>>Mini Profesor</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Contraseña (dejar vacío para no cambiar)</label>
                        <div class="field-row">
                            <input type="password" name="contrasenia" placeholder="Nueva contraseña">
                        </div>
                    </div>

                    <button class="registro-btn center-btn" type="submit">Actualizar</button>
                </form>
            <?php endif; ?>
        </section>
    </main>

    <footer class="estilo-footer">
        <section class="estilo-footer-container">
            <section class="estilo-footer-logo">
                <img src="../img/footerlogo.png" alt="Logo" class="estilo-logo-img">
            </section>
            <section class="estilo-footer-column">
                <h3 class="estilo-footer-title">SOBRE NOSOTROS</h3>
                <ul class="estilo-footer-list">
                    <li>
                        <a href="https://ceipandresmanjon.catedu.aragon.es/" class="estilo-footer-link">
                            Web del centro
                        </a>
                    </li>
                </ul>
            </section>
            <section class="estilo-footer-column">
                <h3 class="estilo-footer-title">DIRECCIÓN</h3>
                <p class="estilo-footer-text">
                    CEIP Andrés Manjón.<br>
                    C/ Delicias, 90, 50017, Zaragoza
                </p>
            </section>
            <section class="estilo-footer-column">
                <h3 class="estilo-footer-title">TELÉFONO</h3>
                <p class="estilo-footer-text">976 331 728</p>
                <h3 class="estilo-footer-title estilo-mt">E-MAIL</h3>
                <p class="estilo-footer-text">cpamanzaragoza@educa.aragon.es</p>
            </section>
            <section class="estilo-footer-column">
                <h3 class="estilo-footer-title">HORARIO</h3>
                <p class="estilo-footer-text">De lunes a viernes de 9 a 14 h</p>
            </section>
            <section class="estilo-footer-right">
                <a href="https://educa.aragon.es/">
                    <img src="../img/gobierno.png" alt="Gobierno de Aragón" class="estilo-gobierno-logo">
                </a>
                <section class="estilo-social-icons">
                    <a href="https://www.instagram.com/ceip_andresmanjon/" class="estilo-social-link">
                        <img src="../img/instagram.png" alt="Instagram" class="estilo-social-icon">
                    </a>
                </section>
            </section>
        </section>
    </footer>
</body>
</html> 