<?php
session_start();


$error_message = '';
if (isset($_SESSION['error']) && $_SESSION['error']) {
    $error_message = $_SESSION['error'];
    unset($_SESSION['error']);
}
?>
<!DOCTYPE html>
<html lang="ES">
<head>
    <meta charset="UTF-8">
    <title>Andres Manjon - Login</title>
    <link rel="stylesheet" href="css/estilo.css">
    <style>
        body {
            font-family: Arial, sans-serif;
        }
    </style>
</head>
<body>
    <?php if ($error_message): ?>
    <script>
        window.addEventListener('DOMContentLoaded', function() {
            alert('Error: <?php echo htmlspecialchars(addslashes($error_message)); ?>');
        });
    </script>
    <?php endif; ?>
    <!-- Header -->
    <section class="header">
        <img src="img/logoA.png">
        <section class="text">
            <h4 id="header1">CEIP Andrés Manjón</h4>
            <h4 id="header2">Biblioteca</h4>
        </section>
        <h2 id="title">Préstamo de Libros</h2>

        <section class="buttons">
            <img src="img/help.png" class="head-foto">
            <button id="helpBtn">I need help</button>
            <img src="img/idioma.png" class="head-foto">
            
            <select id="langSelect" class="form-select">
                <option value="ES">Español</option>
                <option value="EN">English</option>
                <option value="FR">Français</option>
                <option value="AL">عربي</option>
                <option value="CN">中文(简体中文)</option>
            </select>
        </section>
    </section>

    <!-- Login Form -->
    <main>
        <section class="login-box">
            <img src="img/usuario-icon.png" alt="Usuario" class="user-icon">

            <form id="loginForm" method="POST" action="login.php">
                <section class="form-row">
                    <img src="img/usuario.png" alt="Usuario">
                    <input type="text" id="inputName" name="username" placeholder="Nombre de usuario">
                </section>

                <section class="form-row">
                    <img src="img/contrasena.png" alt="Contraseña">
                    <input type="password" id="inputPass" name="contrasenia" placeholder="Contraseña">
                </section>

                <button type="submit" id="submitBtn">Iniciar</button>

                <button id="alumno" type="button" onclick="window.location.href='librolista/lista.php?guest=1&id_rol=0';">Soy Alumno</button>
                
            </form>
        </section>
    </main>

    <!-- Footer -->
    <footer class="estilo-footer">
        <section class="estilo-footer-container">
            <section class="estilo-footer-logo">
                <img src="img/footerlogo.png" alt="Logo" class="estilo-logo-img">
            </section>

            <section class="estilo-footer-column">
                <h3 id="footerAbout" class="estilo-footer-title">SOBRE NOSOTROS</h3>
                <ul class="estilo-footer-list">
                    <li><a id="footerLink" href="https://ceipandresmanjon.catedu.es/" class="estilo-footer-link" >Web del centro</a></li>
                </ul>
            </section>

            <section class="estilo-footer-column">
                <h3 id="footerAddress" class="estilo-footer-title">DIRECCIÓN</h3>
                <p id="footerAddressText" class="estilo-footer-text">CEIP Andrés Manjón. <br>C/ Delicias, 90, 50017, Zaragoza</p>
            </section>

            <section class="estilo-footer-column">
                <h3 id="footerPhone" class="estilo-footer-title">TELÉFONO</h3>
                <p id="footerPhoneText" class="estilo-footer-text">976 331 728</p>
                
                <h3 id="footerEmail" class="estilo-footer-title estilo-mt">E-MAIL</h3>
                <p id="footerEmailText" class="estilo-footer-text">cpamanzaragoza@educa.aragon.es</p>
            </section>

            <section class="estilo-footer-column">
                <h3 id="footerHours" class="estilo-footer-title">HORARIO</h3>
                <p id="footerHoursText" class="estilo-footer-text">De lunes a viernes de 9 a 14 h</p>
            </section>

            <section class="estilo-footer-right">
                <a href="https://educa.aragon.es/">
                    <img src="img/gobierno.png" alt="Gobierno de Aragón" class="estilo-gobierno-logo">
                </a>
                <section class="estilo-social-icons">
                    <a href="https://www.instagram.com/ceip_andresmanjon/" class="estilo-social-link">
                        <img src="img/instagram.png" alt="Instagram" class="estilo-social-icon">
                    </a>
                </section>
            </section>
        </section>
    </footer>

    <!-- JS para traducciones -->
    <script>
    const texts = {
        ES: { 
            header1: "CEIP Andrés Manjón", 
            header2: "Biblioteca", 
            title: "Préstamo de Libros", 
            helpBtn: "Necesito ayuda",
            placeholderName: "Nombre y apellido de alumno",
            placeholderPass: "Contraseña",
            btnStudent: "Soy Alumno",
            btnLogin: "Iniciar",
            footerAbout: "SOBRE NOSOTROS",
            footerLink: "Web del centro",
            footerAddress: "DIRECCIÓN",
            footerAddressText: "CEIP Andrés Manjón. <br>C/ Delicias, 90, 50017, Zaragoza",
            footerPhone: "TELÉFONO",
            footerPhoneText: "976 331 728",
            footerEmail: "E-MAIL",
            footerEmailText: "cpamanzaragoza@educa.aragon.es",
            footerHours: "HORARIO",
            footerHoursText: "De lunes a viernes de 9 a 14 h"
        },
        EN: { 
            header1: "CEIP Andrés Manjón", 
            header2: "Library", 
            title: "Book Loan", 
            helpBtn: "I need help",
            placeholderName: "Student Name and Surname",
            placeholderPass: "Password",
            btnStudent: "I am a Student",
            btnLogin: "Login",
            footerAbout: "ABOUT US",
            footerLink: "School website",
            footerAddress: "ADDRESS",
            footerAddressText: "CEIP Andrés Manjón. <br>C/ Delicias, 90, 50017, Zaragoza",
            footerPhone: "PHONE",
            footerPhoneText: "976 331 728",
            footerEmail: "E-MAIL",
            footerEmailText: "cpamanzaragoza@educa.aragon.es",
            footerHours: "OPENING HOURS",
            footerHoursText: "Monday to Friday from 9 to 14 h"
        },
        FR: { 
            header1: "CEIP Andrés Manjón", 
            header2: "Bibliothèque", 
            title: "Prêt de livres", 
            helpBtn: "J'ai besoin d'aide",
            placeholderName: "Nom et prénom de l'élève",
            placeholderPass: "Mot de passe",
            btnStudent: "Je suis élève",
            btnLogin: "Se connecter",
            footerAbout: "À PROPOS DE NOUS",
            footerLink: "Site web de l'école",
            footerAddress: "ADRESSE",
            footerAddressText: "CEIP Andrés Manjón. <br>C/ Delicias, 90, 50017, Zaragoza",
            footerPhone: "TÉLÉPHONE",
            footerPhoneText: "976 331 728",
            footerEmail: "E-MAIL",
            footerEmailText: "cpamanzaragoza@educa.aragon.es",
            footerHours: "HORAIRES",
            footerHoursText: "Du lundi au vendredi de 9h à 14h"
        },
        AL: { 
            header1: "CEIP Andrés Manjón", 
            header2: "مكتبة", 
            title: "استعارة الكتب", 
            helpBtn: "أحتاج مساعدة",
            placeholderName: "اسم الطالب واللقب",
            placeholderPass: "كلمة المرور",
            btnStudent: "أنا طالب",
            btnLogin: "تسجيل الدخول",
            footerAbout: "معلومات عنا",
            footerLink: "موقع المدرسة",
            footerAddress: "العنوان",
            footerAddressText: "CEIP Andrés Manjón. <br>C/ Delicias, 90, 50017, Zaragoza",
            footerPhone: "هاتف",
            footerPhoneText: "976 331 728",
            footerEmail: "البريد الإلكتروني",
            footerEmailText: "cpamanzaragoza@educa.aragon.es",
            footerHours: "ساعات العمل",
            footerHoursText: "من الاثنين إلى الجمعة من 9 إلى 14 ساعة"
        },
        CN: { 
            header1: "CEIP Andrés Manjón", 
            header2: "图书馆", 
            title: "图书借阅", 
            helpBtn: "我需要帮助",
            placeholderName: "学生姓名",
            placeholderPass: "密码",
            btnStudent: "我是学生",
            btnLogin: "登录",
            footerAbout: "关于我们",
            footerLink: "学校网站",
            footerAddress: "地址",
            footerAddressText: "CEIP Andrés Manjón. <br>C/ Delicias, 90, 50017, Zaragoza",
            footerPhone: "电话",
            footerPhoneText: "976 331 728",
            footerEmail: "电子邮箱",
            footerEmailText: "cpamanzaragoza@educa.aragon.es",
            footerHours: "开放时间",
            footerHoursText: "周一至周五上午9点至下午2点"
        }
    };


    const langSelect = document.getElementById("langSelect");
    

    const header1 = document.getElementById("header1");
    const header2 = document.getElementById("header2");
    const title = document.getElementById("title");
    const helpBtn = document.getElementById("helpBtn");


    const inputName = document.getElementById("inputName");
    const inputPass = document.getElementById("inputPass");
    const btnStudent = document.getElementById("alumno");
    const btnLogin = document.getElementById("submitBtn");

    const footerAbout = document.getElementById("footerAbout");
    const footerLink = document.getElementById("footerLink");
    const footerAddress = document.getElementById("footerAddress");
    const footerAddressText = document.getElementById("footerAddressText");
    const footerPhone = document.getElementById("footerPhone");
    const footerPhoneText = document.getElementById("footerPhoneText");
    const footerEmail = document.getElementById("footerEmail");
    const footerEmailText = document.getElementById("footerEmailText");
    const footerHours = document.getElementById("footerHours");
    const footerHoursText = document.getElementById("footerHoursText");

    langSelect.addEventListener("change", () => {
        const lang = langSelect.value;
        const t = texts[lang];

   
        header1.textContent = t.header1;
        header2.textContent = t.header2;
        title.textContent = t.title;
        helpBtn.textContent = t.helpBtn;

        inputName.placeholder = t.placeholderName;
        inputPass.placeholder = t.placeholderPass;
        btnStudent.textContent = t.btnStudent;
        btnLogin.textContent = t.btnLogin;


        footerAbout.textContent = t.footerAbout;
        footerLink.textContent = t.footerLink;
        footerAddress.textContent = t.footerAddress;
        footerAddressText.innerHTML = t.footerAddressText;
        footerPhone.textContent = t.footerPhone;
        footerPhoneText.textContent = t.footerPhoneText;
        footerEmail.textContent = t.footerEmail;
        footerEmailText.textContent = t.footerEmailText;
        footerHours.textContent = t.footerHours;
        footerHoursText.textContent = t.footerHoursText;


        if (lang === 'AL') {
            document.body.style.direction = 'rtl';
            document.body.style.textAlign = 'right';
        } else {
            document.body.style.direction = 'ltr';
            document.body.style.textAlign = 'left';
        }
    });
</script>
</body>
</html>
