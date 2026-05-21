<?php
session_start(); 
// Inicia la sesión para manejar datos del usuario (login, id_alumnado, id_rol, etc.)

// Conexión a la base de datos
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "sistemabiblioteca";

$conn = new mysqli($servername, $username, $password, $dbname);
// Verifica la conexión
if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

// Verificar libro
if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("No se especificó el libro."); // Termina si no hay ID de libro
}
$id = intval($_GET['id']); // Convierte a entero para mayor seguridad

$sql = "SELECT * FROM Libro WHERE id_libro = $id";
$result = $conn->query($sql);

if (!$result || $result->num_rows === 0) {
    die("Libro no encontrado."); // Termina si no hay resultados
}

$book = $result->fetch_assoc(); // Obtiene la información del libro
$imagen = "../img_libros/$id.jpg"; // Ruta de la imagen del libro
if (!file_exists($imagen)) {
    $imagen = "../img_libros/default.jpg"; // Imagen por defecto si no existe
}

// --- Obtener info de origen ---
$from = $_GET['from'] ?? 'lista'; // Página de origen
$mode = $_GET['mode'] ?? '';       // Modo de búsqueda (nombre o isbn)
$q = $_GET['q'] ?? '';             // Valor de búsqueda

// Propagar modo invitado
$is_guest = isset($_GET['guest']) && $_GET['guest'] == '1';
// Leer id_rol si viene en GET (ej. 0 para visitante)
$id_rol = isset($_GET['id_rol']) ? (int)$_GET['id_rol'] : ($_SESSION['id_rol'] ?? null);

// --- Información del usuario ---
$id_alumnado = $_SESSION['id_alumnado'] ?? null;

$conn->close(); // Cierra la conexión a la base de datos
?>
<!DOCTYPE html>
<!-- Documento HTML5 -->
<html lang="ES">
<head>
    <meta charset="UTF-8"> <!-- Codificación de caracteres -->
    <title><?php echo htmlspecialchars($book['titulo']); ?> - Andrés Manjón</title> <!-- Título dinámico según el libro -->
    <link rel="stylesheet" href="../css/estilo.css"> <!-- CSS principal -->
</head>
<body>
    <!-- ================= HEADER ================= -->
    <section class="header">
        <img src="../img/logoA.png"> <!-- Logo del centro -->
        <section class="text">
            <h4 id="header1">CEIP Andrés Manjón</h4>
            <h4 id="header2">Biblioteca</h4>
        </section>
        <h2 id="title"><?php echo htmlspecialchars($book['titulo']); ?></h2> <!-- Título del libro -->
        <section class="buttons">
            <img src="../img/help.png" class="head-foto"> <!-- Icono de ayuda -->
            <button id="helpBtn">I need help</button> <!-- Botón de ayuda -->
        </section>
    </section>

    <!-- ================= CONTENIDO PRINCIPAL ================= -->
    <main class="estilo-detalles-main">
        <section class="estilo-detalles-card">
            <!-- Portada del libro -->
            <section class="estilo-detalles-portada">
                <img src="<?php echo $imagen; ?>" alt="<?php echo htmlspecialchars($book['titulo']); ?>">
            </section>

            <!-- Formulario de detalles -->
            <section class="estilo-detalles-form">
                <div class="estilo-form-field">
                    <label>Título:</label>
                    <input type="text" value="<?php echo htmlspecialchars($book['titulo']); ?>" readonly>
                </div>

                <div class="estilo-form-field">
                    <label>Autor:</label>
                    <input type="text" value="<?php echo htmlspecialchars($book['autor']); ?>" readonly>
                </div>

                <div class="estilo-form-field">
                    <label>Ubicación:</label>
                    <input type="text" value="<?php echo htmlspecialchars($book['ubicacion_por_colores']); ?>" readonly>
                </div>

                <div class="estilo-form-field">
                    <label>Estado:</label>
                    <input type="text" value="<?php echo htmlspecialchars($book['estado_de_actividad'] ?? 'Disponible'); ?>" class="estilo-detalles-estado" readonly>
                </div>

                <div class="estilo-detalles-bottom">
                    <?php
                    $estado_libro = $book['estado_de_actividad'] ?? 'Disponible';
                    ?>

                    <?php if ($estado_libro === 'No disponible'): ?>
                        <!-- Libro no disponible, no mostrar botón de préstamo -->

                    <?php elseif ($id_alumnado === null): ?>
                        <!-- Usuario no logueado, no se permite préstamo -->

                    <?php else: ?>
                        <!-- Usuario logueado y libro disponible -->
                        <form method="POST" action="../prestamo/procesar_prestamo.php" style="display:inline;">
                            <input type="hidden" name="id_libro" value="<?= $book['id_libro'] ?>">
                            <input type="hidden" name="id_alumnado" value="<?= $id_alumnado ?>">
                            <button type="submit" class="estilo-detalles-prestamo">
                                <img src="../img/start.png" alt="" class="estilo-btn-icon">
                                Solicitar préstamo
                            </button>
                        </form>
                    <?php endif; ?>

                    <!-- Botón de regreso dinámico -->
                    <?php
                    if (($from ?? '') === 'buscar') {
                        $params = [];
                        if (!empty($_GET['titulo'])) $params['titulo'] = $_GET['titulo'];
                        if (!empty($_GET['codigo'])) $params['codigo'] = $_GET['codigo'];
                        $return_url = "../profesor/buscar.php";
                        if ($params) $return_url .= '?' . http_build_query($params);
                    } elseif (($from ?? '') === 'prestamo' && !empty($mode) && !empty($q)) {
                        $return_url = "../busqueda_libro/resultado.php?mode=" . urlencode($mode) . "&q=" . urlencode($q);

                    } else {
                        $return_url = "../librolista/lista.php";
                    }

                    // Si venimos en modo invitado, asegurar que el retorno conserve guest=1 y id_rol
                    if ($is_guest) {
                        $sep = (strpos($return_url, '?') === false) ? '?' : '&';
                        $return_url .= $sep . 'guest=1&id_rol=' . urlencode($id_rol);
                    }
                    ?>
                    <button class="estilo-detalles-prestamo">
                        <a href="<?= $return_url ?>">Volver</a> <!-- Enlace de regreso -->
                    </button>
                </div>

            </section>

            <!-- Ilustración decorativa -->
            <section class="estilo-detalles-illu">
                <img src="../img/duckinfo.png" alt="Goose with books">
            </section>
        </section>
    </main>

    <!-- ================= FOOTER ================= -->
    <footer class="estilo-footer">
        <section class="estilo-footer-container">
            <section class="estilo-footer-logo">
                <img src="../img/footerlogo.png" alt="Logo" class="estilo-logo-img">
            </section>

            <section class="estilo-footer-column">
                <h3 id="footerAbout" class="estilo-footer-title">SOBRE NOSOTROS</h3>
                <ul class="estilo-footer-list">
                    <li><a id="footerLink" href="https://ceipandresmanjon.catedu.es/" class="estilo-footer-link" >Web del centro</a></li>
                </ul>
            </section>

            <section class="estilo-footer-column">
                <h3 id="footerAddress" class="estilo-footer-title">DIRECCIÓN</h3>
                <p id="footerAddressText" class="estilo-footer-text">CEIP Andrés Manjón. <br>C/ Delicias, 90, 50017, Zaragoza</p>
            </section>

            <section class="estilo-footer-column">
                <h3 id="footerPhone" class="estilo-footer-title">TELÉFONO</h3>
                <p id="footerPhoneText" class="estilo-footer-text">976 331 728</p>
                
                <h3 id="footerEmail" class="estilo-footer-title estilo-mt">E-MAIL</h3>
                <p id="footerEmailText" class="estilo-footer-text">cpamanzaragoza@educa.aragon.es</p>
            </section>

            <section class="estilo-footer-column">
                <h3 id="footerHours" class="estilo-footer-title">HORARIO</h3>
                <p id="footerHoursText" class="estilo-footer-text">De lunes a viernes de 9 a 14 h</p>
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
