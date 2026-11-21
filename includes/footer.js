// includes/footer.js
(function () {
  'use strict';

  function renderFooter() {
    var host = document.getElementById('site-footer');
    if (!host) return;

    var enSubcarpeta = window.location.pathname.indexOf('/qr/') !== -1;
    var base = enSubcarpeta ? '../' : '';
    var anio = new Date().getFullYear();

    host.innerHTML =
      '<footer class="footer-module--footer--8b6c1" style="background:#ffffff;border-top:1px solid #e9eef2;padding:32px 20px;margin-top:40px;color:#4d4d4f;">' +
        '<div style="max-width:1120px;margin:0 auto;display:flex;flex-wrap:wrap;justify-content:space-between;align-items:center;gap:20px;">' +
          '<div style="display:flex;align-items:center;gap:16px;flex-wrap:wrap;">' +
            '<a href="' + base + 'index.html">' +
              '<img src="' + base + 'assets/img/imagen_1_.png" alt="Seguros Mundial" title="Seguros Mundial" style="height:36px;width:auto;object-fit:contain;">' +
            '</a>' +
            '<span style="font-size:0.88rem;color:#6c7a89;">' +
              '&copy; ' + anio + ' · Todos los derechos reservados.' +
            '</span>' +
          '</div>' +
          '<div style="display:flex;gap:18px;flex-wrap:wrap;font-size:0.9rem;">' +
            '<a href="' + base + 'index.html" style="color:#066188;text-decoration:none;font-weight:500;">Inicio</a>' +
            '<a href="' + base + 'informacion-vehiculo.html" style="color:#066188;text-decoration:none;font-weight:500;">Vehículo</a>' +
            '<a href="' + base + 'pago.html" style="color:#066188;text-decoration:none;font-weight:500;">Medios de pago</a>' +
            '<a href="https://www.segurosmundial.com.co/proteccion-de-datos/" target="_blank" rel="noopener noreferrer" style="color:#066188;text-decoration:none;font-weight:500;">Privacidad</a>' +
            '<a href="https://soatmundial.com.co/terminos-y-condiciones/" target="_blank" rel="noopener noreferrer" style="color:#066188;text-decoration:none;font-weight:500;">Términos</a>' +
          '</div>' +
        '</div>' +
      '</footer>';
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', renderFooter);
  } else {
    renderFooter();
  }
})();
