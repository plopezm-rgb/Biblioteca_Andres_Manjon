<?php
session_start(); // Iniciar sesión para acceder a datos de usuario y préstamo

// -------------------------
// Conexión a la base de datos
// -------------------------
$conn = new mysqli("localhost", "root", "", "sistemabiblioteca");
if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

// -------------------------
// Obtener último libro prestado desde la sesión
// -------------------------
$id_libro = $_SESSION['ultimo_libro'] ?? null;

if (!$id_libro) {
    die("No hay información del último préstamo.");
}

// Obtener id_alumnado del usuario logueado
$id_alumnado = $_SESSION['id_alumnado'] ?? null;

// -------------------------
// Obtener información del libro
// -------------------------
$stmt_book = $conn->prepare("SELECT titulo FROM Libro WHERE id_libro = ?");
$stmt_book->bind_param("i", $id_libro);
$stmt_book->execute();
$stmt_book->bind_result($titulo);
$stmt_book->fetch();
$stmt_book->close();

// Determinar la imagen del libro
$imagen = "../img_libros/$id_libro.jpg";
if (!file_exists($imagen)) {
    $imagen = "../img_libros/default.jpg"; // Imagen por defecto si no existe
}

// -------------------------
// Obtener último préstamo del usuario para este libro
// -------------------------
$stmt_prestamo = $conn->prepare("
    SELECT 
        DATE(fecha_de_salida) AS fecha_de_salida,
        DATE(fecha_limite)    AS fecha_limite
    FROM Prestamo 
    WHERE id_libro = ? AND id_alumnado = ? 
    ORDER BY id_prestamo DESC LIMIT 1
");
$stmt_prestamo->bind_param("ii", $id_libro, $id_alumnado);
$stmt_prestamo->execute();
$stmt_prestamo->bind_result($fecha_salida, $fecha_limite);
$stmt_prestamo->fetch();
$stmt_prestamo->close();

// Cerrar conexión
$conn->close();
?>
<!DOCTYPE html>
<html lang="ES">
<head>
    <meta charset="UTF-8">
    <title>Préstamo Libro - Andrés Manjón</title>
    <link rel="stylesheet" href="../css/estilo.css">
</head>
<body>
    <!-- Header -->
    <section class="header">
        <img src="../img/logoA.png">
        <section class="text">
            <h4 id="header1">CEIP Andrés Manjón</h4>
            <h4 id="header2">Biblioteca</h4>
        </section>
        <!-- Título dinámico: nombre del libro -->
        <h2 id="title"><?php echo htmlspecialchars($titulo); ?></h2>
        <section class="buttons">
            <img src="../img/presta.png" class="head-foto">
            <button><a href="../librolista/lista.php" class="presta-text">Lista de libro</a></button>
            <img src="../img/help.png" class="head-foto">
            <button id="helpBtn"><a href="https://campusdigitalfp.com/contacto/">I need help</a></button>
            <img src="../img/idioma.png" class="head-foto">
            <select id="langSelect" class="form-select">
                <option value="ES">Español</option>
                <option value="EN">English</option>
                <option value="FR">Français</option>
                <option value="AL">عربي</option>
                <option value="CN">中文(简体中文)</option>
            </select>
        </section>
    </section>

    <main class="prestamo-main">
        <!-- Botón historial -->
        <a href="../prestamo/historial.php" class="historia-fab" aria-label="Historia">
            <img src="../img/historia.png" alt="Historia">
            <span id="historiaTxt">Historial</span>
        </a>

        <section class="prestamo-card">
            <div class="prestamo-card-header">
                <img src="../img/presta.png" alt="Book" class="prestamo-book-icon">
                <h2 class="prestamo-title">Préstamo libro</h2>
            </div>

            <div class="prestamo-content">
                <!-- Mostrar imagen del libro -->
                <div class="prestamo-book-container">
                    <div class="prestamo-book-frame">
                        <!-- Esquinas decorativas -->
                        <div class="prestamo-corner prestamo-top-left"></div>
                        <div class="prestamo-corner prestamo-top-right"></div>
                        <div class="prestamo-corner prestamo-bottom-left"></div>
                        <div class="prestamo-corner prestamo-bottom-right"></div>
                        <!-- Portada y título -->
                        <div class="prestamo-book-cover">
                            <img src="<?php echo $imagen; ?>" 
                                alt="<?php echo htmlspecialchars($titulo); ?>" 
                                style="width:100%; height:100%; object-fit:cover;">

                            <p class="prestamo-book-title">
                                <?php echo htmlspecialchars($titulo); ?>
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Mostrar fechas del préstamo -->
                <div class="prestamo-dates-container">
                    <div class="prestamo-date-wrapper">
                        <label>Fecha de salida:</label>
                        <input type="date" value="<?php echo $fecha_salida; ?>" class="prestamo-date-input" readonly>
                    </div>
                    
                    <div class="prestamo-arrow">
                        <img src="../img/flecha.png" alt="Arrow" class="prestamo-arrow-icon">
                    </div>
                    
                    <div class="prestamo-date-wrapper">
                        <label>Fecha límite:</label>
                        <input type="date" value="<?php echo $fecha_limite; ?>" class="prestamo-date-input" readonly>
                    </div>
                </div>

                <!-- Ilustración decorativa -->
                <div class="prestamo-illustration">
                    <img src="../img/reading.png" alt="Goose reading" class="prestamo-goose">
                </div>
            </div>

            <!-- Botón volver -->
            <div class="prestamo-button-container">
                <button type="button" class="prestamo-volver-btn" onclick="window.history.back();">Volver</button>
            </div>
        </section>
    </main>

    <!-- Footer (igual que el anterior, sin cambios) -->
    <footer class="estilo-footer">
        <section class="estilo-footer-container">
            <!-- ... el footer queda igual ... -->
        </section>
    </footer>
</body>
</html>
