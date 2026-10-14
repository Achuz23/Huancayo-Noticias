/* Huancayo Noticias - interacciones ligeras (sin librerías externas) */
(function () {
    'use strict';
    var raiz = document.documentElement;

    function guardar(clave, valor) {
        try { localStorage.setItem(clave, valor); } catch (e) {}
    }

    // ----- Modo oscuro -----
    var btnTema = document.getElementById('btn-tema');
    if (btnTema) {
        var actualizarTema = function () {
            var oscuro = raiz.getAttribute('data-tema') === 'oscuro';
            btnTema.setAttribute('aria-pressed', oscuro ? 'true' : 'false');
            btnTema.setAttribute('aria-label', oscuro ? 'Activar modo claro' : 'Activar modo oscuro');
        };
        if (!raiz.getAttribute('data-tema') && window.matchMedia('(prefers-color-scheme: dark)').matches) {
            raiz.setAttribute('data-tema', 'oscuro');
        }
        actualizarTema();
        btnTema.addEventListener('click', function () {
            var nuevo = raiz.getAttribute('data-tema') === 'oscuro' ? 'claro' : 'oscuro';
            raiz.setAttribute('data-tema', nuevo);
            guardar('tema', nuevo);
            actualizarTema();
        });
    }

    // ----- Tamaño de texto (90% a 150%) -----
    document.querySelectorAll('[data-fuente]').forEach(function (b) {
        b.addEventListener('click', function () {
            var actual = parseInt(raiz.style.fontSize, 10) || 100;
            actual += b.getAttribute('data-fuente') === '+' ? 10 : -10;
            actual = Math.min(150, Math.max(90, actual));
            raiz.style.fontSize = actual + '%';
            guardar('fuente', actual);
        });
    });

    // ----- Menú móvil -----
    var toggle = document.querySelector('.menu-toggle');
    var menu = document.getElementById('menu-principal');
    if (toggle && menu) {
        toggle.addEventListener('click', function () {
            var abierto = menu.classList.toggle('abierto');
            toggle.setAttribute('aria-expanded', abierto ? 'true' : 'false');
        });
    }

    // ----- Confirmación antes de eliminar -----
    document.querySelectorAll('form[data-confirmar]').forEach(function (f) {
        f.addEventListener('submit', function (ev) {
            if (!window.confirm(f.getAttribute('data-confirmar'))) ev.preventDefault();
        });
    });

    // ----- Mostrar / ocultar contraseña -----
    var verClave = document.getElementById('ver-clave');
    if (verClave) {
        verClave.addEventListener('click', function () {
            var campo = document.getElementById('clave');
            var mostrar = campo.type === 'password';
            campo.type = mostrar ? 'text' : 'password';
            verClave.textContent = mostrar ? 'Ocultar' : 'Mostrar';
            verClave.setAttribute('aria-pressed', mostrar ? 'true' : 'false');
        });
    }

    // ----- Contador de palabras en el editor -----
    var contenido = document.getElementById('contenido');
    var contador = document.getElementById('contador-palabras');
    if (contenido && contador) {
        var contar = function () {
            var palabras = contenido.value.trim().split(/\s+/).filter(Boolean).length;
            contador.textContent = palabras + ' palabras · ' + Math.max(1, Math.ceil(palabras / 200)) + ' min de lectura.';
        };
        contenido.addEventListener('input', contar);
        contar();
    }

    // ----- Leer noticia en voz alta (síntesis de voz del navegador) -----
    var btnLeer = document.getElementById('btn-leer');
    if (btnLeer) {
        if (!('speechSynthesis' in window)) {
            btnLeer.hidden = true;
        } else {
            btnLeer.addEventListener('click', function () {
                if (speechSynthesis.speaking) {
                    speechSynthesis.cancel();
                    btnLeer.setAttribute('aria-pressed', 'false');
                    btnLeer.textContent = '🔊 Escuchar noticia';
                    return;
                }
                var titulo = document.querySelector('.articulo h1').textContent;
                var texto = document.querySelector('.articulo-texto').textContent;
                var voz = new SpeechSynthesisUtterance(titulo + '. ' + texto);
                voz.lang = 'es-PE';
                voz.onend = function () {
                    btnLeer.setAttribute('aria-pressed', 'false');
                    btnLeer.textContent = '🔊 Escuchar noticia';
                };
                speechSynthesis.speak(voz);
                btnLeer.setAttribute('aria-pressed', 'true');
                btnLeer.textContent = '⏹ Detener lectura';
            });
        }
    }

    // ----- Copiar enlace -----
    var btnCopiar = document.getElementById('btn-copiar');
    var estado = document.getElementById('estado-compartir');
    if (btnCopiar) {
        btnCopiar.addEventListener('click', function () {
            var ok = function () { estado.textContent = 'Enlace copiado al portapapeles.'; };
            if (navigator.clipboard) {
                navigator.clipboard.writeText(location.href).then(ok, function () {
                    estado.textContent = 'No se pudo copiar el enlace.';
                });
            }
        });
    }

    // ----- Llevar el foco al resumen de errores -----
    var resumen = document.getElementById('resumen-errores');
    if (resumen) resumen.focus();
})();
