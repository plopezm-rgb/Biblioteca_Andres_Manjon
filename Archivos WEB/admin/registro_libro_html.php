<?php
/* =========================
   CONEXIÓN BD
   ========================= */
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

/* =========================
   INSERT SI POST
   ========================= */
if ($_SERVER["REQUEST_METHOD"] === "POST" && $pdo) {

  // ===== Obtener datos del formulario =====
  $titulo = trim($_POST["titulo"] ?? "");
  $autor  = trim($_POST["autor"] ?? "");
  $isbn   = trim($_POST["isbn"] ?? "");
  $color  = trim($_POST["ubicacion_por_colores"] ?? "");

  if ($titulo === "" || $autor === "" || $isbn === "" || $color === "") {
    $msgErr = "Rellena todos los campos.";
  } else {
    try {
      // ===== Insertar libro en la base de datos =====
      $sql = "
        INSERT INTO libro
          (titulo, codigo_de_barra, autor, isbn, ubicacion_por_colores, estado_de_actividad)
        VALUES
          (:titulo, 0, :autor, :isbn, :color, 'Disponible')
      ";
      $stmt = $pdo->prepare($sql);
      $stmt->execute([
        ":titulo" => $titulo,
        ":autor"  => $autor,
        ":isbn"   => $isbn,
        ":color"  => $color
      ]);

      // ===== Obtener id_libro del libro insertado =====
      $id_libro = $pdo->lastInsertId();

      // ===== Subida de la portada =====
      if (!empty($_FILES['portada']['name'])) {
          $uploadDir = __DIR__ . '/../img_libros/'; // Carpeta Reto3/img_libros/
          if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

          $ext = pathinfo($_FILES['portada']['name'], PATHINFO_EXTENSION);
          $targetPath = $uploadDir . $id_libro . '.' . $ext;

          if (move_uploaded_file($_FILES['portada']['tmp_name'], $targetPath)) {
              $msgOk = "Libro y portada registrados correctamente.";
          } else {
              $msgErr = "Libro registrado pero error al subir la imagen.";
          }
      } else {
          $msgOk = "Libro registrado correctamente.";
      }

      // ===== Limpiar variables para limpiar el formulario =====
      $titulo = $autor = $isbn = $color = "";

    } catch (PDOException $e) {
      if ($e->getCode() === "23000") {
        $msgErr = "El ISBN ya existe.";
      } else {
        $msgErr = "Error al registrar el libro.";
      }
    }
  }
}
$color = $color ?? '';

?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Registro de Libro</title>
  <link rel="stylesheet" href="../css/estilo.css">
</head>
<body>

<!-- HEADER -->
<section class="header">
    <img src="../img/logoA.png">
    <section class="text">
        <h4 id="header1">CEIP Andrés Manjón</h4>
        <h4 id="header2">Biblioteca</h4>
    </section>
    <h2 id="title">Préstamo de Libros</h2>ss
</section>

<main class="registro-main">
  <section class="registro-card">

    <h1 class="registro-title">Registro De Libro/Panel Admin</h1>

    <?php if ($msgOk): ?>
      <div class="registro-alert ok"><?= htmlspecialchars($msgOk) ?></div>
    <?php endif; ?>

    <?php if ($msgErr): ?>
      <div class="registro-alert err"><?= htmlspecialchars($msgErr) ?></div>
    <?php endif; ?>

    <!-- FORMULARIO DE REGISTRO -->
     <a href="indexadmin.html" class="btn-volver">← Volver</a>
    <form class="registro-form" method="POST" enctype="multipart/form-data">
      <section class="registro-left">
         <label class="portada-box">
            <input type="file" name="portada" accept="image/*" class="portada-input">
            <div class="portada-inner">
              <p class="portada-text">Subir portada</p>
              <span class="portada-plus">+</span>
            </div>
            
        </label>
      </section>

      <section class="registro-right">
        <div class="field-row">
          <img src="../img/isbn.png" class="field-ico">
          <input type="text" name="isbn" placeholder="ISBN del libro" value="<?= htmlspecialchars($isbn ?? '') ?>" required>

          <select name="ubicacion_por_colores" class="field-select" required>
            <option value="" disabled <?= empty($color) ? 'selected' : '' ?>>Colores</option>
            <option value="red" <?= ($color=='red')?'selected':'' ?>>Rojo</option>
            <option value="pink" <?= ($color=='pink')?'selected':'' ?>>Rosa</option>
            <option value="purple" <?= ($color=='purple')?'selected':'' ?>>Morado</option>
            <option value="yellow" <?= ($color=='yellow')?'selected':'' ?>>Amarillo</option>
            <option value="brown" <?= ($color=='brown')?'selected':'' ?>>Marrón</option>
            <option value="white" <?= ($color=='white')?'selected':'' ?>>Blanco</option>
            <option value="black" <?= ($color=='black')?'selected':'' ?>>Negro</option>
            <option value="green" <?= ($color=='green')?'selected':'' ?>>Verde</option>
            <option value="orange" <?= ($color=='orange')?'selected':'' ?>>Naranja</option>
            <option value="light_blue" <?= ($color=='light_blue')?'selected':'' ?>>Azul claro</option>
          </select>
        </div>

        <div class="field-row two-cols">
          <img src="../img/titulo.png" class="field-ico">
          <input type="text" name="titulo" placeholder="Título" value="<?= htmlspecialchars($titulo ?? '') ?>" required>
        </div>

        <div class="field-row two-cols">
          <img src="../img/autor.png" class="field-ico">
          <input type="text" name="autor" placeholder="Autor" value="<?= htmlspecialchars($autor ?? '') ?>" required>
        </div>

        <div class="botones-form">
          <button type="submit" class="registro-btn crear-btn">Crear</button>
          <button type="reset" class="registro-btn reset-btn">Borrar</button>
        </div>
      </section>
    </form>

  </section>
</main>

<script>
// ===== Vista previa de imagen =====
const input = document.querySelector('.portada-input');
const preview = document.getElementById('previewImg');

input.addEventListener('change', () => {
  const file = input.files[0];
  if (!file) return;

  const url = URL.createObjectURL(file);
  preview.src = url;
  preview.style.display = 'block';
});
</script>

<footer class="estilo-footer">
    <section class="estilo-footer-container">
      <section class="estilo-footer-logo">
        <img src="../img/footerlogo.png" alt="Logo" class="estilo-logo-img">
      </section>

      <section class="estilo-footer-column">
        <h3 class="estilo-footer-title">SOBRE NOSOTROS</h3>
        <ul class="estilo-footer-list">
          <li><a href="https://ceipandresmanjon.catedu.es/" class="estilo-footer-link">Web del centro</a></li>
        </ul>
      </section>

      <section class="estilo-footer-column">
        <h3 class="estilo-footer-title">DIRECCIÓN</h3>
        <p class="estilo-footer-text">CEIP Andrés Manjón. <br>C/ Delicias, 90, 50017, Zaragoza</p>
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