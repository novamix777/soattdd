// includes/header.js
(function () {
  'use strict';

  function renderHeader() {
    var host = document.getElementById('site-header');
    if (!host) return;

    var enSubcarpeta = window.location.pathname.indexOf('/qr/') !== -1;
    var base = enSubcarpeta ? '../' : '';

    host.innerHTML =
      '<nav class="menu-module--menu--ff100">' +
        '<div class="menu-module--menu__container--550e5">' +
          '<div class="logo-module--wrapper__logo--d2507">' +
            '<a href="' + base + 'index.html">' +
              '<img class="safe-gatsby-image-module--safeImage--9bfbe menu-module--menu__container__logos__logo--30a8e" ' +
                   'src="' + base + 'assets/img/imagen_1_.png" ' +
                   'width="609" height="136" alt="seguros mundial" title="seguros mundial">' +
            '</a>' +
          '</div>' +
          '<div class="menu-module--menu__container__logos--cd40c" data-ally="false"></div>' +
        '</div>' +
        '<ul class="menu-module--menu__items--6eadc">' +
          '<li class="menu-item-module--menu_item--04780 menu-item-module--display_none--19788 menu-item-module--menu_item__highlighted--fca77 menu-item-module--menu_item__centered--2e42f">' +
            '<div class="menu-item-module--button--a1b98">' +
              '<div><a data-theme="highlighted" class="anchor-module--anchor--6e022" href="' + base + 'index.html#form-cotizar">Comprar SOAT</a></div>' +
            '</div>' +
          '</li>' +
          '<li class="menu-item-module--menu_item--04780 menu-item-module--menu_item__centered--2e42f">' +
            '<div class="menu-item-module--button--a1b98">' +
              '<a data-theme="menu-item" class="button-icon-module--button--c6d67" href="' + base + 'index.html#conoce-mas">' +
                '<div class="button-icon-module--button__text--80959">SOAT</div>' +
              '</a>' +
            '</div>' +
          '</li>' +
          '<li class="menu-item-module--menu_item--04780 menu-item-module--menu_item__highlighted--fca77 menu-item-module--menu_item__centered--2e42f">' +
            '<div class="menu-item-module--button--a1b98">' +
              '<div><a data-theme="highlighted" class="anchor-module--anchor--6e022" href="' + base + 'informacion-vehiculo.html">Ver Resumen</a></div>' +
            '</div>' +
          '</li>' +
        '</ul>' +
      '</nav>';
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', renderHeader);
  } else {
    renderHeader();
  }
})(); 
 
