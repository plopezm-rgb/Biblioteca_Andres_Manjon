<?php
        session_start();

        // Verificar si el usuario ha iniciado sesión
        if (!isset($_SESSION['id_usuario'])) {
            header("Location: ../index.html");
            exit;
        }

        // Encabezados para evitar el almacenamiento en caché
        header("Cache-Control: no-cache, no-store, must-revalidate");
        header("Pragma: no-cache");
        header("Expires: 0");

        // Conexión a la base de datos
        $conn = new mysqli("localhost", "root", "", "sistemabiblioteca");
        if ($conn->connect_error) {
            die("Conexión fallida: " . $conn->connect_error);
        }

        // Manejar la solicitud de eliminación de un libro (POST)
        if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['eliminar_libro'])) {
            $id_libro = $_POST['eliminar_libro'];
            
            // Paso 1: Eliminar préstamos asociados primero para mantener la integridad referencial
            $stmt_delete_prestamos = $conn->prepare("DELETE FROM prestamo WHERE id_libro = ?");
            $stmt_delete_prestamos->bind_param("i", $id_libro);
            $stmt_delete_prestamos->execute();
            $stmt_delete_prestamos->close();
            
            // Paso 2: Eliminar el libro de la base de datos
            $stmt_delete = $conn->prepare("DELETE FROM libro WHERE id_libro = ?");
            $stmt_delete->bind_param("i", $id_libro);
            $stmt_delete->execute();
            $stmt_delete->close();
            
            // Redirigir a la misma página para actualizar la lista
            header("Location: Gestiondelibros.php");
            exit();
        }

        // Inicializar variables para la búsqueda
        $libros = [];
        $busqueda = '';
        $tipo_busqueda = 'titulo';

        // Procesar la búsqueda si se reciben parámetros GET
        if (isset($_GET['busqueda']) || isset($_GET['tipo_busqueda'])) {
            $busqueda = $_GET['busqueda'] ?? '';
            $tipo_busqueda = $_GET['tipo_busqueda'] ?? 'titulo';

            // Consulta para buscar libros según el criterio seleccionado
            if ($tipo_busqueda == 'todos') {
                $sql = "SELECT * FROM libro";
                $stmt = $conn->prepare($sql);
                $stmt->execute();
                $result = $stmt->get_result();
                $libros = $result->fetch_all(MYSQLI_ASSOC);
                $stmt->close();
            } else {
                $sql = "SELECT * FROM libro WHERE ";
                // Construir la consulta según el tipo de búsqueda
                if ($tipo_busqueda == 'titulo') {
                    $sql .= "titulo LIKE ?";
                } elseif ($tipo_busqueda == 'isbn') {
                    $sql .= "isbn LIKE ?";
                } elseif ($tipo_busqueda == 'autor') {
                    $sql .= "autor LIKE ?";
                } elseif ($tipo_busqueda == 'ubicacion_por_colores') {
                    $sql .= "ubicacion_por_colores LIKE ?";
                }

                $stmt = $conn->prepare($sql);
                $param = "%" . $busqueda . "%";
                $stmt->bind_param("s", $param);
                $stmt->execute();
                $result = $stmt->get_result();
                $libros = $result->fetch_all(MYSQLI_ASSOC);
                $stmt->close();
            }
        }

        $conn->close();
        ?>
        <!DOCTYPE html>
        <html lang="es">
        <head>
            <meta charset="UTF-8">
            <title>Gestión de Libros - Biblioteca</title>
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <link rel="stylesheet" href="../css/estilo.css">
            <style>
                /* Estilos CSS internos para la página de gestión */
                body {
                    font-family: Arial, Helvetica, sans-serif;
                }
                .search-form {
                    max-width: 600px;
                    margin: 20px auto;
                    padding: 20px;
                    background-color: rgba(255, 255, 255, 0.9);
                    border-radius: 10px;
                    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
                }
                .search-form form {
                    display: flex;
                    flex-direction: column;
                    gap: 10px;
                }
                .search-form select, .search-form input[type="text"] {
                    padding: 10px;
                    border: 1px solid #ccc;
                    border-radius: 5px;
                }
                .search-form button {
                    padding: 10px;
                    background-color: #4CAF50;
                    color: white;
                    border: none;
                    border-radius: 5px;
                    cursor: pointer;
                }
                .libro-card {
                    background-color: rgba(255, 255, 255, 0.9);
                    margin: 20px auto;
                    max-width: 800px;
                    padding: 20px;
                    border-radius: 10px;
                    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
                }
                .acciones {
                    margin-top: 10px;
                }
                .btn-editar, .btn-eliminar {
                    padding: 5px 10px;
                    margin-right: 10px;
                    border: none;
                    border-radius: 5px;
                    cursor: pointer;
                    text-decoration: none;
                    display: inline-block;
                }
                .btn-editar {
                    background-color: #007bff;
                    color: white;
                }
                .btn-eliminar {
                    background-color: #dc3545;
                    color: white;
                }
                .btn-eliminar:hover {
                    transform: scale(1.05);
                    background-color: #dc3545;
                }
                .libro-content {
                    display: flex;
                    justify-content: space-between;
                    gap: 20px;
                    align-items: flex-start;
                }
                .libro-info {
                    flex: 1;
                }
                .libro-img {
                    width: 120px;
                    flex-shrink: 0;
                }
                .libro-img img {
                    width: 100%;
                    aspect-ratio: 3 / 4;
                    object-fit: cover;
                    border-radius: 6px;
                    box-shadow: 0 4px 6px rgba(0,0,0,0.15);
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
                <h2 id="title">Gestión de libros/Panel de Admin</h2>
                <section class="buttons">
                    <img src="../img/help.png" class="head-foto">
                    <button id="helpBtn">Necesito ayuda</button>
                </section>
            </section>
        
        <main>
            <div class="search-form">
                <a href="indexadmin.html" class="btn-volver">← Volver</a>
                <h2>Buscar Libro</h2>
                <form method="GET">
                    <select name="tipo_busqueda" onchange="this.form.submit()">
                        <option value="titulo" <?= ($tipo_busqueda == 'titulo') ? 'selected' : ''; ?>>Título</option>
                        <option value="isbn" <?= ($tipo_busqueda == 'isbn') ? 'selected' : ''; ?>>ISBN</option>
                        <option value="autor" <?= ($tipo_busqueda == 'autor') ? 'selected' : ''; ?>>Autor</option>
                        <option value="ubicacion_por_colores" <?= ($tipo_busqueda == 'ubicacion_por_colores') ? 'selected' : ''; ?>>Color</option>
                        <option value="todos" <?= ($tipo_busqueda == 'todos') ? 'selected' : ''; ?>>Todos</option>
                    </select>

                    <?php if ($tipo_busqueda == 'ubicacion_por_colores'): ?>
                        <select name="busqueda" required>
                            <option value="">-- Todos los colores --</option>
                            <option value="red"     <?= ($busqueda == 'red') ? 'selected' : ''; ?>>Rojo</option>
                            <option value="pink"     <?= ($busqueda == 'pink') ? 'selected' : ''; ?>>Rosa</option>
                            <option value="purple"   <?= ($busqueda == 'purple') ? 'selected' : ''; ?>>Morado</option>
                            <option value="yellow" <?= ($busqueda == 'yellow') ? 'selected' : ''; ?>>Amarillo</option>
                            <option value="brown"   <?= ($busqueda == 'brown') ? 'selected' : ''; ?>>Marrón</option>
                            <option value="white"   <?= ($busqueda == 'white') ? 'selected' : ''; ?>>Blanco</option>
                            <option value="light_blue"     <?= ($busqueda == 'light_blue') ? 'selected' : ''; ?>>Azul</option>
                            <option value="black"    <?= ($busqueda == 'black') ? 'selected' : ''; ?>>Negro</option>
                        </select>
                    <?php elseif ($tipo_busqueda != 'todos'): ?>
                        <input 
                            type="text" 
                            name="busqueda" 
                            placeholder="Ingrese el término de búsqueda" 
                            value="<?= htmlspecialchars($busqueda); ?>" 
                            required
                        >
                    <?php endif; ?>

                    <button type="submit">Buscar</button>
                </form>
            </div>

            <?php if (!empty($libros)): ?>
                <?php foreach ($libros as $libro): 
                    $img = "../img_libros/" . $libro['id_libro'] . ".jpg";
                    // Usar imagen por defecto si no existe la imagen del libro
                    if (!file_exists($img)) {
                        $img = "../img_libros/default.jpg";
                    }
                    ?>
                    <div class="libro-card">
                        <div class="libro-content">
                            <div class="libro-info">
                                <h3><?php echo htmlspecialchars($libro['titulo']); ?></h3>
                                <p><strong>ISBN:</strong> <?php echo htmlspecialchars($libro['isbn'] ?? 'N/A'); ?></p>
                                <p><strong>Autor:</strong> <?php echo htmlspecialchars($libro['autor'] ?? 'N/A'); ?></p>
                                <p><strong>Color:</strong> <?php echo htmlspecialchars($libro['ubicacion_por_colores'] ?? 'N/A'); ?></p>
                                <p><strong>Estado:</strong> <?php echo htmlspecialchars($libro['estado_de_actividad'] ?? 'N/A'); ?></p>
                            </div>

                            <div class="libro-img">
                                <img src="<?php echo $img; ?>" alt="<?php echo htmlspecialchars($libro['titulo']); ?>">
                            </div>
                        </div>

                        <div class="acciones">
                            <a href="Editarlibro.php?id=<?php echo $libro['id_libro']; ?>" class="btn-editar">Editar</a>
                            <form method="POST" style="display:inline;" onsubmit="return confirm('¿Estás seguro de que quieres eliminar este libro?');">
                                <input type="hidden" name="eliminar_libro" value="<?php echo $libro['id_libro']; ?>">
                                <button type="submit" class="btn-eliminar">Eliminar</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php elseif (isset($_GET['busqueda'])): ?>
                <div class="libro-card">
                    <p>No se encontraron libros con ese criterio de búsqueda.</p>
                </div>
            <?php endif; ?>
        </main>

        <footer class="estilo-footer">
            <section class="estilo-footer-container">
                <section class="estilo-footer-logo">
                    <img src="../img/footerlogo.png" alt="Logo" class="estilo-logo-img">
                </section>
                <section class="estilo-footer-column">
                    <h3 class="estilo-footer-title">SOBRE NOSOTROS</h3>
                    <ul class="estilo-footer-list">
                        <li>
                            <a href="https://ceipandresmanjon.catedu.aragon.es/" class="estilo-footer-link">
                                Web del centro
                            </a>
                        </li>
                    </ul>
                </section>
                <section class="estilo-footer-column">
                    <h3 class="estilo-footer-title">DIRECCIÓN</h3>
                    <p class="estilo-footer-text">
                        CEIP Andrés Manjón.<br>
                        C/ Delicias, 90, 50017, Zaragoza
                    </p>
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