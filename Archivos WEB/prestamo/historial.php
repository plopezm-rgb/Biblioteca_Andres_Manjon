<?php
session_start();

if (!isset($_SESSION['id_usuario'])) {
    header("Location: ../index.html");
    exit;
}

$id_alumnado = $_SESSION['id_alumnado'] ?? null;
if (!$id_alumnado) {
    die("No se encontró el ID del alumno.");
}

// Conexión BD
$conn = new mysqli("localhost", "root", "", "sistemabiblioteca");
if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

// Procesar devolución de libro
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_prestamo'])) {
    $id_prestamo = (int)$_POST['id_prestamo'];
    $id_libro = (int)$_POST['id_libro'];
    
    // Actualizar estado del préstamo a "Devuelto"
    $update_prestamo = "UPDATE Prestamo SET estado_del_prestamo = 'Devuelto' WHERE id_prestamo = ?";
    $stmt_prestamo = $conn->prepare($update_prestamo);
    $stmt_prestamo->bind_param("i", $id_prestamo);
    $stmt_prestamo->execute();
    $stmt_prestamo->close();
    
    // Actualizar estado del libro a "disponible"
    $update_libro = "UPDATE Libro SET estado_de_actividad = 'disponible' WHERE id_libro = ?";
    $stmt_libro = $conn->prepare($update_libro);
    $stmt_libro->bind_param("i", $id_libro);
    $stmt_libro->execute();
    $stmt_libro->close();
    
    header("Location: historial.php?filtro=" . $_GET['filtro'] . "&exito=1");
    exit;
}

// Obtener tipo de filtro desde GET
$filtro = $_GET['filtro'] ?? 'todos';
$busqueda = $_GET['busqueda'] ?? '';
$hoy = date("Y-m-d");
$hace_30_dias = date("Y-m-d", strtotime("-30 days"));

$sql = "SELECT p.id_prestamo, l.titulo, l.id_libro, l.estado_de_actividad, 
               p.fecha_de_salida, p.fecha_limite, p.estado_del_prestamo
        FROM Prestamo p
        JOIN Libro l ON p.id_libro = l.id_libro
        WHERE p.id_alumnado = ?";

if ($filtro === 'no_devuelto') {
    $sql .= " AND p.estado_del_prestamo = 'Prestado'";
} elseif ($filtro === 'vencido') {
    $sql .= " AND p.estado_del_prestamo = 'Prestado' AND p.fecha_limite < ?";
} elseif ($filtro === 'devuelto') {
    $sql .= " AND p.estado_del_prestamo = 'Devuelto'";
}

if ($busqueda !== '') {
    $sql .= " AND l.titulo LIKE ?";
}

$sql .= " ORDER BY p.fecha_de_salida DESC";

$stmt = $conn->prepare($sql);
if ($filtro === 'vencido') {
    if ($busqueda !== '') {
        $search_param = "%$busqueda%";
        $stmt->bind_param("iss", $id_alumnado, $hoy, $search_param);
    } else {
        $stmt->bind_param("is", $id_alumnado, $hoy);
    }
} else {
    if ($busqueda !== '') {
        $search_param = "%$busqueda%";
        $stmt->bind_param("is", $id_alumnado, $search_param);
    } else {
        $stmt->bind_param("i", $id_alumnado);
    }
}

$stmt->execute();
$result = $stmt->get_result();
$todos_prestamos = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Separar en los primeros 10 y el resto
$prestamos_recientes = [];
$prestamos_antiguos = [];

foreach ($todos_prestamos as $index => $prestamo) {
    if ($index < 10) {
        $prestamos_recientes[] = $prestamo;
    } else {
        $prestamos_antiguos[] = $prestamo;
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Historial de Libros - Andrés Manjón</title>
    <link rel="stylesheet" href="../css/estilo.css">
    <style>
        .filtro-buttons {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin: 20px 0;
        }
        .filtro-buttons button {
            padding: 8px 15px;
            border-radius: 5px;
            border: none;
            cursor: pointer;
            background-color: #4CAF50;
            color: white;
            font-weight: bold;
        }
        .filtro-buttons button.active {
            background-color: #007BFF;
        }
        .historial-main {
            padding: 20px;
        }
        .historial-card {
            max-width: 900px;
            margin: 0 auto;
        }
        .historial-columns {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        .historial-entry {
            display: flex;
            gap: 20px;
            background-color: rgba(255, 255, 255, 0.9);
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            align-items: flex-start;
        }
        .historial-info {
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        .historial-input {
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 14px;
            background-color: #f9f9f9;
        }
        .historial-input:readonly {
            cursor: default;
        }
        .historial-tag {
            padding: 8px 12px;
            border-radius: 5px;
            border: none;
            font-weight: bold;
            cursor: default;
            width: fit-content;
        }
        .historial-estado {
            padding: 8px 12px;
            border-radius: 5px;
            font-weight: bold;
            width: fit-content;
            font-size: 14px;
        }
        .estado-devuelto {
            background-color: #d4edda;
            color: #155724;
        }
        .estado-prestado {
            background-color: #fff3cd;
            color: #856404;
        }
        .estado-vencido {
            background-color: #f8d7da;
            color: #721c24;
        }
        .historial-book-frame {
            width: 180px;
            height: 250px;
            flex-shrink: 0;
            position: relative;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        .historial-book-frame img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .historial-button-container {
            display: flex;
            gap: 10px;
            margin-top: 10px;
        }
        .btn-devolver {
            padding: 10px 15px;
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
            transition: background-color 0.3s;
        }
        .btn-devolver:hover {
            background-color: #45a049;
        }
        .btn-devolver:disabled {
            background-color: #cccccc;
            cursor: not-allowed;
        }
        .fecha-label {
            font-size: 12px;
            color: #666;
        }
        .fecha-valor {
            font-size: 14px;
            font-weight: bold;
            color: #333;
        }
        .dropdown-toggle {
            display: inline-block;
            padding: 12px 20px;
            background-color: #f0f0f0;
            border: 1px solid #ccc;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
            margin: 20px;
            user-select: none;
            transition: background-color 0.3s;
        }
        .dropdown-toggle:hover {
            background-color: #e0e0e0;
        }
        .dropdown-toggle::after {
            content: ' ▼';
            font-size: 10px;
        }
        .dropdown-content {
            display: none;
            position: fixed;
            background-color: white;
            min-width: 300px;
            box-shadow: 0 8px 16px rgba(0,0,0,0.2);
            border-radius: 5px;
            z-index: 1000;
            max-height: 500px;
            overflow-y: auto;
        }
        .dropdown-content.show {
            display: block;
        }
        .dropdown-search {
            padding: 15px;
            border-bottom: 1px solid #ddd;
            position: sticky;
            top: 0;
            background-color: white;
        }
        .dropdown-search input {
            width: 100%;
            padding: 8px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 14px;
            box-sizing: border-box;
        }
        .dropdown-items {
            max-height: 400px;
            overflow-y: auto;
        }
        .dropdown-item {
            padding: 12px 15px;
            border-bottom: 1px solid #f0f0f0;
            cursor: pointer;
            transition: background-color 0.2s;
        }
        .dropdown-item:hover {
            background-color: #f5f5f5;
        }
        .dropdown-item-title {
            font-weight: bold;
            color: #333;
        }
        .dropdown-item-date {
            font-size: 12px;
            color: #666;
        }
        .modal {
            display: none;
            position: fixed;
            z-index: 2000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
        }
        .modal.show {
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .modal-content {
            background-color: white;
            padding: 30px;
            border-radius: 10px;
            max-width: 900px;
            width: 90%;
            box-shadow: 0 4px 20px rgba(0,0,0,0.3);
        }
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .modal-title {
            font-size: 24px;
            font-weight: bold;
            color: #333;
        }
        .modal-close {
            background: none;
            border: none;
            font-size: 28px;
            cursor: pointer;
            color: #666;
        }
        .modal-body {
            display: flex;
            gap: 30px;
        }
        .modal-info {
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        .modal-book-frame {
            width: 200px;
            height: 280px;
            flex-shrink: 0;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        .modal-book-frame img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
    </style>
</head>
<body>
<section class="header">
    <img src="../img/logoA.png">
    <section class="text">
        <h4 id="header1">CEIP Andrés Manjón</h4>
        <h4 id="header2">Biblioteca</h4>
    </section>
    <h2 id="title">Historial de libros</h2>
    <section class="buttons">
        <img src="../img/presta.png" class="head-foto">
            <button><a href="../librolista/lista.php" class="presta-text">Lista de libro</a></button>
        <img src="../img/help.png" class="head-foto">
        <button id="helpBtn">I need help</button>
    </section>
    <img src="../img/idioma.png" class="head-foto">
            <select id="langSelect" class="form-select">
                <option value="ES">Español</option>
                <option value="EN">English</option>
                <option value="FR">Français</option>
                <option value="AL">عربي</option>
                <option value="CN">中文(简体中文)</option>
            </select>
</section>

<!-- Botones de filtro -->
<div class="filtro-buttons">
    <a href="?filtro=no_devuelto"><button class="<?php echo ($filtro==='no_devuelto')?'active':''; ?>">No Devuelto</button></a>
    <a href="?filtro=vencido"><button class="<?php echo ($filtro==='vencido')?'active':''; ?>">Vencido</button></a>
    <a href="?filtro=devuelto"><button class="<?php echo ($filtro==='devuelto')?'active':''; ?>">Devuelto</button></a>
    <a href="?filtro=todos"><button class="<?php echo ($filtro==='todos')?'active':''; ?>">Todos</button></a>
</div>

<?php if (isset($_GET['exito'])): ?>
    <div style="background-color: #d4edda; color: #155724; padding: 15px; margin: 20px; border-radius: 5px; text-align: center;">
        ¡Libro devuelto correctamente!
    </div>
<?php endif; ?>

<!-- Dropdown para histórico -->
<?php if (!empty($prestamos_antiguos)): ?>
    <div style="margin: 20px; text-align: center;">
        <div class="dropdown-toggle" id="dropdownToggle">
            Historial anterior (<?php echo count($prestamos_antiguos); ?> registros)
        </div>
        <div class="dropdown-content" id="dropdownContent">
            <div class="dropdown-search">
                <input type="text" id="dropdownSearch" placeholder="Buscar por nombre del libro...">
            </div>
            <div class="dropdown-items" id="dropdownItems">
                <?php foreach ($prestamos_antiguos as $prestamo): ?>
                    <div class="dropdown-item" 
                         data-title="<?php echo htmlspecialchars(strtolower($prestamo['titulo'])); ?>"
                         data-id-prestamo="<?php echo $prestamo['id_prestamo']; ?>"
                         data-id-libro="<?php echo $prestamo['id_libro']; ?>"
                         data-titulo="<?php echo htmlspecialchars($prestamo['titulo']); ?>"
                         data-fecha-salida="<?php echo $prestamo['fecha_de_salida']; ?>"
                         data-fecha-limite="<?php echo $prestamo['fecha_limite']; ?>"
                         data-estado="<?php echo htmlspecialchars($prestamo['estado_del_prestamo']); ?>">
                        <div class="dropdown-item-title"><?php echo htmlspecialchars($prestamo['titulo']); ?></div>
                        <div class="dropdown-item-date">Salida: <?php echo date("d/m/Y", strtotime($prestamo['fecha_de_salida'])); ?> | ID: <?php echo $prestamo['id_libro']; ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
<?php endif; ?>

<main class="historial-main">
    <section class="historial-card">
        <div class="historial-columns">
            <?php if (!empty($prestamos_recientes)): ?>
                <h3 style="text-align: center; margin-bottom: 20px;">Últimos préstamos</h3>
                <?php foreach ($prestamos_recientes as $prestamo): ?>
                    <div class="historial-entry">
                        <!-- Información a la izquierda -->
                        <div class="historial-info">
                            <div>
                                <label class="fecha-label">Título del libro</label>
                                <input type="text" class="historial-input" value="<?php echo htmlspecialchars($prestamo['titulo']); ?>" readonly>
                            </div>
                            <div>
                                <label class="fecha-label">ID del libro</label>
                                <input type="text" class="historial-input" value="<?php echo htmlspecialchars($prestamo['id_libro']); ?>" readonly>
                            </div>
                            <div>
                                <label class="fecha-label">Fecha de salida</label>
                                <div class="fecha-valor"><?php echo date("d/m/Y", strtotime($prestamo['fecha_de_salida'])); ?></div>
                            </div>
                            <div>
                                <label class="fecha-label">Fecha límite</label>
                                <div class="fecha-valor"><?php echo date("d/m/Y", strtotime($prestamo['fecha_limite'])); ?></div>
                            </div>
                            <div>
                                <label class="fecha-label">Estado</label>
                                <?php
                                    $estado = htmlspecialchars($prestamo['estado_del_prestamo']);
                                    $clase = 'estado-prestado';
                                    if ($estado === 'Devuelto') {
                                        $clase = 'estado-devuelto';
                                    } elseif ($estado === 'Prestado' && $prestamo['fecha_limite'] < $hoy) {
                                        $clase = 'estado-vencido';
                                    }
                                ?>
                                <div class="historial-estado <?php echo $clase; ?>"><?php echo $estado; ?></div>
                            </div>
                            
                            <!-- Botón devolver -->
                            <?php if ($prestamo['estado_del_prestamo'] === 'Prestado'): ?>
                                <div class="historial-button-container">
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="id_prestamo" value="<?php echo $prestamo['id_prestamo']; ?>">
                                        <input type="hidden" name="id_libro" value="<?php echo $prestamo['id_libro']; ?>">
                                        <button type="submit" class="btn-devolver">Devolver Libro</button>
                                    </form>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Imagen a la derecha -->
                        <div class="historial-book-frame">
                            <?php
                            $img = "../img_libros/{$prestamo['id_libro']}.jpg";
                            if (!file_exists($img)) $img = "../img_libros/default.jpg";
                            ?>
                            <img src="<?php echo $img; ?>" alt="<?php echo htmlspecialchars($prestamo['titulo']); ?>">
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php elseif (empty($prestamos_recientes) && !empty($prestamos_antiguos)): ?>
                <p style="text-align:center; width:100%; padding: 20px;">No hay registros para mostrar.</p>
            <?php else: ?>
                <p style="text-align:center; width:100%; padding: 20px;">No hay registros con este filtro.</p>
            <?php endif; ?>
        </div>
    </section>
</main>

<!-- Modal para mostrar detalles del libro del dropdown -->
<div id="bookModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 class="modal-title" id="modalTitle">Detalles del libro</h2>
            <button class="modal-close" onclick="closeModal()">&times;</button>
        </div>
        <div class="modal-body">
            <div class="modal-info">
                <div>
                    <label class="fecha-label">Título del libro</label>
                    <input type="text" class="historial-input" id="modalTitulo" readonly>
                </div>
                <div>
                    <label class="fecha-label">ID del libro</label>
                    <input type="text" class="historial-input" id="modalIdLibro" readonly>
                </div>
                <div>
                    <label class="fecha-label">Fecha de salida</label>
                    <div class="fecha-valor" id="modalFechaSalida"></div>
                </div>
                <div>
                    <label class="fecha-label">Fecha límite</label>
                    <div class="fecha-valor" id="modalFechaLimite"></div>
                </div>
                <div>
                    <label class="fecha-label">Estado</label>
                    <div class="historial-estado" id="modalEstado"></div>
                </div>
                <div class="historial-button-container" id="modalButtonContainer">
                </div>
            </div>
            <div class="modal-book-frame">
                <img id="modalBookImage" src="" alt="Libro">
            </div>
        </div>
    </div>
</div>

<script>
    const dropdownToggle = document.getElementById('dropdownToggle');
    const dropdownContent = document.getElementById('dropdownContent');
    const dropdownSearch = document.getElementById('dropdownSearch');
    const dropdownItems = document.getElementById('dropdownItems');
    const bookModal = document.getElementById('bookModal');

    if (dropdownToggle) {
        dropdownToggle.addEventListener('click', function(e) {
            e.stopPropagation();
            dropdownContent.classList.toggle('show');
        });

        dropdownSearch.addEventListener('input', function() {
            const searchValue = this.value.toLowerCase();
            const items = dropdownItems.querySelectorAll('.dropdown-item');
            
            items.forEach(item => {
                const title = item.getAttribute('data-title');
                if (title.includes(searchValue)) {
                    item.style.display = 'block';
                } else {
                    item.style.display = 'none';
                }
            });
        });

        // Agregar evento de clic a cada item del dropdown
        dropdownItems.addEventListener('click', function(e) {
            const item = e.target.closest('.dropdown-item');
            if (item) {
                showBookModal(item);
                dropdownContent.classList.remove('show');
            }
        });

        document.addEventListener('click', function(e) {
            if (!dropdownContent.contains(e.target) && !dropdownToggle.contains(e.target)) {
                dropdownContent.classList.remove('show');
            }
        });
    }

    function showBookModal(item) {
        const titulo = item.getAttribute('data-titulo');
        const idLibro = item.getAttribute('data-id-libro');
        const idPrestamo = item.getAttribute('data-id-prestamo');
        const fechaSalida = item.getAttribute('data-fecha-salida');
        const fechaLimite = item.getAttribute('data-fecha-limite');
        const estado = item.getAttribute('data-estado');

        // Llenar los campos del modal
        document.getElementById('modalTitulo').value = titulo;
        document.getElementById('modalIdLibro').value = idLibro;
        document.getElementById('modalFechaSalida').textContent = formatDate(fechaSalida);
        document.getElementById('modalFechaLimite').textContent = formatDate(fechaLimite);
        
        // Configurar el estado con la clase correcta
        const estadoDiv = document.getElementById('modalEstado');
        estadoDiv.textContent = estado;
        estadoDiv.className = 'historial-estado';
        
        if (estado === 'Devuelto') {
            estadoDiv.classList.add('estado-devuelto');
        } else if (estado === 'Prestado') {
            const hoy = new Date().toISOString().split('T')[0];
            if (fechaLimite < hoy) {
                estadoDiv.classList.add('estado-vencido');
            } else {
                estadoDiv.classList.add('estado-prestado');
            }
        }

        // Configurar la imagen
        const bookImage = document.getElementById('modalBookImage');
        bookImage.src = '../img_libros/' + idLibro + '.jpg';
        bookImage.onerror = function() {
            this.src = '../img_libros/default.jpg';
        };

        // Configurar el botón de devolución si es necesario
        const buttonContainer = document.getElementById('modalButtonContainer');
        buttonContainer.innerHTML = '';
        
        if (estado === 'Prestado') {
            buttonContainer.innerHTML = `
                <form method="POST" style="display: inline;">
                    <input type="hidden" name="id_prestamo" value="${idPrestamo}">
                    <input type="hidden" name="id_libro" value="${idLibro}">
                    <button type="submit" class="btn-devolver">Devolver Libro</button>
                </form>
            `;
        }

        // Mostrar el modal
        bookModal.classList.add('show');
    }

    function closeModal() {
        bookModal.classList.remove('show');
    }

    function formatDate(dateString) {
        const date = new Date(dateString);
        const day = String(date.getDate()).padStart(2, '0');
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const year = date.getFullYear();
        return day + '/' + month + '/' + year;
    }

    // Cerrar el modal al hacer clic fuera
    bookModal.addEventListener('click', function(e) {
        if (e.target === bookModal) {
            closeModal();
        }
    });
</script>
