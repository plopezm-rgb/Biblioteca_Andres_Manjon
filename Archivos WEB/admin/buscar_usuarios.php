<?php
// ================================
// Conexión a la base de datos
// ================================
$conn = new mysqli("localhost", "root", "", "sistemabiblioteca");
if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

// ================================
// Manejar eliminación de usuario
// ================================
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['eliminar_usuario'])) {
    $id_usuario = $_POST['eliminar_usuario']; // ID del usuario a eliminar
    $busqueda_redirect = $_POST['busqueda'] ?? ''; // Término de búsqueda para redirigir después
    $tipo_busqueda_redirect = $_POST['tipo_busqueda'] ?? 'nombre'; // Tipo de búsqueda para redirigir

    // ================================
    // Obtener código de carnet para eliminar datos relacionados en alumnado
    // ================================
    $stmt_get = $conn->prepare("SELECT codigo_de_carnet FROM usuario WHERE id_usuario = ?");
    $stmt_get->bind_param("i", $id_usuario);
    $stmt_get->execute();
    $stmt_get->bind_result($codigo_carnet);
    $stmt_get->fetch();
    $stmt_get->close();

    if ($codigo_carnet) {
        // ================================
        // Obtener ID del alumno correspondiente
        // ================================
        $stmt_get_id = $conn->prepare("SELECT id_alumnado FROM alumnado WHERE codigo_de_carnet = ?");
        $stmt_get_id->bind_param("s", $codigo_carnet);
        $stmt_get_id->execute();
        $stmt_get_id->bind_result($id_alumnado);
        $stmt_get_id->fetch();
        $stmt_get_id->close();

        if ($id_alumnado) {
            // ================================
            // Eliminar préstamos del alumno
            // ================================
            $stmt_delete_prestamos = $conn->prepare("DELETE FROM prestamo WHERE id_alumnado = ?");
            $stmt_delete_prestamos->bind_param("i", $id_alumnado);
            $stmt_delete_prestamos->execute();
            $stmt_delete_prestamos->close();

            // ================================
            // Eliminar datos del alumno
            // ================================
            $stmt_delete_alumno = $conn->prepare("DELETE FROM alumnado WHERE id_alumnado = ?");
            $stmt_delete_alumno->bind_param("i", $id_alumnado);
            $stmt_delete_alumno->execute();
            $stmt_delete_alumno->close();
        }
    }

    // ================================
    // Eliminar usuario de la tabla usuario
    // ================================
    $stmt_delete = $conn->prepare("DELETE FROM usuario WHERE id_usuario = ?");
    $stmt_delete->bind_param("i", $id_usuario);
    $stmt_delete->execute();
    $stmt_delete->close();

    // ================================
    // Redirigir a la página de búsqueda con parámetros
    // ================================
    header("Location: buscar_usuarios.php?busqueda=" . urlencode($busqueda_redirect) . "&tipo_busqueda=" . urlencode($tipo_busqueda_redirect));
    exit();
}

// ================================
// Inicialización de variables
// ================================
$usuarios = [];        // Array que contendrá los usuarios encontrados
$busqueda = '';        // Término de búsqueda
$tipo_busqueda = 'nombre'; // Tipo de búsqueda (nombre, username, todos)
$filtro_rol = '';      // Filtro opcional por rol

// ================================
// Manejar búsqueda de usuarios
// ================================
if (($_SERVER["REQUEST_METHOD"] == "POST" && !isset($_POST['eliminar_usuario'])) || isset($_GET['busqueda'])) {
    $busqueda = $_GET['busqueda'] ?? $_POST['busqueda'] ?? '';
    $tipo_busqueda = $_GET['tipo_busqueda'] ?? $_POST['tipo_busqueda'] ?? 'nombre';
    $filtro_rol = $_GET['filtro_rol'] ?? $_POST['filtro_rol'] ?? '';

    // ================================
    // Consulta para mostrar todos los usuarios
    // ================================
    if ($tipo_busqueda == 'todos') {
        $sql = "SELECT u.id_usuario, u.username, u.nombre, u.id_rol, u.contrasenia, u.codigo_de_carnet, 
                        a.clase, a.edad, a.estado_de_sancion
                FROM usuario u
                LEFT JOIN alumnado a ON a.codigo_de_carnet = u.codigo_de_carnet
                WHERE 1=1"; // Siempre verdadero, para poder añadir AND dinámicamente

        // Aplicar filtro por rol si se seleccionó
        if ($filtro_rol) {
            $sql .= " AND u.id_rol = ?";
        }

        $sql .= " ORDER BY u.nombre"; // Orden alfabético por nombre

        $stmt = $conn->prepare($sql);
        if ($filtro_rol) {
            $stmt->bind_param("i", $filtro_rol);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        $usuarios = $result->fetch_all(MYSQLI_ASSOC); // Guardar resultados en array
        $stmt->close();

    } else {
        // ================================
        // Consulta para búsqueda específica por nombre o username
        // ================================
        $sql = "SELECT u.id_usuario, u.username, u.nombre, u.id_rol, u.contrasenia, u.codigo_de_carnet, 
                        a.clase, a.edad, a.estado_de_sancion
                FROM usuario u
                LEFT JOIN alumnado a ON a.codigo_de_carnet = u.codigo_de_carnet
                WHERE ";

        if ($tipo_busqueda == 'nombre') {
            $sql .= "u.nombre LIKE ?";
        } elseif ($tipo_busqueda == 'username') {
            $sql .= "u.username LIKE ?";
        }

        if ($filtro_rol) {
            $sql .= " AND u.id_rol = ?";
        }

        $sql .= " ORDER BY u.nombre";

        $stmt = $conn->prepare($sql);
        $param = "%" . $busqueda . "%"; // Preparar búsqueda parcial

        if ($filtro_rol) {
            $stmt->bind_param("si", $param, $filtro_rol);
        } else {
            $stmt->bind_param("s", $param);
        }

        $stmt->execute();
        $result = $stmt->get_result();
        $usuarios = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
    }
}

// Cerrar conexión
$conn->close();
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Gestión de Usuarios - Biblioteca</title>
    <link rel="stylesheet" href="../css/estilo.css">
    <style>
        /* ================================
           Estilos generales de la página
           ================================ */
        :root{font-family: Arial, Helvetica, sans-serif}
        .search-form{max-width:600px;margin:20px auto;padding:20px;background:rgba(255,255,255,.95);border-radius:10px;box-shadow:0 4px 8px rgba(0,0,0,.08)}
        .search-form form{display:flex;flex-direction:column;gap:10px}
        .search-form select,.search-form input[type=text]{padding:10px;border:1px solid #ccc;border-radius:5px}
        .search-form button{padding:10px;background:#4CAF50;color:#fff;border:0;border-radius:5px;cursor:pointer}

        .alumno-card{background:#fff;margin:20px auto;max-width:800px;padding:20px;border-radius:10px;box-shadow:0 4px 8px rgba(0,0,0,.08)}
        .acciones{margin-top:10px;display:flex;gap:8px;flex-wrap:wrap}
        .btn-editar,.btn-eliminar{padding:6px 12px;margin-right:0;border-radius:5px;border:0;color:#fff;cursor:pointer;text-decoration:none;font-size:12px}
        .btn-editar{background:#007bff;display:inline-block}
        .btn-eliminar{background:#dc3545}

        .rol-badge{display:inline-block;padding:4px 8px;border-radius:4px;font-size:12px;font-weight:bold;margin-left:10px}
        .rol-1{background:#e3f2fd;color:#1976d2}
        .rol-2{background:#f3e5f5;color:#7b1fa2}
        .rol-3{background:#fff3e0;color:#f57c00}
        .rol-4{background:#ffebee;color:#c62828}

        .password-container{display:flex;align-items:center;gap:8px;margin-top:8px}
        .password-field{font-family:monospace;padding:6px 10px;background:#f5f5f5;border-radius:4px;min-width:150px}
        .btn-toggle-pass{padding:4px 8px;background:#007bff;color:#fff;border:0;border-radius:4px;cursor:pointer;font-size:12px}
    </style>
</head>
<body>
    <!-- ================================
         Cabecera
         ================================ -->
    <header class="header" role="banner">
        <img src="../img/logoA.png" alt="logo">
        <section class="text">
            <h1 id="header1">CEIP Andrés Manjón</h1>
            <h2 id="header2">Biblioteca</h2>
        </section>
        <section class="buttons" aria-hidden="true">
            <img src="../img/help.png" class="head-foto" alt="ayuda">
            <button id="helpBtn">I need help</button>
        </section>
    </header>

    <!-- ================================
         Formulario de búsqueda
         ================================ -->
    <main>
        <section class="search-form" aria-labelledby="search-title">
            <a href="indexadmin.html" class="btn-volver">← Volver</a>
            <h3 id="search-title">Buscar Usuario/Panel Admin</h3>
            <form method="get" role="search">
                <!-- Selección tipo búsqueda y filtro por rol -->
                <section style="display:flex;gap:10px">
                    <section style="flex:1">
                        <label for="tipo_busqueda" class="sr-only">Tipo de búsqueda</label>
                        <select id="tipo_busqueda" name="tipo_busqueda" onchange="toggleInput(this.value)">
                            <option value="nombre" <?php echo ($tipo_busqueda == 'nombre') ? 'selected' : ''; ?>>Nombre</option>
                            <option value="username" <?php echo ($tipo_busqueda == 'username') ? 'selected' : ''; ?>>Username</option>
                            <option value="todos" <?php echo ($tipo_busqueda == 'todos') ? 'selected' : ''; ?>>Todos</option>
                        </select>
                    </section>
                    <section style="flex:1">
                        <label for="filtro_rol" class="sr-only">Filtrar por rol</label>
                        <select id="filtro_rol" name="filtro_rol">
                            <option value="">Todos los Roles</option>
                            <option value="1" <?php echo (isset($_GET['filtro_rol']) && $_GET['filtro_rol'] == 1) ? 'selected' : ''; ?>>Alumno</option>
                            <option value="2" <?php echo (isset($_GET['filtro_rol']) && $_GET['filtro_rol'] == 2) ? 'selected' : ''; ?>>Mini Profesor</option>
                            <option value="3" <?php echo (isset($_GET['filtro_rol']) && $_GET['filtro_rol'] == 3) ? 'selected' : ''; ?>>Profesor</option>
                            <option value="4" <?php echo (isset($_GET['filtro_rol']) && $_GET['filtro_rol'] == 4) ? 'selected' : ''; ?>>Admin</option>
                        </select>
                    </section>
                </section>

                <!-- Campo de texto para búsqueda -->
                <label for="busqueda" class="sr-only">Término de búsqueda</label>
                <input id="busqueda" type="text" name="busqueda" placeholder="<?php echo ($tipo_busqueda == 'todos') ? 'No necesario para \'Todos\'' : 'Ingrese el término de búsqueda'; ?>" value="<?php echo htmlspecialchars($busqueda); ?>" <?php echo ($tipo_busqueda != 'todos') ? 'required' : ''; ?>>

                <button type="submit">Buscar</button>
            </form>
        </section>

        <!-- ================================
             Mostrar usuarios encontrados
             ================================ -->
        <?php if (!empty($usuarios)): ?>
            <?php foreach ($usuarios as $usuario): ?>
                <article class="alumno-card" aria-labelledby="usuario-<?php echo $usuario['id_usuario']; ?>">
                    <h3 id="usuario-<?php echo $usuario['id_usuario']; ?>" class="alumno-name"><?php echo htmlspecialchars($usuario['nombre']); ?> 
                        <?php 
                            // Mostrar badge del rol
                            if ($usuario['id_rol'] == 1) echo '<span class="rol-badge rol-1">Alumno</span>';
                            elseif ($usuario['id_rol'] == 2) echo '<span class="rol-badge rol-2">Mini Profesor</span>';
                            elseif ($usuario['id_rol'] == 3) echo '<span class="rol-badge rol-3">Profesor</span>';
                            elseif ($usuario['id_rol'] == 4) echo '<span class="rol-badge rol-4">Admin</span>';
                            else echo '<span class="rol-badge" style="background:#f3e5f5;color:#666;">Sin rol</span>';
                        ?>
                    </h3>

                    <!-- Mostrar info del alumno si existe -->
                    <?php if ($usuario['clase']): ?>
                    <p><strong>Clase:</strong> <?php echo htmlspecialchars($usuario['clase'] ?? 'N/A'); ?></p>
                    <p><strong>Edad:</strong> <?php echo htmlspecialchars($usuario['edad'] ?? 'N/A'); ?></p>
                    <p><strong>Código de Carnet:</strong> <?php echo htmlspecialchars($usuario['codigo_de_carnet'] ?? 'N/A'); ?></p>
                    <p><strong>Estado de Sanción:</strong> <?php echo htmlspecialchars($usuario['estado_de_sancion'] ?? 'Sin sanción'); ?></p>
                    <?php endif; ?>

                    <!-- Mostrar username y contraseña con toggle -->
                    <?php if ($usuario['username']): ?>
                    <section class="password-container">
                        <strong>Acceso:</strong>
                        <span style="margin-left:10px">Username: <strong><?php echo htmlspecialchars($usuario['username']); ?></strong></span>
                        <section style="flex:1"></section>
                        <button type="button" class="btn-toggle-pass" onclick="togglePassword(this)">Mostrar Pass</button>
                        <span class="password-field" style="display:none" data-password="<?php echo htmlspecialchars($usuario['contrasenia']); ?>">••••••••</span>
                    </section>
                    <?php endif; ?>

                    <!-- Botones de acción -->
                    <section class="acciones">
                        <a href="editar_usuarios.php?id=<?php echo $usuario['id_usuario']; ?>&busqueda=<?php echo urlencode($busqueda); ?>&tipo_busqueda=<?php echo urlencode($tipo_busqueda); ?>" class="btn-editar">Editar</a>
                        <form method="post" style="display:inline" onsubmit="return confirm('¿Estás seguro de que quieres eliminar este usuario?');">
                            <input type="hidden" name="eliminar_usuario" value="<?php echo $usuario['id_usuario']; ?>">
                            <input type="hidden" name="busqueda" value="<?php echo htmlspecialchars($busqueda); ?>">
                            <input type="hidden" name="tipo_busqueda" value="<?php echo htmlspecialchars($tipo_busqueda); ?>">
                            <button type="submit" class="btn-eliminar">Eliminar</button>
                        </form>
                    </section>
                </article>
            <?php endforeach; ?>
        <?php elseif ($_SERVER["REQUEST_METHOD"] == "POST"): ?>
            <section class="alumno-card"><p>No se encontraron usuarios con ese criterio de búsqueda.</p></section>
        <?php endif; ?>
    </main>

    <!-- ================================
         Footer
         ================================ -->
    <footer class="estilo-footer" role="contentinfo">
        <section class="estilo-footer-container">
            <section class="estilo-footer-logo"><img src="../img/footerlogo.png" alt="Logo"></section>
            <section class="estilo-footer-column">
                <h4>SOBRE NOSOTROS</h4>
                <ul><li><a href="https://ceipandresmanjon.catedu.aragon.es/">Web del centro</a></li></ul>
            </section>
            <section class="estilo-footer-column">
                <h4>DIRECCIÓN</h4>
                <p>CEIP Andrés Manjón.<br>C/ Delicias, 90, 50017, Zaragoza</p>
            </section>
            <section class="estilo-footer-column">
                <h4>TELÉFONO</h4>
                <p>976 331 728</p>
                <h4>E-MAIL</h4>
                <p>cpamanzaragoza@educa.aragon.es</p>
            </section>
            <section class="estilo-footer-column">
                <h4>HORARIO</h4>
                <p>De lunes a viernes de 9 a 14 h</p>
            </section>
        </section>
    </footer>

    <!-- ================================
         JavaScript
         ================================ -->
    <script>
        // Mostrar u ocultar campo de búsqueda según tipo
        function toggleInput(value){
            var input = document.querySelector('input[name="busqueda"]');
            if(!input) return;
            if(value === 'todos'){
                input.required = false; 
                input.placeholder = "Presione buscar para ver todos los usuarios";
            } else { 
                input.required = true; 
                input.placeholder = "Ingrese el término de búsqueda"; 
            }
        }

        // Mostrar u ocultar contraseña
        function togglePassword(btn){
            var container = btn.closest('.password-container');
            var field = container.querySelector('.password-field');
            var isHidden = field.style.display === 'none';
            if(isHidden){
                field.textContent = field.dataset.password;
                field.style.display = 'inline-block';
                btn.textContent = 'Ocultar Pass';
            } else {
                field.textContent = '••••••••';
                field.style.display = 'none';
                btn.textContent = 'Mostrar Pass';
            }
        }
    </script>
</body>
</html>
