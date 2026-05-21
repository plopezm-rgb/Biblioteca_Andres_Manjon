<?php
// Conexión a la base de datos
$conn = new mysqli("localhost", "root", "", "sistemabiblioteca");
if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

// Manejar eliminación de alumno
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['eliminar_alumno'])) {
    $id_alumnado = $_POST['eliminar_alumno'];
    $busqueda_redirect = $_POST['busqueda'] ?? '';
    $tipo_busqueda_redirect = $_POST['tipo_busqueda'] ?? 'nombre';
    
    // Eliminar préstamos asociados primero
    $stmt_delete_prestamos = $conn->prepare("DELETE FROM prestamo WHERE id_alumnado = ?");
    $stmt_delete_prestamos->bind_param("i", $id_alumnado);
    $stmt_delete_prestamos->execute();
    $stmt_delete_prestamos->close();
    
    // Eliminar alumno
    $stmt_delete = $conn->prepare("DELETE FROM alumnado WHERE id_alumnado = ?");
    $stmt_delete->bind_param("i", $id_alumnado);
    $stmt_delete->execute();
    $stmt_delete->close();
    
    // Redirigir con parámetros
    header("Location: buscar_alumno.php?busqueda=" . urlencode($busqueda_redirect) . "&tipo_busqueda=" . urlencode($tipo_busqueda_redirect));
    exit();
}
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['marcar_devuelto'])) {
    $id_prestamo = $_POST['id_prestamo'];
    
    // Obtener id_libro del préstamo
    $stmt_get = $conn->prepare("SELECT id_libro FROM prestamo WHERE id_prestamo = ?");
    $stmt_get->bind_param("i", $id_prestamo);
    $stmt_get->execute();
    $stmt_get->bind_result($id_libro);
    $stmt_get->fetch();
    $stmt_get->close();
    
    // Actualizar estado del libro a 'Disponible'
    $stmt_update_libro = $conn->prepare("UPDATE libro SET estado_de_actividad = 'Disponible' WHERE id_libro = ?");
    $stmt_update_libro->bind_param("i", $id_libro);
    $stmt_update_libro->execute();
    $stmt_update_libro->close();
    
    // Actualizar préstamo
    $stmt_update = $conn->prepare("UPDATE prestamo SET fecha_de_devolucion = CURDATE(), estado_del_prestamo = 'Devuelto' WHERE id_prestamo = ?");
    $stmt_update->bind_param("i", $id_prestamo);
    $stmt_update->execute();
    $stmt_update->close();
    
    // Redirigir para evitar reenvío de formulario
    header("Location: " . $_SERVER['REQUEST_URI']);
    exit();
}

// Inicializar variables
$alumnos = [];
$busqueda = '';
$tipo_busqueda = 'nombre';
$prestamos_por_alumno = [];

if (($_SERVER["REQUEST_METHOD"] == "POST" && !isset($_POST['marcar_devuelto']) && !isset($_POST['eliminar_alumno'])) || isset($_GET['busqueda'])) {
    $busqueda = $_GET['busqueda'] ?? $_POST['busqueda'] ?? '';
    $tipo_busqueda = $_GET['tipo_busqueda'] ?? $_POST['tipo_busqueda'] ?? 'nombre';

    // Consulta para buscar alumnos - Solo mostrar rol 1 (alumno) y 2 (miniprofesor)
    if ($tipo_busqueda == 'todos') {
        $sql = "SELECT a.*, u.username, u.id_rol, u.contrasenia 
                FROM alumnado a
                LEFT JOIN usuario u ON u.codigo_de_carnet = a.codigo_de_carnet AND u.id_rol IN (1, 2)
                ORDER BY a.nombre";
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $result = $stmt->get_result();
        $alumnos = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
    } else {
        $sql = "SELECT a.*, u.username, u.id_rol, u.contrasenia 
                FROM alumnado a
                LEFT JOIN usuario u ON u.codigo_de_carnet = a.codigo_de_carnet AND u.id_rol IN (1, 2)
                WHERE ";
        if ($tipo_busqueda == 'nombre') {
            $sql .= "a.nombre LIKE ?";
        } elseif ($tipo_busqueda == 'apellidos') {
            $sql .= "a.apellidos LIKE ?";
        } elseif ($tipo_busqueda == 'clase') {
            $sql .= "a.clase LIKE ?";
        } elseif ($tipo_busqueda == 'codigo') {
            $sql .= "a.codigo_de_carnet LIKE ?";
        }

        $stmt = $conn->prepare($sql);
        $param = "%" . $busqueda . "%";
        $stmt->bind_param("s", $param);
        $stmt->execute();
        $result = $stmt->get_result();
        $alumnos = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
    }

    // Obtener préstamos para todos los alumnos encontrados
    if (!empty($alumnos)) {
        $ids = array_column($alumnos, 'id_alumnado');
        $placeholders = str_repeat('?,', count($ids) - 1) . '?';
        $sql_prestamos = "SELECT p.id_prestamo, p.id_alumnado, p.fecha_de_salida, p.fecha_de_devolucion, p.estado_del_prestamo, l.titulo, l.isbn FROM prestamo p JOIN libro l ON p.id_libro = l.id_libro WHERE p.id_alumnado IN ($placeholders)";
        $stmt_p = $conn->prepare($sql_prestamos);
        $stmt_p->bind_param(str_repeat('i', count($ids)), ...$ids);
        $stmt_p->execute();
        $result_p = $stmt_p->get_result();
        while ($row = $result_p->fetch_assoc()) {
            $prestamos_por_alumno[$row['id_alumnado']][] = $row;
        }
        $stmt_p->close();
    }
}

$conn->close();
?>

<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Buscar Alumno - Biblioteca</title>
    <link rel="stylesheet" href="../css/estilo.css">
    <style>
        :root{font-family: Arial, Helvetica, sans-serif}
        .search-form{max-width:600px;margin:20px auto;padding:20px;background:rgba(255,255,255,.95);border-radius:10px;box-shadow:0 4px 8px rgba(0,0,0,.08)}
        .search-form form{display:flex;flex-direction:column;gap:10px}
        .search-form select,.search-form input[type=text]{padding:10px;border:1px solid #ccc;border-radius:5px}
        .search-form button{padding:10px;background:#4CAF50;color:#fff;border:0;border-radius:5px;cursor:pointer}
        .alumno-card{background:#fff;margin:20px auto;max-width:800px;padding:20px;border-radius:10px;box-shadow:0 4px 8px rgba(0,0,0,.08)}
        .acciones{margin-top:10px}
        .btn-editar,.btn-eliminar{padding:6px 12px;margin-right:8px;border-radius:5px;border:0;color:#fff;cursor:pointer;text-decoration:none}
        .btn-editar{background:#007bff}
        .btn-eliminar{background:#dc3545}
        .btn-eliminar:hover{transform:scale(1.05)}
        .prestamos-detalles{display:none}
        .prestamos-detalles.open{display:block}
        .section-details{display:none}
        .section-details.open{display:block}
        .prestamo-item{margin:10px 0;padding:10px;border-left:5px solid #4CAF50;background:#f9f9f9}
        .prestamo-item.devuelto{border-left-color:green}
        .prestamo-item.pendiente{border-left-color:blue}
        .prestamo-item.no-devuelto{border-left-color:red}
        .rol-badge{display:inline-block;padding:4px 8px;border-radius:4px;font-size:12px;font-weight:bold;margin-left:10px}
        .rol-1{background:#e3f2fd;color:#1976d2}
        .rol-2{background:#f3e5f5;color:#7b1fa2}
        .password-container{display:flex;align-items:center;gap:8px;margin-top:8px}
        .password-field{font-family:monospace;padding:6px 10px;background:#f5f5f5;border-radius:4px;min-width:150px}
        .btn-toggle-pass{padding:4px 8px;background:#007bff;color:#fff;border:0;border-radius:4px;cursor:pointer;font-size:12px}
        .btn-toggle-pass:hover{background:#0056b3}
    </style>
</head>
<body>
    <header class="header" role="banner">
        <img src="../img/logoA.png" alt="logo">
        <div class="text">
            <h1 id="header1">CEIP Andrés Manjón</h1>
            <h2 id="header2">Biblioteca</h2>
        </div>
        <div class="buttons" aria-hidden="true">
            <img src="../img/help.png" class="head-foto" alt="ayuda">
            <button id="helpBtn">I need help</button>
        </div>
    </header>

    <main>
        <section class="search-form" aria-labelledby="search-title">
            <a href="indexprofesor.html" class="btn-volver">← Volver</a>
            <h3 id="search-title">Buscar Alumno/Panel Profesor</h3>
            <form method="get" role="search">
                <label for="tipo_busqueda" class="sr-only">Tipo de búsqueda</label>
                <select id="tipo_busqueda" name="tipo_busqueda" onchange="toggleInput(this.value)">
                    <option value="nombre" <?php echo ($tipo_busqueda == 'nombre') ? 'selected' : ''; ?>>Nombre</option>
                    <option value="apellidos" <?php echo ($tipo_busqueda == 'apellidos') ? 'selected' : ''; ?>>Apellidos</option>
                    <option value="clase" <?php echo ($tipo_busqueda == 'clase') ? 'selected' : ''; ?>>Clase</option>
                    <option value="codigo" <?php echo ($tipo_busqueda == 'codigo') ? 'selected' : ''; ?>>Código de Carnet</option>
                    <option value="todos" <?php echo ($tipo_busqueda == 'todos') ? 'selected' : ''; ?>>Todos</option>
                </select>
                <label for="busqueda" class="sr-only">Término de búsqueda</label>
                <input id="busqueda" type="text" name="busqueda" placeholder="<?php echo ($tipo_busqueda == 'todos') ? 'No necesario para \'Todos\'' : 'Ingrese el término de búsqueda'; ?>" value="<?php echo htmlspecialchars($busqueda); ?>" <?php echo ($tipo_busqueda != 'todos') ? 'required' : ''; ?>>
                <button type="submit">Buscar</button>
            </form>
        </section>

        <?php if (!empty($alumnos)): ?>
            <?php foreach ($alumnos as $alumno): ?>
                <article class="alumno-card" aria-labelledby="alumno-<?php echo $alumno['id_alumnado']; ?>">
                    <h3 id="alumno-<?php echo $alumno['id_alumnado']; ?>" class="alumno-name" tabindex="0" onclick="toggleDetails(this)"><?php echo htmlspecialchars($alumno['nombre'] . ' ' . $alumno['apellidos']); ?> 
                        <?php 
                            if ($alumno['id_rol'] == 1) {
                                echo '<span class="rol-badge rol-1">Alumno</span>';
                            } elseif ($alumno['id_rol'] == 2) {
                                echo '<span class="rol-badge rol-2">Mini Profesor</span>';
                            } else {
                                echo '<span class="rol-badge" style="background:#f3e5f5;color:#666;">Sin rol</span>';
                            }
                        ?>
                        <span aria-hidden="true">▼</span>
                    </h3>
                    <p><strong>Clase:</strong> <?php echo htmlspecialchars($alumno['clase'] ?? 'N/A'); ?></p>
                    <p><strong>Edad:</strong> <?php echo htmlspecialchars($alumno['edad'] ?? 'N/A'); ?></p>
                    <p><strong>Código de Carnet:</strong> <?php echo htmlspecialchars($alumno['codigo_de_carnet'] ?? 'N/A'); ?></p>
                    <p><strong>Estado de Sanción:</strong> <?php echo htmlspecialchars($alumno['estado_de_sancion'] ?? 'Sin sanción'); ?></p>
                    
                    <?php if ($alumno['username']): ?>
                    <div class="password-container">
                        <strong>Acceso:</strong>
                        <span style="margin-left:10px">Username: <strong><?php echo htmlspecialchars($alumno['username']); ?></strong></span>
                        <div style="flex:1"></div>
                        <button type="button" class="btn-toggle-pass" onclick="togglePassword(this)">Mostrar Pass</button>
                        <span class="password-field" style="display:none" data-password="<?php echo htmlspecialchars($alumno['contrasenia']); ?>">••••••••</span>
                    </div>
                    <?php endif; ?>

                    <div class="acciones">
                        <a href="editar_alumno.php?id=<?php echo $alumno['id_alumnado']; ?>&busqueda=<?php echo urlencode($busqueda); ?>&tipo_busqueda=<?php echo urlencode($tipo_busqueda); ?>" class="btn-editar">Editar</a>
                        <form method="post" class="form-inline" onsubmit="return confirm('¿Estás seguro de que quieres eliminar este alumno?');">
                            <input type="hidden" name="eliminar_alumno" value="<?php echo $alumno['id_alumnado']; ?>">
                            <input type="hidden" name="busqueda" value="<?php echo htmlspecialchars($busqueda); ?>">
                            <input type="hidden" name="tipo_busqueda" value="<?php echo htmlspecialchars($tipo_busqueda); ?>">
                            <button type="submit" class="btn-eliminar">Eliminar</button>
                        </form>
                    </div>

                    <div class="prestamos-detalles" aria-hidden="true">
                        <h4>Libros Prestados:</h4>
                        <?php
                            $prestamos = $prestamos_por_alumno[$alumno['id_alumnado']] ?? [];
                            $devueltos = [];
                            $pendientes = [];
                            $no_devueltos = [];
                            foreach ($prestamos as $prestamo) {
                                $fecha_limite = date('Y-m-d', strtotime($prestamo['fecha_de_salida'] . ' + 15 days'));
                                $hoy = date('Y-m-d');
                                if ($prestamo['fecha_de_devolucion'] || $prestamo['estado_del_prestamo'] == 'Devuelto') {
                                    $devueltos[] = $prestamo;
                                } elseif ($hoy > $fecha_limite) {
                                    $no_devueltos[] = $prestamo;
                                } else {
                                    $pendientes[] = $prestamo;
                                }
                            }
                        ?>

                        <?php if (!empty($pendientes)): ?>
                            <h5 class="toggle" onclick="toggleSection(this)">Pendientes <span aria-hidden="true">▼</span></h5>
                            <div class="section-details">
                                <?php foreach ($pendientes as $prestamo): ?>
                                    <div class="prestamo-item pendiente">
                                        <p><strong>Libro:</strong> <?php echo htmlspecialchars($prestamo['titulo']); ?></p>
                                        <p><strong>ISBN:</strong> <?php echo htmlspecialchars($prestamo['isbn']); ?></p>
                                        <p><strong>Fecha de Salida:</strong> <?php echo htmlspecialchars($prestamo['fecha_de_salida']); ?></p>
                                        <p><strong>Fecha Límite:</strong> <?php echo date('Y-m-d', strtotime($prestamo['fecha_de_salida'] . ' + 15 days')); ?></p>
                                        <p><strong>Fecha de Devolución:</strong> <?php echo htmlspecialchars($prestamo['fecha_de_devolucion'] ?? 'No devuelto'); ?></p>
                                        <p><strong>Estado:</strong> <?php echo htmlspecialchars($prestamo['estado_del_prestamo']); ?></p>
                                        <form method="post" class="form-inline">
                                            <input type="hidden" name="id_prestamo" value="<?php echo $prestamo['id_prestamo']; ?>">
                                            <input type="hidden" name="busqueda" value="<?php echo htmlspecialchars($busqueda); ?>">
                                            <input type="hidden" name="tipo_busqueda" value="<?php echo htmlspecialchars($tipo_busqueda); ?>">
                                            <button type="submit" name="marcar_devuelto">Marcar como Devuelto</button>
                                        </form>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($no_devueltos)): ?>
                            <h5 class="toggle" onclick="toggleSection(this)">No Devueltos <span aria-hidden="true">▼</span></h5>
                            <div class="section-details">
                                <?php foreach ($no_devueltos as $prestamo): ?>
                                    <div class="prestamo-item no-devuelto">
                                        <p><strong>Libro:</strong> <?php echo htmlspecialchars($prestamo['titulo']); ?></p>
                                        <p><strong>ISBN:</strong> <?php echo htmlspecialchars($prestamo['isbn']); ?></p>
                                        <p><strong>Fecha de Salida:</strong> <?php echo htmlspecialchars($prestamo['fecha_de_salida']); ?></p>
                                        <p><strong>Fecha Límite:</strong> <?php echo date('Y-m-d', strtotime($prestamo['fecha_de_salida'] . ' + 15 days')); ?></p>
                                        <p><strong>Fecha de Devolución:</strong> <?php echo htmlspecialchars($prestamo['fecha_de_devolucion'] ?? 'No devuelto'); ?></p>
                                        <p><strong>Estado:</strong> <?php echo htmlspecialchars($prestamo['estado_del_prestamo']); ?></p>
                                        <form method="post" class="form-inline">
                                            <input type="hidden" name="id_prestamo" value="<?php echo $prestamo['id_prestamo']; ?>">
                                            <input type="hidden" name="busqueda" value="<?php echo htmlspecialchars($busqueda); ?>">
                                            <input type="hidden" name="tipo_busqueda" value="<?php echo htmlspecialchars($tipo_busqueda); ?>">
                                            <button type="submit" name="marcar_devuelto">Marcar como Devuelto</button>
                                        </form>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($devueltos)): ?>
                            <h5 class="toggle" onclick="toggleSection(this)">Devueltos <span aria-hidden="true">▼</span></h5>
                            <div class="section-details">
                                <?php foreach ($devueltos as $prestamo): ?>
                                    <div class="prestamo-item devuelto">
                                        <p><strong>Libro:</strong> <?php echo htmlspecialchars($prestamo['titulo']); ?></p>
                                        <p><strong>ISBN:</strong> <?php echo htmlspecialchars($prestamo['isbn']); ?></p>
                                        <p><strong>Fecha de Salida:</strong> <?php echo htmlspecialchars($prestamo['fecha_de_salida']); ?></p>
                                        <p><strong>Fecha Límite:</strong> <?php echo date('Y-m-d', strtotime($prestamo['fecha_de_salida'] . ' + 15 days')); ?></p>
                                        <p><strong>Fecha de Devolución:</strong> <?php echo htmlspecialchars($prestamo['fecha_de_devolucion'] ?? 'No devuelto'); ?></p>
                                        <p><strong>Estado:</strong> <?php echo htmlspecialchars($prestamo['estado_del_prestamo']); ?></p>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <?php if (empty($prestamos)): ?>
                            <p>No tiene libros prestados.</p>
                        <?php endif; ?>
                    </div>
                </article>
            <?php endforeach; ?>
        <?php elseif ($_SERVER["REQUEST_METHOD"] == "POST"): ?>
            <div class="alumno-card"><p>No se encontraron alumnos con ese criterio de búsqueda.</p></div>
        <?php endif; ?>
    </main>

    <footer class="estilo-footer" role="contentinfo">
        <div class="estilo-footer-container">
            <div class="estilo-footer-logo"><img src="../img/footerlogo.png" alt="Logo"></div>
            <div class="estilo-footer-column">
                <h4>SOBRE NOSOTROS</h4>
                <ul><li><a href="https://ceipandresmanjon.catedu.aragon.es/">Web del centro</a></li></ul>
            </div>
            <div class="estilo-footer-column">
                <h4>DIRECCIÓN</h4>
                <p>CEIP Andrés Manjón.<br>C/ Delicias, 90, 50017, Zaragoza</p>
            </div>
            <div class="estilo-footer-column">
                <h4>TELÉFONO</h4>
                <p>976 331 728</p>
                <h4>E-MAIL</h4>
                <p>cpamanzaragoza@educa.aragon.es</p>
            </div>
            <div class="estilo-footer-column">
                <h4>HORARIO</h4>
                <p>De lunes a viernes de 9 a 14 h</p>
            </div>
        </div>
    </footer>

    <script>
        function toggleDetails(el){
            var card = el.closest('.alumno-card');
            var details = card.querySelector('.prestamos-detalles');
            details.classList.toggle('open');
            var arrow = el.querySelector('span');
            if(details.classList.contains('open')) arrow.textContent = '▲'; else arrow.textContent = '▼';
        }
        function toggleSection(h5){
            var details = h5.nextElementSibling;
            details.classList.toggle('open');
            var span = h5.querySelector('span');
            if(details.classList.contains('open')) span.textContent = '▲'; else span.textContent = '▼';
        }
        function toggleInput(value){
            var input = document.querySelector('input[name="busqueda"]');
            if(!input) return;
            if(value === 'todos'){
                input.required = false; input.placeholder = "No necesario para 'Todos'";
            } else { input.required = true; input.placeholder = "Ingrese el término de búsqueda"; }
        }
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