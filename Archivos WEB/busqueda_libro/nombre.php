<!DOCTYPE html>
<!-- Declaración del tipo de documento HTML5 -->
<html lang="ES">
<head>
  <!-- Codificación de caracteres -->
  <meta charset="UTF-8" />

  <!-- Configuración para que la página sea responsive -->
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>

  <!-- Título que aparece en la pestaña del navegador -->
  <title>Andres Manjon - Préstamo libro</title>

  <!-- Hoja de estilos principal del proyecto -->
  <link rel="stylesheet" href="../css/estilo.css">
</head>

<body>
  <!-- ================= HEADER ================= -->
  <!-- Cabecera principal de la página -->
  <section class="header">
    <!-- Logo del centro -->
    <img src="../img/logoA.png" alt="Logo">

    <!-- Nombre del centro y sección -->
    <section class="text">
      <h4 id="header1">CEIP Andrés Manjón</h4>
      <h4 id="header2">Biblioteca</h4>
    </section>

    <!-- Título de la página -->
    <h2 id="title">Préstamo de Libros</h2>

    <!-- Contenedor de botones del header -->
    <section class="buttons">
      <!-- Botón que redirige a la lista de libros -->
      <button id="listBtn" class="head-btn">
        <img src="../img/lista.png" alt="Lista">
        <a href="../librolista/lista.php">
        <span id="listBtnTxt">Lista de libro</span></a>
      </button>

      <!-- Botón de ayuda -->
      <img src="../img/help.png" class="head-foto" alt="Ayuda">
      <button id="helpBtn">I need help</button>

      <!-- Selector de idioma -->
      <img src="../img/idioma.png" class="head-foto" alt="Idioma">
      <select id="langSelect" class="form-select">
        <option value="ES">Español</option>
        <option value="EN">English</option>
        <option value="FR">Français</option>
        <option value="AL">عربي</option>
        <option value="CN">中文(简体中文)</option>
      </select>
    </section>
  </section>

  <!-- ================= CONTENIDO PRINCIPAL ================= -->
  <main class="prestamo-main">
    <!-- Botón flotante para acceder al historial -->
    <a href="historia.html" class="historia-fab" aria-label="Historia">
      <img src="../img/historia.png" alt="Historia">
      <span id="historiaTxt">Historia</span>
    </a>

    <!-- Tarjeta central del préstamo -->
    <section class="prestamo-card">
      <!-- Título de la tarjeta -->
      <section class="prestamo-title">
        <img src="../img/prestamo.png" alt="Libro" class="prestamo-title-icon">
        <span id="prestamoTitleTxt">Préstamo libro</span>
      </section>

      <!-- ================= PESTAÑAS ================= -->
      <!-- Permiten cambiar el modo de búsqueda -->
      <section class="prestamo-tabs" role="tablist" aria-label="Buscar por">
        <!-- Búsqueda por nombre -->
        <button class="tab-btn is-active" id="tabNombre" role="tab" aria-selected="true" data-target="nombre">
          <span id="tabNombreTxt">Nombre</span>
        </button>
        <!-- Búsqueda por ISBN -->
        <button class="tab-btn" id="tabISBN" role="tab" aria-selected="false" data-target="isbn">
          <span id="tabISBNTxt">ISBN</span>
        </button>
      </section>

      <!-- ================= CAJA DE BÚSQUEDA ================= -->
      <section class="prestamo-box">
        <!-- Ilustración izquierda -->
        <img src="../img/hand-left.png" alt="" class="prestamo-illu prestamo-illu-left">

        <!-- Formulario de búsqueda -->
        <section class="prestamo-form">
          <input
            id="searchInput"
            type="text"
            placeholder="Introducir nombre de libro"
            aria-label="Buscar libro"
          />
          <button id="searchBtn" type="button">Buscar</button>
        </section>

        <!-- Ilustración derecha -->
        <img src="../img/hand-right.png" alt="" class="prestamo-illu prestamo-illu-right">
      </section>
    </section>

    <!-- ================= CONSEJOS ================= -->
    <section class="tips">
      <span class="tips-bulb" aria-hidden="true">💡</span>
      <p id="tipsTxt">
        Tips: Es la forma de saber cómo se llama un libro, igual que tú tienes tu propio nombre.
        Lo puedes ver en la portada con letras grandes y también en el lomo del libro cuando está en la estantería.
        Así es más fácil encontrarlo.
      </p>
    </section>
  </main>

  <!-- ================= FOOTER ================= -->
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

  <!-- ================= JAVASCRIPT ================= -->
  <script>
   // Objeto que contiene todos los textos traducidos por idioma
   const texts = {
    ES: {
      header1: "CEIP Andrés Manjón",
      header2: "Biblioteca",
      title: "Préstamo de Libros",
      help: "I need help",
      list: "Lista de libro",
      historia: "Historia",
      prestamoTitle: "Préstamo libro",
      tabNombre: "Nombre",
      tabISBN: "ISBN",
      phNombre: "Introducir nombre de libro",
      phISBN: "Introducir ISBN del libro",
      buscar: "Buscar",
      tipsNombre: "Tips: Es la forma de saber cómo se llama un libro, igual que tú tienes tu propio nombre. Lo puedes ver en la portada con letras grandes y también en el lomo del libro cuando está en la estantería. Así es más fácil encontrarlo.",
      tipsISBN: "Tips: El ISBN es un número que identifica un libro. Suele estar junto al código de barras en la contraportada. Si lo escribes, encontrarás el libro más rápido."
    },
    EN: {
      header1: "CEIP Andrés Manjón",
      header2: "Library",
      title: "Loan Books",
      help: "I need help",
      list: "Book list",
      historia: "History",
      prestamoTitle: "Book loan",
      tabNombre: "Name",
      tabISBN: "ISBN",
      phNombre: "Enter book name",
      phISBN: "Enter book ISBN",
      buscar: "Search",
      tipsNombre: "Tip: This is the book's name. You can see it on the cover in big letters and on the spine on the shelf. It helps you find it easily.",
      tipsISBN: "Tip: ISBN is a number that identifies a book. It is usually near the barcode on the back cover. Using it is faster."
    },
    FR: {
      header1: "CEIP Andrés Manjón",
      header2: "Bibliothèque",
      title: "Prêt de livres",
      help: "J'ai besoin d'aide",
      list: "Liste des livres",
      historia: "Histoire",
      prestamoTitle: "Emprunter un livre",
      tabNombre: "Nom",
      tabISBN: "ISBN",
      phNombre: "Entrer le nom du livre",
      phISBN: "Entrer l'ISBN",
      buscar: "Chercher",
      tipsNombre: "Conseil: C'est le nom du livre. Vous pouvez le voir sur la couverture en grosses lettres et sur le dos du livre sur l'étagère.",
      tipsISBN: "Conseil: L'ISBN est un numéro qui identifie un livre. Il se trouve généralement près du code-barres au dos du livre."
    },
    AL: {
      header1: "CEIP Andrés Manjón",
      header2: "المكتبة",
      title: "إعارة الكتب",
      help: "أحتاج مساعدة",
      list: "قائمة الكتب",
      historia: "سجل",
      prestamoTitle: "استعارة كتاب",
      tabNombre: "اسم",
      tabISBN: "ISBN",
      phNombre: "أدخل اسم الكتاب",
      phISBN: "أدخل رقم ISBN",
      buscar: "بحث",
      tipsNombre: "نصيحة: هذا هو اسم الكتاب. يمكنك رؤيته على الغلاف بحروف كبيرة وعلى كعب الكتاب في الرف.",
      tipsISBN: "نصيحة: الرقم الدولي المعياري للكتاب (ISBN) هو رقم يحدد هوية الكتاب. عادة ما يكون بجوار الرمز الشريطي."
    },
    CN: {
      header1: "CEIP Andrés Manjón",
      header2: "图书馆",
      title: "借阅书籍",
      help: "我需要帮助",
      list: "书籍列表",
      historia: "历史记录",
      prestamoTitle: "借书",
      tabNombre: "书名",
      tabISBN: "ISBN",
      phNombre: "输入书名",
      phISBN: "输入书籍 ISBN",
      buscar: "搜索",
      tipsNombre: "提示：就像你有名字一样，书也有名字。你可以在封面上看到大字写的名字，或者在书架上的书脊上看到。这样更容易找到它。",
      tipsISBN: "提示：ISBN 是识别书籍的号码。通常位于封底条形码旁边。输入它可以更快找到书。"
    }
  };

    // Referencias a los elementos del DOM
    const els = {
      header1: document.getElementById("header1"),
      header2: document.getElementById("header2"),
      title: document.getElementById("title"),
      helpBtn: document.getElementById("helpBtn"),
      listBtnTxt: document.getElementById("listBtnTxt"),
      historiaTxt: document.getElementById("historiaTxt"),
      prestamoTitleTxt: document.getElementById("prestamoTitleTxt"),
      tabNombreTxt: document.getElementById("tabNombreTxt"),
      tabISBNTxt: document.getElementById("tabISBNTxt"),
      searchInput: document.getElementById("searchInput"),
      searchBtn: document.getElementById("searchBtn"),
      tipsTxt: document.getElementById("tipsTxt"),
      langSelect: document.getElementById("langSelect"),
      tabNombre: document.getElementById("tabNombre"),
      tabISBN: document.getElementById("tabISBN"),
    };

    // Modo actual de búsqueda
    let currentMode = "nombre";

    // Aplica los textos según el idioma seleccionado
    function applyLang(lang) {
      const t = texts[lang] || texts.ES;
      els.header1.textContent = t.header1;
      els.header2.textContent = t.header2;
      els.title.textContent = t.title;
      els.helpBtn.textContent = t.help;
      els.listBtnTxt.textContent = t.list;
      els.historiaTxt.textContent = t.historia;
      els.prestamoTitleTxt.textContent = t.prestamoTitle;
      els.tabNombreTxt.textContent = t.tabNombre;
      els.tabISBNTxt.textContent = t.tabISBN;
      els.searchBtn.textContent = t.buscar;
      els.searchInput.placeholder = currentMode === "nombre" ? t.phNombre : t.phISBN;
      els.tipsTxt.textContent = currentMode === "nombre" ? t.tipsNombre : t.tipsISBN;
    }

    // Cambia entre búsqueda por nombre o ISBN
    function setMode(mode) {
      currentMode = mode;
      const lang = els.langSelect.value || "ES";
      const t = texts[lang] || texts.ES;
      els.tabNombre.classList.toggle("is-active", mode==="nombre");
      els.tabISBN.classList.toggle("is-active", mode==="isbn");
      els.tabNombre.setAttribute("aria-selected", String(mode==="nombre"));
      els.tabISBN.setAttribute("aria-selected", String(mode==="isbn"));
      els.searchInput.placeholder = mode==="nombre"? t.phNombre : t.phISBN;
      els.tipsTxt.textContent = mode==="nombre"? t.tipsNombre : t.tipsISBN;
      els.searchInput.value = "";
      els.searchInput.focus();
    }

    // Evento: cambio de idioma
    els.langSelect.addEventListener("change", () => applyLang(els.langSelect.value));

    // Eventos: cambio de pestaña
    els.tabNombre.addEventListener("click", () => setMode("nombre"));
    els.tabISBN.addEventListener("click", () => setMode("isbn"));

    // Evento: botón buscar
    els.searchBtn.addEventListener("click", () => {
      const value = els.searchInput.value.trim();
      if(!value) return alert("Por favor, introduce un valor.");
      const params = new URLSearchParams({ mode: currentMode, q: value });
      window.location.href = `resultado.php?${params.toString()}`;
    });

    // Permitir buscar pulsando Enter
    els.searchInput.addEventListener("keypress", (e) => { if(e.key==="Enter") els.searchBtn.click(); });

    // Inicialización por defecto
    applyLang("ES");
    setMode("nombre");
  </script>
</body>
</html>
