<?php
/* =========================
   CONEXIÓN A LA BASE DE DATOS
   ========================= */
$DB_HOST = "localhost";
$DB_NAME = "sistemabiblioteca";
$DB_USER = "root";
$DB_PASS = "";

try {
  $pdo = new PDO(
    "mysql:host=$DB_HOST;dbname=$DB_NAME;charset=utf8mb4",
    $DB_USER,
    $DB_PASS,
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
  );
} catch (Exception $e) {
  die("Error de conexión con la base de datos");
}

// =========================
// LÓGICA DE BÚSQUEDA
// =========================
$resultados = [];

if ($_SERVER["REQUEST_METHOD"] == "GET") {

    $titulo_buscado = $_GET['titulo'] ?? '';
    $codigo_buscado = $_GET['codigo_barra'] ?? '';

    $sql = "SELECT * FROM libro WHERE 1=1";
    $params = [];

    // Agregar condición de título si existe
    if (!empty($titulo_buscado)) {
        $sql .= " AND titulo LIKE :titulo";
        $params[':titulo'] = "%" . $titulo_buscado . "%";
    }

    // Agregar condición de código de barras si existe
    if (!empty($codigo_buscado)) {
        $sql .= " AND codigo_de_barra = :codigo";
        $params[':codigo'] = $codigo_buscado;
    }

    // Solo ejecutar si hay algún filtro
    if (!empty($params)) {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Resultados de Búsqueda</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="../css/estilo.css">
  <style>
    /* Estilos CSS internos para los resultados */
    .resultado-card {
      background: #fff;
      margin-bottom: 15px;
      padding: 15px;
      border-radius: 8px;
      box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    }
    .resultado-card h4 { margin-top: 0; }
    .resultado-card .info { color: #555; font-size: 0.9em; }
  </style>
</head>
<body>

<section class="header">
  <img src="img/logoA.png">
  <section class="text">
    <h4 id="header1">CEIP Andrés Manjón</h4>
    <h4 id="header2">Biblioteca</h4>
  </section>
  <h2 id="title">Resultados de Búsqueda</h2>
  <section class="buttons">
     <button><a href="Gestiondelibros.php">Volver</a></button>
  </section>
</section>

<main style="max-width:800px; margin:20px auto; padding:20px;">

<?php if ($_SERVER["REQUEST_METHOD"] == "GET"): ?>
  
  <?php if (count($resultados) > 0): ?>
    <div class="lista-resultados">
      <h3>Resultados encontrados:</h3>
      
      <?php foreach ($resultados as $libro): ?>
      <div class="resultado-card">
        <h4>
        <a href="detalles.php?id=<?= $libro['id_libro'] ?>&from=buscar&titulo=<?= urlencode($titulo_buscado) ?>" 
          style="text-decoration: none; color: inherit;">
          <?= htmlspecialchars($libro['titulo']) ?>
        </a>
      </h4>
        <div class="info">Autor: <?= htmlspecialchars($libro['autor']) ?></div>
        <div class="info">ISBN: <?= htmlspecialchars($libro['isbn']) ?></div>
        <div class="info">Estado: <?= htmlspecialchars($libro['estado_de_actividad']) ?></div>
      </div>

      <?php endforeach; ?>

    </div>
  <?php else: ?>
    <p style="padding:20px;">No se encontraron resultados.</p>
  <?php endif; ?>

<?php endif; ?>

</main>

<footer class="estilo-footer">
  <section class="estilo-footer-container">
    <section class="estilo-footer-logo">
      <img src="img/footerlogo.png" alt="Logo" class="estilo-logo-img">
    </section>
    <section class="estilo-footer-column">
      <h3 class="estilo-footer-title">SOBRE NOSOTROS</h3>
      <ul class="estilo-footer-list">
        <li><a href="https://ceipandresmanjon.catedu.aragon.es/" class="estilo-footer-link">Web del centro</a></li>
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
        <img src="img/gobierno.png" alt="Gobierno de Aragón" class="estilo-gobierno-logo">
      </a>
      <section class="estilo-social-icons">
        <a href="https://www.instagram.com/ceip_andresmanjon/" class="estilo-social-link">
          <img src="img/instagram.png" alt="Instagram" class="estilo-social-icon">
        </a>
      </section>
    </section>
  </section>
</footer>

</body>
</html>