<?php
session_start();

$conn = new mysqli("localhost", "root", "", "sistemabiblioteca");
if ($conn->connect_error) {
    $_SESSION['error'] = "Error de conexión con la base de datos";
    header("Location: index.php");
    exit();
}

$usuario_username = $_POST['username'] ?? '';
$usuario_pass     = $_POST['contrasenia'] ?? '';

if (!$usuario_username || !$usuario_pass) {
    $_SESSION['error'] = "Complete todos los campos";
    header("Location: index.php");
    exit();
}

/* Identificar usuario - */
$stmt = $conn->prepare(
    "SELECT id_usuario, nombre, id_rol, contrasenia FROM usuario WHERE username = ?"
);
$stmt->bind_param("s", $usuario_username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['error'] = "Usuario o contraseña incorrectos";
    header("Location: index.php");
    exit();
}

$user = $result->fetch_assoc();
$stored_hash = $user['contrasenia'] ?? '';

// Verificar contraseña: preferir password_verify() para hashes; fallback a comparación directa para compatibilidad
$valid = false;
if ($stored_hash && password_verify($usuario_pass, $stored_hash)) {
    $valid = true;
} elseif ($usuario_pass === $stored_hash) {
    // Compatibilidad con contraseñas almacenadas en texto plano
    $valid = true;
}

if (!$valid) {
    $_SESSION['error'] = "Usuario o contraseña incorrectos";
    header("Location: index.php");
    exit();
}
$id_usuario = (int)$user['id_usuario'];  // ID de la tabla Usuario
$nombre_completo = trim($user['nombre']); // Nombre completo: "nombre apellido"
$id_rol = (int)$user['id_rol'];

/* ===== SEGÚN ROL ===== */
if ($id_rol === 1) {
    // ===== ESTUDIANTE (Solo ver libros, no puede pedir prestados) =====

    // Dividir nombre y apellidos del campo nombre de Usuario
    $partes = explode(" ", $nombre_completo, 2);
    if (count($partes) < 2) {
        $_SESSION['error'] = "Nombre del alumno no válido - debe contener nombre y apellido";
        header("Location: index.php");
        exit();
    }

    $nombre    = $partes[0];
    $apellidos = $partes[1];

    /* Buscar en alumnado usando nombre y apellidos separados */
    /* Nota: id_alumnado viene de tabla alumnado y es diferente a id_usuario */
    $stmt2 = $conn->prepare("
        SELECT id_alumnado 
        FROM alumnado 
        WHERE nombre = ? AND apellidos = ?
    ");
    $stmt2->bind_param("ss", $nombre, $apellidos);
    $stmt2->execute();
    $resAlumno = $stmt2->get_result();

    if ($resAlumno->num_rows === 0) {
        $_SESSION['error'] = "El alumno no existe en el sistema";
        header("Location: index.php");
        exit();
    }

    $alumno = $resAlumno->fetch_assoc();

    // Guardar sesiones
    $_SESSION['id_usuario']  = $id_usuario;
    $_SESSION['id_alumnado'] = (int)$alumno['id_alumnado'];
    $_SESSION['nombre']      = $nombre_completo;
    $_SESSION['rol']         = 1;
    $_SESSION['id_rol']      = $id_rol;

    header("Location: librolista/lista.php?modo=visualizar");
    exit();

} elseif ($id_rol === 2) {
    // ===== MINIPROFESOR (Puede pedir prestados) =====

    // Dividir nombre y apellidos del campo nombre de Usuario
    $partes = explode(" ", $nombre_completo, 2);
    if (count($partes) < 2) {
        $_SESSION['error'] = "Nombre del alumno no válido - debe contener nombre y apellido";
        header("Location: index.php");
        exit();
    }

    $nombre    = $partes[0];
    $apellidos = $partes[1];

    /* Buscar en alumnado usando nombre y apellidos separados */
    $stmt2 = $conn->prepare("
        SELECT id_alumnado 
        FROM alumnado 
        WHERE nombre = ? AND apellidos = ?
    ");
    $stmt2->bind_param("ss", $nombre, $apellidos);
    $stmt2->execute();
    $resAlumno = $stmt2->get_result();

    if ($resAlumno->num_rows === 0) {
        $_SESSION['error'] = "El alumno no existe en el sistema";
        header("Location: index.php");
        exit();
    }

    $alumno = $resAlumno->fetch_assoc();

    // Guardar sesiones 
    $_SESSION['id_usuario']  = $id_usuario;
    $_SESSION['id_alumnado'] = (int)$alumno['id_alumnado'];
    $_SESSION['nombre']      = $nombre_completo;
    $_SESSION['rol']         = 2;
    $_SESSION['id_rol']      = $id_rol;

    header("Location: librolista/lista.php");
    exit();

} elseif ($id_rol === 3) {
    // ===== PROFESOR =====
    $_SESSION['id_usuario'] = $id_usuario;
    $_SESSION['nombre']     = $nombre_completo;
    $_SESSION['rol']        = 3;
    $_SESSION['id_rol']     = $id_rol;

    header("Location: profesor/indexprofesor.html");
    exit();

} elseif ($id_rol === 4) {
    // ===== ADMIN =====
    $_SESSION['id_usuario'] = $id_usuario;
    $_SESSION['nombre']     = $nombre_completo;
    $_SESSION['rol']        = 4;
    $_SESSION['id_rol']     = $id_rol;

    header("Location: admin/indexadmin.html");
    exit();

} else {
    $_SESSION['error'] = "Rol desconocido o no válido";
    header("Location: index.php");
    exit();
}

$conn->close();
