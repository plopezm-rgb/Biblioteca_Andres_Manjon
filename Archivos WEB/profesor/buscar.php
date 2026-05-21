<?php
/* =========================
   CONEXIÓN BD
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
// LÓGICA DE BÚSQUEDA (IGUAL)
// =========================
$resultados = [];

if ($_SERVER["REQUEST_METHOD"] == "GET") {

    $titulo_buscado = $_GET['titulo'] ?? '';
    $codigo_buscado = $_GET['codigo_barra'] ?? '';

    $sql = "SELECT * FROM libro WHERE 1=1";
    $params = [];

    if (!empty($titulo_buscado)) {
        $sql .= " AND titulo LIKE :titulo";
        $params[':titulo'] = "%" . $titulo_buscado . "%";
    }

    if (!empty($codigo_buscado)) {
        $sql .= " AND codigo_de_barra = :codigo";
        $params[':codigo'] = $codigo_buscado;
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $resultados = $stmt->fetchAll();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Resultados de búsqueda - Biblioteca</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="../css/estilo.css">

<style>
/* --- Grid para resultados: filas de 5 libros --- */
.result-grid {
  display: grid;
  grid-template-columns: repeat(5, 1fr);
  gap: 20px;
  padding: 20px;
}

.result-card {
  background-color: #f9f9f9;
  border-radius: 12px;
  box-shadow: 0 4px 8px rgba(0,0,0,0.1);
  padding: 10px;
  text-align: center;
  transition: transform 0.2s, box-shadow 0.2s;
}

.result-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 8px 16px rgba(0,0,0,0.2);
}

.result-card img {
  width: 120px;
  height: 180px;
  object-fit: cover;
  border-radius: 6px;
  margin-bottom: 8px;
}

.result-card a {
  text-decoration: none;
  color: #333;
  font-weight: bold;
  display: block;
}

.result-card .isbn {
  font-size: 0.85rem;
  color: #555;
  margin-top: 4px;
}
</style>
</head>

<body>

<!-- HEADER -->
<section class="header">  
  <img src="img/logoA.png" alt="Logo">
  <section class="text">
    <h4 id="header1">CEIP Andrés Manjón</h4>
    <h4 id="header2">Biblioteca</h4>
  </section>
  <h2 id="title">Resultados de búsqueda</h2>
</section>

<button class="head-btn">
  <a href="Gestiondelibros.html">Volver al buscador</a>
</button>

<main class="resultado-main">

<?php if ($_SERVER["REQUEST_METHOD"] == "GET"): ?>

  <?php if (count($resultados) > 0): ?>
    <div class="result-grid">

      <?php foreach ($resultados as $libro): 
        $imagen = "../img_libros/{$libro['id_libro']}.jpg";
        if (!file_exists($imagen)) {
          $imagen = "img_libros/default.jpg";
        }
      ?>

      <div class="result-card">
        <img src="<?= $imagen ?>" alt="<?= htmlspecialchars($libro['titulo']) ?>">
       <h4>
        <a href="../librolista/detalles.php?
                id=<?= $libro['id_libro'] ?>&from=buscar&titulo=<?= urlencode($titulo_buscado) ?>&codigo=<?= urlencode($codigo_buscado) ?>" 
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

<!-- FOOTER -->
<footer class="estilo-footer">
  <section class="estilo-footer-container">
    <section class="estilo-footer-logo">
      <img src="img/footerlogo.png" alt="Logo" class="estilo-logo-img">
    </section>
    <section class="estilo-footer-column">
      <h3 class="estilo-footer-title">SOBRE NOSOTROS</h3>
      <ul class="estilo-footer-list">
        <li><a href="https://ceipandresmanjon.catedu.es/" class="estilo-footer-link">Web del centro</a></li>
      </ul>
    </section>
    <section class="estilo-footer-column">
      <h3 class="estilo-footer-title">DIRECCIÓN</h3>
      <p class="estilo-footer-text">C/ Delicias, 90, Zaragoza</p>
    </section>
  </section>
</footer>

</body>
</html>
