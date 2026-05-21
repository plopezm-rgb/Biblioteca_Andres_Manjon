
<!DOCTYPE html>
<html lang="ES">
<head>
    <meta charset="UTF-8">
    <title>Lista de Libros - Andrés Manjón</title>
    <link rel="stylesheet" href="../css/estilo.css">
</head>
<body>
    <?php
    session_start(); // Inicia sesión para manejar usuarios y permisos
    
    // -------------------------
    // Detectar si es modo invitado (solo lectura)
    // -------------------------
    $is_guest = isset($_GET['guest']) && $_GET['guest'] == '1';

    if (!$is_guest) {
        // -------------------------
        // Validar sesión de usuario
        // -------------------------
        if (!isset($_SESSION['id_usuario']) || !isset($_SESSION['id_rol'])) {
            $_SESSION['error'] = "Acceso denegado: No ha iniciado sesión correctamente";
            header("Location: ../index.php"); // Redirige a login
            exit();
        }

        // -------------------------
        // Validar rol: solo estudiantes (1) y miniprofesor (2) pueden acceder
        // -------------------------
        $id_rol = $_SESSION['id_rol'] ?? null;
        if ($id_rol !== 1 && $id_rol !== 2 ) {
            $_SESSION['error'] = "Acceso denegado: Su rol no tiene permiso para acceder a esta página";
            header("Location: ../index.php");
            exit();
        }

        // -------------------------
        // Validar id_alumnado (obligatorio para estudiantes y miniprofesor)
        // -------------------------
        if (!isset($_SESSION['id_alumnado']) || empty($_SESSION['id_alumnado'])) {
            $_SESSION['error'] = "Error: No se encontró el ID del alumno en la sesión";
            header("Location: ../index.php");
            exit();
        }
    } else {
        // -------------------------
        // Modo invitado: asignar rol visual, sin sesión
        // -------------------------
        $id_rol = 1; // Para traducciones/visualización
    }

    // -------------------------
    // Mostrar mensaje de error si existe
    // -------------------------
    $error_message = '';
    if ($is_guest) {
        // Invitado no muestra errores de sesión previos
        if (isset($_SESSION['error'])) unset($_SESSION['error']);
        $id_rol = isset($_GET['id_rol']) ? (int)$_GET['id_rol'] : 0;
    } else {
        if (isset($_SESSION['error']) && $_SESSION['error']) {
            $error_message = $_SESSION['error'];
            unset($_SESSION['error']);
        }
    }

    // -------------------------
    // Preparar fragmentos GET para enlaces en modo invitado
    // -------------------------
    $guest_prefix = $is_guest ? ('?guest=1&id_rol=' . urlencode($id_rol)) : '';
    $guest_suffix = $is_guest ? ('&guest=1&id_rol=' . urlencode($id_rol)) : '';
    ?>
    <?php if ($error_message): ?>
    <!-- Mostrar alerta si hay mensaje de error -->
    <script>
        window.addEventListener('DOMContentLoaded', function() {
            alert('Error: <?php echo htmlspecialchars(addslashes($error_message)); ?>');
        });
    </script>
    <?php endif; ?>
    <!-- Header -->
    <section class="header">
        <img src="../img/logoA.png">
        <section class="text">
            <h4 id="header1">CEIP Andrés Manjón</h4>
            <h4 id="header2">Biblioteca</h4>
        </section>
        <h2 id="title">Lista de libros</h2>
        <section class="buttons">
            <?php if (!$is_guest): ?>
            <!-- Botón de prestar libro solo para usuarios logueados -->
            <img src="../img/presta.png" class="head-foto">
            <button><a href="../busqueda_libro/nombre.php<?php echo $guest_prefix; ?>" class="presta-text">Presta Libro con nombre/isbn</a></button>
            <?php endif; ?>
            <img src="../img/help.png" class="head-foto">
            <button id="helpBtn">I need help</button>
            <img src="../img/idioma.png" class="head-foto">
            <!-- Selector de idioma -->
            <select id="langSelect" class="form-select">
                <option value="ES">Español</option>
                <option value="EN">English</option>
                <option value="FR">Français</option>
                <option value="AL">عربي</option>
                <option value="CN">中文(简体中文)</option>
            </select>
        </section>
    </section>

    <!-- Main Content -->
    <main class="lista-main">
        <?php if (!$is_guest): ?>
        <!-- Botón de historial solo para usuarios logueados -->
        <a href="../prestamo/historial.php" class="historia-fab" aria-label="Historia">
            <img src="../img/historia.png" alt="Historia">
            <span id="historiaTxt">Historial</span>
        </a>
        <?php endif; ?>
        <!-- Sidebar -->
        <aside class="sidebar">
            <!-- Sección de categorías por color -->
            <section class="sidebar-section">
                <h3 class="sidebar-title">Categorías</h3>
                <ul class="sidebar-list">
                    <li><a href="lista.php<?php echo $guest_prefix; ?>" class="sidebar-link color-todos">Todos</a></li>
                    <li><a href="lista.php?color=pink<?php echo $guest_suffix; ?>" class="sidebar-link color-pink">Rosa</a></li>
                    <li><a href="lista.php?color=purple<?php echo $guest_suffix; ?>" class="sidebar-link color-purple">Morado</a></li>
                    <li><a href="lista.php?color=light_blue<?php echo $guest_suffix; ?>" class="sidebar-link color-light-blue">Azul</a></li>
                    <li><a href="lista.php?color=red<?php echo $guest_suffix; ?>" class="sidebar-link color-red">Rojo</a></li>
                    <li><a href="lista.php?color=brown<?php echo $guest_suffix; ?>" class="sidebar-link color-brown">Marron</a></li>
                    <li><a href="lista.php?color=yellow<?php echo $guest_suffix; ?>" class="sidebar-link color-yellow">Amarillo</a></li>
                    <li><a href="lista.php?color=orange<?php echo $guest_suffix; ?>" class="sidebar-link color-orange">Naranja</a></li>
                    <li><a href="lista.php?color=black<?php echo $guest_suffix; ?>" class="sidebar-link color-black">Negro</a></li>
                    <li><a href="lista.php?color=white<?php echo $guest_suffix; ?>" class="sidebar-link color-white">Blanco</a></li>
                    <li><a href="lista.php?color=green<?php echo $guest_suffix; ?>" class="sidebar-link color-green">Verde</a></li>
                </ul>
            </section>
        </aside>

        <!-- Content Area -->
        <section class="content-area">
            <section class="section-recomendados">
                <section class="section-header">
                    <img src="../img/good.png" alt="Trophy" class="trophy-icon">
                    <h2 class="section-title">Todos libros</h2>
                </section>
                <!-- Barra de búsqueda -->
                <form method="GET" action="lista.php" class="search-bar">
                    <input type="text" name="query" placeholder="Nombre" class="search-input"
                        value="<?php echo isset($_GET['query']) ? htmlspecialchars($_GET['query']) : ''; ?>">
                    <input type="hidden" name="color" value="<?php echo isset($_GET['color']) ? htmlspecialchars($_GET['color']) : ''; ?>">
                    <input type="hidden" name="guest" value="<?php echo $is_guest ? '1' : ''; ?>">
                    <input type="hidden" name="id_rol" value="<?php echo $id_rol; ?>">
                    <button type="submit" class="search-btn">Buscar</button>
                </form>

                <!-- Grid de libros -->
                <section class="books-grid">
                    <?php include 'libros.php'; // Incluir script que genera tarjetas de libros ?>
                </section>
            </section>
        </section>
    </main>

    <!-- Botón de logout -->
    <section class="sidebar-logout" style="margin-top: auto; padding: 20px;">
        <button class="sidebar-link logout-btn" onclick="confirmLogout()">Cerrar sesión</button>
    </section>

    <script>
    function confirmLogout() {
        if (confirm("¿Estás seguro de que deseas cerrar sesión?")) {
            // Redirige al script PHP que destruye la sesión
            window.location.href = "../librolista/logout.php";
        }
    }
    </script>

    <!-- Footer -->
    <footer class="estilo-footer">
        <section class="estilo-footer-container">
            <section class="estilo-footer-logo">
                <img src="../img/footerlogo.png" alt="Logo" class="estilo-logo-img">
            </section>
            <section class="estilo-footer-column">
                <h3 class="estilo-footer-title">SOBRE NOSOTROS</h3>
                <ul class="estilo-footer-list">
                    <li><a href="https://ceipandresmanjon.catedu.es/" class="estilo-footer-link">Web del centro</a></li>
                </ul>
            </section>
            <section class="estilo-footer-column">
                <h3 class="estilo-footer-title">DIRECCIÓN</h3>
                <p class="estilo-footer-text">CEIP Andrés Manjón. <br>C/ Delicias, 90, 50017, Zaragoza</p>
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

    <!-- Script para traducción de la página -->

    <script>
    const texts = {
       ES: { 
            header2: "Biblioteca", 
            title: "Lista de libros", 
            button: "Necesito ayuda",

            presta: "Presta libro por nombre / ISBN",

            tiposTitle: "Categorías",

            color_pink: "Rosa",
            color_purple: "Morado",
            color_light_blue: "Azul",
            color_red: "Rojo",
            color_brown: "Marrón",
            color_yellow: "Amarillo",
            color_orange: "Naranja",
            color_black: "Negro",
            color_white: "Blanco",
            color_green: "Verde",

            todosLibros: "Todos los libros",
            searchPlaceholder: "Nombre"
        },

        EN: { 
            header2: "Library",
            title: "Book List",
            button: "I need help",

            presta: "Lend book by name / ISBN",

            tiposTitle: "Book types",

            color_pink: "Pink",
            color_purple: "Purple",
            color_light_blue: "Blue",
            color_red: "Red",
            color_brown: "Brown",
            color_yellow: "Yellow",
            color_orange: "Orange",
            color_black: "Black",
            color_white: "White",
            color_green: "Green",

            todosLibros: "All books",
            searchPlaceholder: "Book name"
        },


       FR: {  
            header2: "Bibliothèque",
            title: "Liste des livres",
            button: "J’ai besoin d’aide",

            presta: "Prêter un livre par nom / ISBN",

            tiposTitle: "Types de livres",

            color_pink: "Rose",
            color_purple: "Violet",
            color_light_blue: "Bleu",
            color_red: "Rouge",
            color_brown: "Marron",
            color_yellow: "Jaune",
            color_orange: "Orange",
            color_black: "Noir",
            color_white: "Blanc",
            color_green: "Vert",

            todosLibros: "Tous les livres",
            searchPlaceholder: "Nom du livre"
        },

        AL: { 
            header2: "المكتبة",
            title: "قائمة الكتب",
            button: "أحتاج مساعدة",

            presta: "إعارة كتاب بالاسم أو رقم ISBN",

            tiposTitle: "أنواع الكتب",

            color_pink: "وردي",
            color_purple: "بنفسجي",
            color_light_blue: "أزرق",
            color_red: "أحمر",
            color_brown: "بني",
            color_yellow: "أصفر",
            color_orange: "برتقالي",
            color_black: "أسود",
            color_white: "أبيض",
            color_green: "أخضر",

            todosLibros: "جميع الكتب",
            searchPlaceholder: "اسم الكتاب"
        },

        CN: { 
            header2: "图书馆",
            title: "图书列表",
            button: "我需要帮助",

            presta: "按书名 / ISBN 借书",

            tiposTitle: "图书类型",

            color_pink: "粉色",
            color_purple: "紫色",
            color_light_blue: "蓝色",
            color_red: "红色",
            color_brown: "棕色",
            color_yellow: "黄色",
            color_orange: "橙色",
            color_black: "黑色",
            color_white: "白色",
            color_green: "绿色",

            todosLibros: "全部图书",
            searchPlaceholder: "书名"
        }
    };


    const header2 = document.getElementById("header2");
    const title = document.getElementById("title");
    const helpBtn = document.getElementById("helpBtn");
    const langSelect = document.getElementById("langSelect");

    const tiposTitle = document.querySelector(".sidebar-section:nth-child(1) .sidebar-title");
    const recomendadosTitle = document.querySelector(".section-recomendados .section-title");
    const searchInput = document.querySelector(".search-input");
    const searchBtns = document.querySelectorAll(".search-btn");
    const prestaText = document.querySelector(".presta-text");

    const colorPink = document.querySelector(".color-pink");
    const colorPurple = document.querySelector(".color-purple");
    const colorLightBlue = document.querySelector(".color-light-blue");
    const colorRed = document.querySelector(".color-red");
    const colorBrown = document.querySelector(".color-brown");
    const colorYellow = document.querySelector(".color-yellow");
    const colorOrange = document.querySelector(".color-orange");
    const colorBlack = document.querySelector(".color-black");
    const colorWhite = document.querySelector(".color-white");
    const colorGreen = document.querySelector(".color-green");

    function translatePage(lang) {
        header2.textContent = texts[lang].header2;
        title.textContent = texts[lang].title;
        helpBtn.textContent = texts[lang].button;

        tiposTitle.textContent = texts[lang].tiposTitle;
        recomendadosTitle.textContent = texts[lang].todosLibros;

        searchInput.placeholder = texts[lang].searchPlaceholder;
        searchBtns[0].textContent = "Buscar";

        prestaText.textContent = texts[lang].presta;

        colorPink.textContent = texts[lang].color_pink;
        colorPurple.textContent = texts[lang].color_purple;
        colorLightBlue.textContent = texts[lang].color_light_blue;
        colorRed.textContent = texts[lang].color_red;
        colorBrown.textContent = texts[lang].color_brown;
        colorYellow.textContent = texts[lang].color_yellow;
        colorOrange.textContent = texts[lang].color_orange;
        colorBlack.textContent = texts[lang].color_black;
        colorWhite.textContent = texts[lang].color_white;
        colorGreen.textContent = texts[lang].color_green;
    }

    langSelect.addEventListener("change", () => translatePage(langSelect.value));
    translatePage(langSelect.value);
</script>
</body>
</html>
