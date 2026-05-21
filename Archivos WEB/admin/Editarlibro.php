<?php
session_start();

if (!isset($_SESSION['id_usuario'])) {
    header("Location: ../index.html");
    exit;
}

$conn = new mysqli("localhost", "root", "", "sistemabiblioteca");
if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}

// Comprobar ID
if (!isset($_GET['id'])) {
    header("Location: gestiondelibros.php");
    exit;
}

$id_libro = intval($_GET['id']);

// Capturamos la página de origen
$pagina_anterior = $_GET['from'] ?? ($_SERVER['HTTP_REFERER'] ?? 'gestiondelibros.php');

// =======================
// GUARDAR CAMBIOS
// =======================
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $titulo = $_POST['titulo'];
    $isbn   = $_POST['isbn'];
    $autor  = $_POST['autor'];
    $color  = $_POST['ubicacion_por_colores'];
    $estado = $_POST['estado_de_actividad'];

    $stmt = $conn->prepare("
        UPDATE libro 
        SET titulo = ?, isbn = ?, autor = ?, ubicacion_por_colores = ?, estado_de_actividad = ?
        WHERE id_libro = ?
    ");
    $stmt->bind_param("sssssi", $titulo, $isbn, $autor, $color, $estado, $id_libro);
    $stmt->execute();
    $stmt->close();

    // =======================
    // SUBIDA / CAMBIO DE PORTADA
    // =======================
    if (!empty($_FILES['portada']['name'])) {

        $uploadDir = __DIR__ . '/../img_libros/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        // Extensión del archivo
        $ext = strtolower(pathinfo($_FILES['portada']['name'], PATHINFO_EXTENSION));

        // Extensiones permitidas
        $permitidas = ['jpg', 'jpeg', 'png', 'webp'];
        if (!in_array($ext, $permitidas)) {
            die("Formato de imagen no permitido");
        }

        // Eliminar imagen anterior (cualquier extensión)
        foreach (glob($uploadDir . $id_libro . '.*') as $oldImg) {
            unlink($oldImg);
        }

        // Guardar nueva imagen (mismo nombre)
        $rutaFinal = $uploadDir . $id_libro . '.' . $ext;
        move_uploaded_file($_FILES['portada']['tmp_name'], $rutaFinal);
    }

    // Volver a la página anterior
    header("Location: " . $pagina_anterior);
    exit;
}

// =======================
// CARGAR DATOS ACTUALES
// =======================
$stmt = $conn->prepare("SELECT * FROM libro WHERE id_libro = ?");
$stmt->bind_param("i", $id_libro);
$stmt->execute();
$result = $stmt->get_result();
$libro = $result->fetch_assoc();
$stmt->close();

if (!$libro) {
    echo "Libro no encontrado";
    exit;
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Editar libro</title>
    <link rel="stylesheet" href="../css/estilo.css">
    <style>
       body {
             font-family: Arial, Helvetica, sans-serif;
        }

        .form-editar {
            max-width: 600px;
            margin: 40px auto;
            background: #fff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }

        .form-editar input,
        .form-editar select {
            width: 90%;
            padding: 10px;
            margin-bottom: 15px;
        }

        .form-editar button {
            padding: 10px;
            background: #007bff;
            color: #fff;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        .btn-volver {
            display: inline-block;
            margin-bottom: 15px;
            text-decoration: none;
            color: #007bff;
            font-weight: bold;
        }

        .btn-volver:hover {
            text-decoration: underline;
        }

        .preview-img {
            max-width: 150px;
            margin-top: 10px;
            border-radius: 6px;
            display: block;
        }
    </style>
</head>
<body>

<div class="form-editar">

    <a href="<?= htmlspecialchars($pagina_anterior) ?>" class="btn-volver">← Volver</a>

    <h2>Editar libro</h2>

    <!-- ⚠️ IMPORTANTE：multipart -->
    <form method="POST" enctype="multipart/form-data">

        <label>Título</label>
        <input type="text" name="titulo" required value="<?= htmlspecialchars($libro['titulo']) ?>">

        <label>ISBN</label>
        <input type="text" name="isbn" value="<?= htmlspecialchars($libro['isbn']) ?>">

        <label>Autor</label>
        <input type="text" name="autor" value="<?= htmlspecialchars($libro['autor']) ?>">

        <label>Ubicación por colores</label>
        <select name="ubicacion_por_colores" required>
            <option value="">-- Selecciona un color --</option>

            <option value="red"    <?= $libro['ubicacion_por_colores'] === 'red' ? 'selected' : '' ?>>Rojo</option>
            <option value="pink"    <?= $libro['ubicacion_por_colores'] === 'pink' ? 'selected' : '' ?>>Rosa</option>
            <option value="purple"  <?= $libro['ubicacion_por_colores'] === 'purple' ? 'selected' : '' ?>>Morado</option>
            <option value="yellow"<?= $libro['ubicacion_por_colores'] === 'yellow' ? 'selected' : '' ?>>Amarillo</option>
            <option value="brown"  <?= $libro['ubicacion_por_colores'] === 'brown' ? 'selected' : '' ?>>Marrón</option>
            <option value="white"  <?= $libro['ubicacion_por_colores'] === 'white' ? 'selected' : '' ?>>Blanco</option>
            <option value="light_blue"    <?= $libro['ubicacion_por_colores'] === 'light_blue' ? 'selected' : '' ?>>Azul</option>
            <option value="black"   <?= $libro['ubicacion_por_colores'] === 'black' ? 'selected' : '' ?>>Negro</option>
        </select>
        <br>

        <label>Estado</label>
        <br>
        <select name="estado_de_actividad">
            <option value="disponible" <?= $libro['estado_de_actividad'] === 'disponible' ? 'selected' : '' ?>>
                Disponible
            </option>
            <option value="No disponible" <?= $libro['estado_de_actividad'] === 'No disponible' ? 'selected' : '' ?>>
                No Disponible
            </option>
        </select>

        <label>Portada del libro</label>
        <input type="file" name="portada" accept="image/*">

        <?php
        $imgActual = glob("../img_libros/" . $id_libro . ".*");
        if ($imgActual):
        ?>
            <img src="<?= $imgActual[0] ?>" class="preview-img">
        <?php endif; ?>

        <br>
        <button type="submit">Guardar cambios</button>
    </form>
</div>

</body>
</html>