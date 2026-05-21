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

// =========================
// INSERT SI POST
// =========================
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($pdo)) {

  $nombreCompleto = trim($_POST["nombre_completo"] ?? "");
  $edad = trim($_POST["edad"] ?? "");
  $tipoCuenta = trim($_POST["tipo_cuenta"] ?? "alumno"); // alumno | admin | profesor | miniprofesor
  $clase = trim($_POST["clase"] ?? ""); // Curso solo alumnos
  $estado = trim($_POST["estado_de_sancion"] ?? "");
  $username = trim($_POST["username"] ?? "");
  $codigoCarnet = trim($_POST["codigo_de_carnet"] ?? "");
  $passwordPlano = trim($_POST["contrasenia"] ?? "");

  // 调试：检查密码是否被接收
  error_log("DEBUG - POST contrasenia: " . var_export($_POST["contrasenia"] ?? "NO_RECIBIDO", true));
  error_log("DEBUG - passwordPlano después trim: '" . $passwordPlano . "'");

  // Validaciones:
  if ($nombreCompleto === "" || $username === "" || $codigoCarnet === "" || $passwordPlano === "") {
    $msgErr = "Rellena: nombre, username, código de carnet y contraseña.";
  } elseif (($tipoCuenta === "alumno" || $tipoCuenta === "miniprofesor") && $clase === "") {
    $msgErr = "Rellena el curso para registrar un alumno o miniprofesor.";
  } else {

    // dividir nombre / apellidos
    $partes = preg_split('/\s+/', $nombreCompleto);
    $nombre = $partes[0] ?? $nombreCompleto;
    $apellidos = trim(substr($nombreCompleto, strlen($nombre)));
    if ($apellidos === "") $apellidos = "-";

    // edad -> NULL si vacía
    $edadVal = ($edad === "" ? null : (int)$edad);

    // estado -> si vacío, por defecto (solo alumno)
    if ($estado === "") $estado = "Sin sanción";

    // Contraseña - almacenar como texto plano (para compatibilidad)
    $passwordHash = $passwordPlano;

    // Roles (si no usas roles todavía, ponlos a null)
    $ID_ROL_ALUMNO = 1; // pon por ejemplo 2 si tienes rol alumno
    $ID_ROL_ADMIN  = 4; // 1
    $ID_ROL_PROFESOR = 3; // 3
    $ID_ROL_MINIPROF = 2; // 4

    try {

      if ($tipoCuenta === "alumno" || $tipoCuenta === "miniprofesor") {
        // Alumno o Miniprofesor: alumnado + usuario
        $pdo->beginTransaction();

        $sqlAl = "INSERT INTO alumnado (nombre, apellidos, clase, edad, codigo_de_carnet, estado_de_sancion)
                  VALUES (:nombre, :apellidos, :clase, :edad, :carnet, :estado)";
        $stmtAl = $pdo->prepare($sqlAl);
        $stmtAl->execute([
          ":nombre" => $nombre,
          ":apellidos" => $apellidos,
          ":clase" => $clase,
          ":edad" => $edadVal,
          ":carnet" => $codigoCarnet,
          ":estado" => $estado
        ]);

        $sqlUs = "INSERT INTO usuario (nombre, username, contrasenia, codigo_de_carnet, id_rol)
                  VALUES (:nombre, :username, :pass, :carnet, :id_rol)";
        $stmtUs = $pdo->prepare($sqlUs);
        $rol = ($tipoCuenta === "alumno" ? $ID_ROL_ALUMNO : $ID_ROL_MINIPROF);
        $stmtUs->execute([
          ":nombre" => $nombreCompleto,
          ":username" => $username,
          ":pass" => $passwordHash,
          ":carnet" => $codigoCarnet,
          ":id_rol" => $rol
        ]);

        $pdo->commit();
        $msgOk = ucfirst($tipoCuenta) . " registrado correctamente.";

      }

    } catch (PDOException $e) {
      if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
      }

      // 详细错误调试
      error_log("PDO Error - Code: " . $e->getCode() . " | Message: " . $e->getMessage());
      
      if ($e->getCode() === "23000") {
        // Verificar qué campo causa el conflicto
        $errorInfo = $e->errorInfo;
        if (strpos($errorInfo[2], 'username') !== false) {
          $msgErr = "Ese username ya existe.";
        } elseif (strpos($errorInfo[2], 'codigo_de_carnet') !== false) {
          $msgErr = "Ese código de carnet ya existe.";
        } else {
          $msgErr = "Ese código de carnet o username ya existe.";
        }
      } else {
        $msgErr = "Error al registrar: " . $e->getMessage();
      }
    }
  }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Registro De Usuario</title>
  <link rel="stylesheet" href="../css/estilo.css">
  <style>
    body { font-family: Arial, sans-serif; }
    .registro-card { max-width: 600px; margin: 40px auto; padding: 20px; background: #fff; border-radius: 10px; box-shadow:0 4px 8px rgba(0,0,0,0.1); }
    .registro-alert { padding:10px; margin-bottom:10px; border-radius:5px; }
    .ok { background-color: #c8f0c8; color:#2a7b2a; }
    .err { background-color: #f8d7da; color:#842029; }
    .field-row { margin-bottom: 15px; display:flex; align-items:center; }
    .field-ico { width:24px; margin-right:10px; }
    .field-select, input, textarea { flex:1; padding:8px; }
    .registro-btn { width:100%; padding:10px; background:lightblue; border:none; border-radius:5px; cursor:pointer; }
  </style>
</head>
<body>

<section class="header">
  <img src="../img/logoA.png">
  <section class="text">
      <h4 id="header1">CEIP Andrés Manjón</h4>
      <h4 id="header2">Biblioteca</h4>
  </section>
  <h2 id="title">Registro de Usuarios/Panel de profesor</h2>
</section>

<main class="registro-main">
  <section class="registro-card">
    <a href="indexprofesor.html" class="btn-volver">← Volver</a>
    <h1 class="registro-title">Registro De Usuarios</h1>

    <?php if ($msgOk): ?><div class="registro-alert ok"><?= htmlspecialchars($msgOk) ?></div><?php endif; ?>
    <?php if ($msgErr): ?><div class="registro-alert err"><?= htmlspecialchars($msgErr) ?></div><?php endif; ?>

    <form class="alumno-form" method="POST">

      <div class="field-row">
        <input type="text" name="nombre_completo" placeholder="Nombre y apellido de usuario" required>
      </div>

      <div class="field-row">
        <input type="text" name="username" placeholder="Username" required>
      </div>

      <div class="field-row">
        <input type="text" name="codigo_de_carnet" placeholder="Código de carnet" required>
      </div>

      <div class="field-row">
        <input type="password" name="contrasenia" placeholder="Contraseña" required>
      </div>

      <div class="field-row">
        <select name="tipo_cuenta" id="tipoCuentaSelect" class="field-select" required>
          <option value="alumno" selected>Alumno</option>
          <option value="miniprofesor">Miniprofesor</option>
        </select>
      </div>

      

      <!-- Campos solo alumno -->
      <div id="soloAlumno">
        <div class="field-row">
          <select name="clase" id="claseSelect" class="field-select" required>
            <option value="" disabled selected>Curso</option>
            <option value="1º">1º</option>
            <option value="2º">2º</option>
            <option value="3º">3º</option>
            <option value="4º">4º</option>
            <option value="5º">5º</option>
            <option value="6º">6º</option>
          </select>
        </div>

        <div class="field-row">
          <textarea name="estado_de_sancion" placeholder="Estado de sanción" rows="4"></textarea>
        </div>

        <div class="field-row">
          <select name="edad" id="edadSelect" class="field-select" required>
            <option value="" disabled selected>Edad</option>
            <?php for($i=3;$i<=12;$i++): ?>
              <option value="<?= $i ?>"><?= $i ?></option>
            <?php endfor; ?>
          </select>
        </div>
      </div>

      <button class="registro-btn center-btn" type="submit">Crear</button>
    </form>
  </section>
</main>

<script>
document.addEventListener('DOMContentLoaded', () => {
  const tipo = document.getElementById('tipoCuentaSelect');
  const soloAlumno = document.getElementById('soloAlumno');
  const edad = document.getElementById('edadSelect');
  const clase = document.getElementById('claseSelect');

  function actualizarCampos() {
    if (tipo.value === 'alumno' || tipo.value === 'miniprofesor') {
      soloAlumno.style.display = '';
      edad.required = true;
      clase.required = true;
      edad.disabled = false;
      clase.disabled = false;
    } else {
      soloAlumno.style.display = 'none';
      edad.required = false;
      clase.required = false;
      edad.disabled = true;
      clase.disabled = true;
    }
  }

  tipo.addEventListener('change', actualizarCampos);
  actualizarCampos();
});
</script>

</body>
<!-- FOOTER (IGUAL QUE EL RESTO) -->
<footer class="estilo-footer">
  <section class="estilo-footer-container">
    <section class="estilo-footer-logo">
      <img src="../img/footerlogo.png" alt="Logo" class="estilo-logo-img">
    </section>

    <section class="estilo-footer-column">
      <h3 class="estilo-footer-title">SOBRE NOSOTROS</h3>
      <ul class="estilo-footer-list">
        <li>
          <a href="https://ceipandresmanjon.catedu.es/" class="estilo-footer-link">
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
</html>
