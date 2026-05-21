<?php
// =======================================
// resultado.php
// Función: procesar la búsqueda de libros
// y mostrar los resultados encontrados
// =======================================

// Datos de conexión al servidor MySQL
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "sistemabiblioteca";

// Crear la conexión con la base de datos
$conn = new mysqli($servername, $username, $password, $dbname);

// Comprobar si la conexión falla
if ($conn->connect_error) die("Conexión fallida: " . $conn->connect_error);

// ---------------------------------------
// Obtener los parámetros enviados por URL
// mode: tipo de búsqueda (isbn o nombre)
// q: texto introducido por el usuario
// ---------------------------------------
$mode = $_GET['mode'] ?? 'nombre';
$q = $_GET['q'] ?? '';

// Escapar el texto para evitar inyección SQL
$q = $conn->real_escape_string($q);

// Crear la consulta SQL según el modo elegido
$sql = ($mode === 'isbn') 
    ? "SELECT * FROM Libro WHERE isbn LIKE '%$q%'" 
    : "SELECT * FROM Libro WHERE titulo LIKE '%$q%'";

// Ejecutar la consulta
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="ES">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Resultado de búsqueda - Andrés Manjón</title>

<!-- Hoja de estilos externa -->
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
  <!-- Cabecera de la página -->
  <section class="header">  
    <img src="../img/logoA.png" alt="Logo">
    <section class="text">
      <h4 id="header1">CEIP Andrés Manjón</h4>
      <h4 id="header2">Biblioteca</h4>
    </section>
    <h2 id="title">Resultados de búsqueda</h2>
  </section>

  <!-- Botón para ir a la lista completa de libros -->
  <button id="listBtn" class="head-btn">
    <img src="../img/lista.png" alt="Lista">
    <a href="../librolista/lista.php">
      <span id="listBtnTxt">Lista de libro</span>
    </a>
  </button>

  <!-- Botón para volver a la búsqueda -->
  <button><a href="nombre.php">Volver</a></button>

  <main class="resultado-main">

    <!-- Comprobar si la consulta tiene resultados -->
    <?php if ($result && $result->num_rows > 0): ?>
      <div class="result-grid">

        <!-- Recorrer cada libro obtenido -->
        <?php while($book = $result->fetch_assoc()): 

          // Ruta de la imagen del libro según su ID
          $imagen = "../img_libros/{$book['id_libro']}.jpg";

          // Si la imagen no existe, usar una imagen por defecto
          if (!file_exists($imagen)) $imagen = "../img_libros/default.jpg";

          // Crear enlace a la página de detalles manteniendo el origen
          $detalles_url = "../librolista/detalles.php?id={$book['id_libro']}&from=prestamo&mode=" . urlencode($mode) . "&q=" . urlencode($q);
        ?>

        <!-- Tarjeta individual del libro -->
        <div class="result-card">
          <a href="<?php echo $detalles_url; ?>">
            <img src="<?php echo $imagen; ?>" alt="<?php echo htmlspecialchars($book['titulo']); ?>">
            <?php echo htmlspecialchars($book['titulo']); ?>
          </a>

          <!-- Mostrar ISBN -->
          <div class="isbn">
            ISBN: <?php echo htmlspecialchars($book['isbn'] ?? 'N/A'); ?>
          </div>
        </div>

        <?php endwhile; ?>
      </div>

    <!-- Si no hay resultados -->
    <?php else: ?>
      <p style="padding:20px;">
        No se encontraron resultados para "<?php echo htmlspecialchars($q); ?>"
      </p>
    <?php endif; ?>

  </main>

  <!-- Pie de página -->
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

<?php
// Cerrar la conexión con la base de datos
$conn->close();
?>
