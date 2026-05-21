<?php
session_start();

// Conexión BD
$conn = new mysqli("localhost", "root", "", "sistemabiblioteca");
if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

// Datos necesarios
$id_libro    = $_POST['id_libro'] ?? null;
$id_alumnado = $_SESSION['id_alumnado'] ?? null;
$id_usuario  = $_SESSION['id_usuario'] ?? null;

// Validación mínima 
if (!$id_libro || !$id_alumnado || !$id_usuario) {
    die("Faltan datos para registrar el préstamo.");
}

// Verificar límite de libros prestados (máximo 2 para roles 1 y 2)
$id_rol = $_SESSION['id_rol'] ?? null;
if ($id_rol === 1 || $id_rol === 2) {
    $stmt_count = $conn->prepare(
        "SELECT COUNT(*) AS total FROM Prestamo 
         WHERE id_alumnado = ? AND estado_del_prestamo = 'Prestado'"
    );
    $stmt_count->bind_param("i", $id_alumnado);
    $stmt_count->execute();
    $stmt_count->bind_result($prestamos_activos);
    $stmt_count->fetch();
    $stmt_count->close();
    
    if ($prestamos_activos >= 2) {
        $_SESSION['error'] = "Ya tienes el máximo de 2 libros en préstamo. Devuelve uno para solicitar otro.";
        header("Location: ../librolista/lista.php");
        exit();
    }
}

// Verificar si el libro está disponible
$stmt_check = $conn->prepare("SELECT estado_de_actividad FROM Libro WHERE id_libro = ?");
$stmt_check->bind_param("i", $id_libro);
$stmt_check->execute();
$stmt_check->bind_result($estado_libro);
$stmt_check->fetch();
$stmt_check->close();

if ($estado_libro === 'No disponible') {
    die("El libro ya no está disponible para préstamo.");
}

// Calcular fecha de salida y fecha límite (15 días después)
$fecha_salida = date("Y-m-d"); // hoy
$fecha_limite = date("Y-m-d", strtotime("+15 days"));

// Insertar préstamo usando fecha_limite
$stmt = $conn->prepare("
    INSERT INTO Prestamo 
    (id_alumnado, id_libro, id_usuario, fecha_de_salida, fecha_limite, estado_del_prestamo)
    VALUES (?, ?, ?, ?, ?, 'Prestado')
");
$stmt->bind_param("iiiss", $id_alumnado, $id_libro, $id_usuario, $fecha_salida, $fecha_limite);

if ($stmt->execute()) {

    $_SESSION['ultimo_libro'] = $id_libro;


    $update = $conn->prepare(
        "UPDATE Libro SET estado_de_actividad = 'No disponible' WHERE id_libro = ?"
    );
    $update->bind_param("i", $id_libro);

    if (!$update->execute()) {
        die("Error al actualizar el estado del libro: " . $update->error);
    }

    $update->close();

    header("Location: ../librolista/tiempo.php");
    exit();
}


$stmt->close();
$conn->close();
?>
