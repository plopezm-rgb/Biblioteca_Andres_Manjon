<?php
// -------------------------
// CONEXIÓN A LA BASE DE DATOS
// -------------------------
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "sistemabiblioteca";

// Crear conexión
$conn = new mysqli($servername, $username, $password, $dbname);

// Verificar si la conexión fue exitosa
if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

// -------------------------
// INICIALIZAR VARIABLES
// -------------------------
$search = ''; // Variable para búsqueda por título
$color = '';  // Variable para filtro por color
$sql_base = "FROM Libro WHERE 1=1"; // Base de la consulta SQL (siempre true para concatenar filtros)

// -------------------------
// PROPAGAR MODO INVITADO
// -------------------------
$is_guest = isset($_GET['guest']) && $_GET['guest'] == '1'; // Si es invitado
$guest_role = isset($_GET['id_rol']) ? (int)$_GET['id_rol'] : 0; // Rol de invitado
$guest_query = $is_guest ? ('&guest=1&id_rol=' . $guest_role) : ''; // Parámetros GET extra para mantener invitado

// -------------------------
// FILTRO POR BÚSQUEDA DE TÍTULO
// -------------------------
if (!empty($_GET['query'])) {
    $search = $conn->real_escape_string($_GET['query']); // Escapar caracteres especiales
    $sql_base .= " AND titulo LIKE '%$search%'"; // Agregar condición SQL
}

// -------------------------
// FILTRO POR COLOR
// -------------------------
if (!empty($_GET['color'])) {
    $color = $conn->real_escape_string($_GET['color']); // Escapar caracteres
    $sql_base .= " AND ubicacion_por_colores='$color'"; // Agregar condición SQL
}

// -------------------------
// PAGINACIÓN
// -------------------------
$libros_por_pagina = 30; // Cantidad de libros por página
$pagina = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1; // Número de página actual
$offset = ($pagina - 1) * $libros_por_pagina; // Calcular offset para LIMIT

// -------------------------
// CONTAR TOTAL DE LIBROS PARA PAGINACIÓN
// -------------------------
$total_sql = "SELECT COUNT(*) AS total " . $sql_base;
$total_result = $conn->query($total_sql);
$total_filas = $total_result->fetch_assoc()['total']; // Total de libros
$total_paginas = ceil($total_filas / $libros_por_pagina); // Total de páginas

// -------------------------
// CONSULTAR LIBROS DE LA PÁGINA ACTUAL
// -------------------------
$sql = "SELECT id_libro, titulo, ubicacion_por_colores " . $sql_base . " ORDER BY id_libro ASC LIMIT $libros_por_pagina OFFSET $offset";
$result = $conn->query($sql);

// -------------------------
// MOSTRAR LOS LIBROS
// -------------------------
if ($result) {
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $id = $row['id_libro']; // ID del libro
            $titulo = htmlspecialchars($row['titulo'], ENT_QUOTES, 'UTF-8'); // Escapar caracteres especiales para HTML
            $imagen = "../img_libros/$id.jpg"; // Ruta de la portada
            if (!file_exists($imagen)) {
                $imagen = "../img_libros/default.jpg"; // Imagen por defecto si no existe
            }

            // Mostrar tarjeta del libro
            echo '<div class="book-card">';
            echo '<a href="detalles.php?id=' . $id . ($is_guest ? '&guest=1&id_rol=' . $guest_role : '') . '" style="text-decoration: none; color: inherit;">';
            echo "<img src=\"$imagen\" alt=\"$titulo\">"; // Imagen del libro
            echo "<div class=\"book-title\"><a href=\"detalles.php?id=$id" . ($is_guest ? "&guest=1&id_rol=" . $guest_role : "") . "\" style='text-decoration: none; color:black;'>$titulo</a></div>";
            echo '</div>';
        }
    } else {
        // Mensaje cuando no hay libros
        echo "<p>No hay libros disponibles.</p>";
    }
} else {
    // Mensaje de error en consulta
    echo "Error en la consulta: " . $conn->error;
}

// -------------------------
// BOTONES DE PAGINACIÓN
// -------------------------
if ($total_paginas > 1) {
    echo '<div class="pagination">';
    
    // Botón "Anterior"
    if ($pagina > 1) {
        echo '<a href="?page=' . ($pagina - 1) . '&query=' . urlencode($search) . '&color=' . urlencode($color) . $guest_query . '">⬅ Anterior</a>';
    }
    
    // Página actual
    echo "<span> Página $pagina de $total_paginas </span>";
    
    // Botón "Siguiente"
    if ($pagina < $total_paginas) {
        echo '<a href="?page=' . ($pagina + 1) . '&query=' . urlencode($search) . '&color=' . urlencode($color) . $guest_query . '">Siguiente ➡</a>';
    }
    
    echo '</div>';
}

// Cerrar conexión
$conn->close();
?>
