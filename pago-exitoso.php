<?php
/**
 * pago-exitoso.php
 * ------------------------------------------------------------------
 * Página de confirmación que se muestra después de un pago exitoso.
 * Muestra un comprobante tipo factura con todos los datos.
 * ------------------------------------------------------------------
 */

session_start();
require_once __DIR__ . '/includes/calculo.php';

// Solo accesible si hay un pago reciente en sesión
if (empty($_SESSION['cotizacion']) || empty($_SESSION['resumen']) || empty($_SESSION['pago_exitoso'])) {
    header('Location: index.php');
    exit;
}

$cot       = $_SESSION['cotizacion'];
$sel       = $_SESSION['seleccion'] ?? [];
$resumen   = $_SESSION['resumen'];
$pago      = $_SESSION['pago_exitoso'];

$vehiculo  = $cot['vehiculo'];
$prop      = $_SESSION['propietario'] ?? [];

$nombres   = $prop['nombres'] ?? '';
$apellido1 = $prop['primer_apellido'] ?? '';
$apellido2 = $prop['segundo_apellido'] ?? '';
$nombreCompleto = trim($nombres . ' ' . $apellido1 . ' ' . $apellido2);
if ($nombreCompleto === '' && !empty($prop['nombre_completo'])) {
    $nombreCompleto = $prop['nombre_completo'];
}

$telefono   = $sel['telefono'] ?? '';
$email      = $sel['email'] ?? '';
$documento  = $cot['documento'] ?? '';
$tipoDoc    = $cot['tipo_documento'] ?? 'CC';
$placa      = $cot['placa'] ?? '';

$numeroFactura = $pago['numero_factura'] ?? ('SOAT-' . strtoupper(bin2hex(random_bytes(4))));
$fechaPago     = $pago['fecha_pago'] ?? date('Y-m-d H:i:s');
$medioPago     = $pago['medio'] ?? 'Tarjeta';
$ultimos4      = $pago['ultimos4'] ?? '';
$totalPagado   = $pago['total'] ?? $resumen['total'];

$fechaObj = new DateTime($fechaPago);

$tieneDescuento = !empty($resumen['descuento_soat']) && $resumen['descuento_soat'] > 0;

// Vigencia del SOAT (1 año desde hoy)
$inicioVigencia = new DateTime('today');
$finVigencia    = (clone $inicioVigencia)->modify('+1 year -1 day');
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta http-equiv="x-ua-compatible" content="ie=edge">
<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
<meta name="theme-color" content="#FFFFFF">
<meta name="robots" content="noindex, nofollow">
<title>Pago exitoso | SOAT Seguros Mundial</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Ubuntu:ital,wght@0,300;0,400;0,500;0,700;1,300;1,400;1,500;1,700&display=swap" rel="stylesheet">
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }

body {
    background-color: #eef1f5;
    font-family: Ubuntu, sans-serif;
    color: #333;
    min-height: 100vh;
    display: flex;
    flex-direction: column;
}

/* ---------- HEADER ---------- */
.pe-header {
    background: #fff;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 14px 6%;
    box-shadow: 0 1px 6px 0 rgba(0,0,0,.06);
}
.pe-header-logo {
    height: 34px;
    width: auto;
    display: block;
}
.pe-header-contacto {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    border: 2px solid #0a99d6;
    color: #0a99d6;
    border-radius: 9999px;
    padding: 6px 20px;
    font-weight: 700;
    font-size: .88rem;
    text-decoration: none;
    background: #fff;
    transition: background .2s;
}
.pe-header-contacto:hover { background: #f5fbfe; }
.pe-header-contacto svg { width: 18px; height: 18px; }

/* ---------- MAIN ---------- */
.pe-main {
    flex: 1 0 auto;
    padding: 40px 16px 60px;
    display: flex;
    flex-direction: column;
    align-items: center;
}

/* ---------- Banner de éxito ---------- */
.pe-success-banner {
    background: #fff;
    border-radius: 16px;
    box-shadow: 0 2px 14px 0 rgba(0,0,0,.08);
    padding: 36px 40px 30px;
    width: 100%;
    max-width: 720px;
    text-align: center;
    margin-bottom: 24px;
}
.pe-success-icon {
    width: 78px;
    height: 78px;
    border-radius: 50%;
    background: #15d064;
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 18px;
    animation: pePop .5s cubic-bezier(.4,1.6,.6,1);
}
.pe-success-icon svg { width: 42px; height: 42px; }
@keyframes pePop {
    0%   { transform: scale(0); opacity: 0; }
    60%  { transform: scale(1.15); opacity: 1; }
    100% { transform: scale(1); }
}

.pe-success-title {
    color: #15d064;
    font-size: 1.6rem;
    font-weight: 700;
    margin: 0 0 10px;
    line-height: 1.2;
}
.pe-success-subtitle {
    color: #066188;
    font-size: 1.05rem;
    font-weight: 500;
    margin: 0;
    line-height: 1.5;
}
.pe-success-subtitle strong {
    color: #066188;
    font-weight: 700;
}
.pe-success-nota {
    margin: 18px auto 0;
    max-width: 520px;
    color: #4d4d4f;
    font-size: .92rem;
    line-height: 1.55;
    background: #f6f7fc;
    padding: 14px 20px;
    border-radius: 10px;
    border-left: 4px solid #0a99d6;
    text-align: left;
}
.pe-success-nota strong { color: #066188; }

/* ---------- Factura ---------- */
.pe-factura {
    background: #fff;
    border-radius: 16px;
    box-shadow: 0 2px 14px 0 rgba(0,0,0,.08);
    padding: 0;
    width: 100%;
    max-width: 720px;
    overflow: hidden;
}

.pe-factura-head {
    background: #0a99d6;
    color: #fff;
    padding: 20px 30px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 10px;
}
.pe-factura-head h2 {
    font-size: 1.15rem;
    font-weight: 700;
    margin: 0;
    letter-spacing: .3px;
}
.pe-factura-num {
    font-size: .95rem;
    font-weight: 500;
    background: rgba(255,255,255,.18);
    padding: 6px 14px;
    border-radius: 9999px;
}

.pe-factura-body {
    padding: 26px 30px 30px;
}

.pe-seccion {
    margin-bottom: 22px;
}
.pe-seccion:last-child { margin-bottom: 0; }

.pe-seccion-titulo {
    color: #0a99d6;
    font-size: .82rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 1px;
    margin: 0 0 12px;
    padding-bottom: 6px;
    border-bottom: 1px solid #eef5fb;
}

.pe-grid-2 {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 14px 20px;
}
.pe-grid-3 {
    display: grid;
    grid-template-columns: 1fr 1fr 1fr;
    gap: 14px 20px;
}

.pe-item {
    display: flex;
    flex-direction: column;
    gap: 3px;
}
.pe-item-label {
    font-size: .72rem;
    color: #828285;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: .5px;
}
.pe-item-value {
    font-size: .95rem;
    color: #1a1a1a;
    font-weight: 500;
    line-height: 1.3;
    word-wrap: break-word;
}
.pe-item-value strong { color: #066188; font-weight: 700; }

/* ---------- Filas de montos ---------- */
.pe-montos {
    background: #f6f7fc;
    border-radius: 10px;
    padding: 16px 20px;
    margin-top: 8px;
}
.pe-monto-fila {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 7px 0;
    color: #4d4d4f;
    font-size: .92rem;
}
.pe-monto-fila span:last-child {
    font-weight: 600;
    color: #1a1a1a;
    white-space: nowrap;
}
.pe-monto-fila.pe-descuento {
    color: #15d064;
    font-weight: 700;
}
.pe-monto-fila.pe-descuento span:last-child {
    color: #15d064;
}
.pe-monto-fila.pe-oficial span:last-child {
    text-decoration: line-through;
    color: #828285;
    font-weight: 500;
}
.pe-monto-total {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 12px;
    padding-top: 14px;
    border-top: 2px solid #0a99d6;
    color: #066188;
    font-size: 1.15rem;
    font-weight: 700;
}
.pe-monto-total span:last-child {
    font-size: 1.4rem;
    color: #0a99d6;
}

/* ---------- Footer de la factura ---------- */
.pe-factura-footer {
    background: #f9fbfd;
    padding: 18px 30px;
    border-top: 1px solid #eef5fb;
    text-align: center;
    color: #828285;
    font-size: .78rem;
    line-height: 1.5;
}
.pe-factura-footer strong { color: #066188; }

/* ---------- Botones ---------- */
.pe-acciones {
    margin-top: 26px;
    display: flex;
    gap: 14px;
    flex-wrap: wrap;
    justify-content: center;
}
.pe-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 12px 32px;
    border-radius: 9999px;
    font-weight: 700;
    font-size: .92rem;
    text-decoration: none;
    cursor: pointer;
    border: none;
    font-family: Ubuntu, sans-serif;
    transition: transform .15s, opacity .2s;
}
.pe-btn:hover { transform: scale(1.02); }
.pe-btn:active { transform: scale(.99); }
.pe-btn-primary {
    background: linear-gradient(90deg, #16b8d4 0%, #0a99d6 100%);
    color: #fff;
}
.pe-btn-secondary {
    background: #fff;
    color: #0a99d6;
    border: 2px solid #0a99d6;
}
.pe-btn svg { width: 16px; height: 16px; }

/* ---------- Responsive ---------- */
@media (max-width: 600px) {
    .pe-header { padding: 12px 5%; }
    .pe-header-logo { height: 26px; }
    .pe-header-contacto {
        padding: 5px 14px;
        font-size: .78rem;
        gap: 6px;
    }
    .pe-header-contacto svg { width: 15px; height: 15px; }

    .pe-main { padding: 24px 12px 40px; }

    .pe-success-banner { padding: 26px 22px 22px; }
    .pe-success-title { font-size: 1.25rem; }
    .pe-success-subtitle { font-size: .95rem; }
    .pe-success-nota { font-size: .85rem; padding: 12px 16px; }

    .pe-factura-head { padding: 16px 20px; }
    .pe-factura-head h2 { font-size: 1rem; }
    .pe-factura-num { font-size: .8rem; }

    .pe-factura-body { padding: 20px 20px 22px; }

    .pe-grid-2, .pe-grid-3 { grid-template-columns: 1fr; }

    .pe-montos { padding: 14px 16px; }
    .pe-monto-fila { font-size: .85rem; }
    .pe-monto-total { font-size: 1rem; }
    .pe-monto-total span:last-child { font-size: 1.2rem; }

    .pe-factura-footer { padding: 14px 20px; font-size: .72rem; }

    .pe-acciones { flex-direction: column; width: 100%; }
    .pe-btn { width: 100%; justify-content: center; }
}
</style>
</head>
<body>

<header class="pe-header">
  <a href="index.php">
    <img class="pe-header-logo" src="assets/img/imagen_1_.png" alt="Seguros Mundial">
  </a>
  <a href="index.php" class="pe-header-contacto">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
      <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
      <polyline points="22,6 12,13 2,6"></polyline>
    </svg>
    Contacto
  </a>
</header>

<main class="pe-main">

  <!-- ============ BANNER DE ÉXITO ============ -->
  <div class="pe-success-banner">
    <div class="pe-success-icon">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
        <polyline points="20 6 9 17 4 12"></polyline>
      </svg>
    </div>
    <h1 class="pe-success-title">¡Pago exitoso!</h1>
    <p class="pe-success-subtitle">
      Tu póliza SOAT ha sido adquirida exitosamente
    </p>

    <div class="pe-success-nota">
      📩 En la próxima hora recibirás tu póliza SOAT en el correo
      <strong><?= htmlspecialchars($email) ?></strong>, junto con todos los
      documentos y certificados del seguro. Si no lo ves, revisa tu carpeta
      de spam.
    </div>
  </div>

  <!-- ============ FACTURA ============ -->
  <div class="pe-factura">

    <div class="pe-factura-head">
      <h2>Comprobante de pago</h2>
      <span class="pe-factura-num"><?= htmlspecialchars($numeroFactura) ?></span>
    </div>

    <div class="pe-factura-body">

      <!-- Datos del cliente -->
      <div class="pe-seccion">
        <h3 class="pe-seccion-titulo">Datos del cliente</h3>
        <div class="pe-grid-2">
          <div class="pe-item">
            <span class="pe-item-label">Nombre</span>
            <span class="pe-item-value"><?= htmlspecialchars($nombreCompleto ?: '—') ?></span>
          </div>
          <div class="pe-item">
            <span class="pe-item-label">Documento</span>
            <span class="pe-item-value"><?= htmlspecialchars($tipoDoc . ' ' . $documento) ?></span>
          </div>
          <div class="pe-item">
            <span class="pe-item-label">Correo</span>
            <span class="pe-item-value"><?= htmlspecialchars($email ?: '—') ?></span>
          </div>
          <div class="pe-item">
            <span class="pe-item-label">Teléfono</span>
            <span class="pe-item-value"><?= htmlspecialchars($telefono ?: '—') ?></span>
          </div>
        </div>
      </div>

      <!-- Datos del vehículo -->
      <div class="pe-seccion">
        <h3 class="pe-seccion-titulo">Datos del vehículo</h3>
        <div class="pe-grid-3">
          <div class="pe-item">
            <span class="pe-item-label">Placa</span>
            <span class="pe-item-value"><strong><?= htmlspecialchars($placa) ?></strong></span>
          </div>
          <div class="pe-item">
            <span class="pe-item-label">Marca</span>
            <span class="pe-item-value"><?= htmlspecialchars($vehiculo['marca'] ?: '—') ?></span>
          </div>
          <div class="pe-item">
            <span class="pe-item-label">Línea</span>
            <span class="pe-item-value"><?= htmlspecialchars($vehiculo['linea'] ?: '—') ?></span>
          </div>
          <div class="pe-item">
            <span class="pe-item-label">Modelo</span>
            <span class="pe-item-value"><?= htmlspecialchars($vehiculo['modelo'] ?: '—') ?></span>
          </div>
          <div class="pe-item">
            <span class="pe-item-label">Clase</span>
            <span class="pe-item-value"><?= htmlspecialchars($vehiculo['clase'] ?: '—') ?></span>
          </div>
          <div class="pe-item">
            <span class="pe-item-label">Cilindraje</span>
            <span class="pe-item-value"><?= htmlspecialchars(($vehiculo['cc'] ?? '') ? $vehiculo['cc'] . ' cc' : '—') ?></span>
          </div>
        </div>
      </div>

      <!-- Datos del pago -->
      <div class="pe-seccion">
        <h3 class="pe-seccion-titulo">Detalles del pago</h3>
        <div class="pe-grid-3">
          <div class="pe-item">
            <span class="pe-item-label">Fecha de pago</span>
            <span class="pe-item-value"><?= $fechaObj->format('d/m/Y H:i') ?></span>
          </div>
          <div class="pe-item">
            <span class="pe-item-label">Medio de pago</span>
            <span class="pe-item-value">
              <?= htmlspecialchars($medioPago) ?>
              <?= $ultimos4 ? ' ****' . htmlspecialchars($ultimos4) : '' ?>
            </span>
          </div>
          <div class="pe-item">
            <span class="pe-item-label">Inicio vigencia</span>
            <span class="pe-item-value"><?= $inicioVigencia->format('d/m/Y') ?></span>
          </div>
          <div class="pe-item">
            <span class="pe-item-label">Fin vigencia</span>
            <span class="pe-item-value"><?= $finVigencia->format('d/m/Y') ?></span>
          </div>
          <div class="pe-item">
            <span class="pe-item-label">Estado</span>
            <span class="pe-item-value"><strong style="color:#15d064;">✅ PAGADO</strong></span>
          </div>
          <div class="pe-item">
            <span class="pe-item-label">N° factura</span>
            <span class="pe-item-value"><code><?= htmlspecialchars($numeroFactura) ?></code></span>
          </div>
        </div>
      </div>

      <!-- Desglose de montos -->
      <div class="pe-seccion">
        <h3 class="pe-seccion-titulo">Desglose</h3>
        <div class="pe-montos">

          <?php if ($tieneDescuento): ?>
            <div class="pe-monto-fila pe-oficial">
              <span>Valor SOAT oficial</span>
              <span><?= formato_cop($resumen['valor_soat_oficial']) ?></span>
            </div>
            <div class="pe-monto-fila pe-descuento">
              <span>Descuento 30% 🎉</span>
              <span>-<?= formato_cop($resumen['descuento_soat']) ?></span>
            </div>
          <?php endif; ?>

          <div class="pe-monto-fila">
            <span>SOAT<?= $tieneDescuento ? ' (con descuento)' : '' ?></span>
            <span><?= formato_cop($resumen['subtotal_soat']) ?></span>
          </div>

          <?php if (!empty($resumen['terceros'])): ?>
            <div class="pe-monto-fila">
              <span>Seguro Tercero Voluntario</span>
              <span><?= formato_cop($resumen['subtotal_terceros']) ?></span>
            </div>
          <?php endif; ?>

          <?php if (!empty($resumen['accidentes'])): ?>
            <div class="pe-monto-fila">
              <span>Accidentes Personales (<?= htmlspecialchars($resumen['accidentes']['label']) ?>)</span>
              <span><?= formato_cop($resumen['subtotal_accidentes']) ?></span>
            </div>
          <?php endif; ?>

          <div class="pe-monto-total">
            <span>Total pagado</span>
            <span><?= formato_cop($totalPagado) ?></span>
          </div>

        </div>
      </div>

    </div>

    <div class="pe-factura-footer">
      <strong>Seguros Mundial</strong> · Compañía Mundial de Seguros S.A.<br>
      Este comprobante es válido como prueba de pago. La póliza oficial será
      enviada a tu correo electrónico en la próxima hora.
    </div>

  </div>

  <!-- ============ BOTONES ============ -->
  <div class="pe-acciones">
    <button type="button" class="pe-btn pe-btn-primary" onclick="window.print()">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <polyline points="6 9 6 2 18 2 18 9"></polyline>
        <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
        <rect x="6" y="14" width="12" height="8"></rect>
      </svg>
      Descargar / Imprimir
    </button>
    <a href="index.php" class="pe-btn pe-btn-secondary">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
        <polyline points="9 22 9 12 15 12 15 22"></polyline>
      </svg>
      Volver al inicio
    </a>
  </div>

</main>

</body>
</html>