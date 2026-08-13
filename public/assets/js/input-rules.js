/**
 * Reglas de entrada compartidas para todos los formularios de NeivActiva.
 *
 * - Cualquier input/textarea de texto: se le quitan los emojis mientras se escribe.
 * - data-rule="digits"  -> solo números (documento, teléfono).
 * - data-rule="letters" -> solo letras, espacios, apóstrofe y guion (nombres).
 *
 * Se aplica solo, sin necesidad de inicializar nada por campo.
 */
(function () {
    'use strict';

    // Rangos de emojis + selectores de variación y ZWJ.
    var EMOJI = /[\u{1F000}-\u{1FAFF}\u{2600}-\u{27BF}\u{2B00}-\u{2BFF}\u{2300}-\u{23FF}\u{FE00}-\u{FE0F}\u{1F1E6}-\u{1F1FF}]|‍/gu;

    function quitarEmojis(v) {
        return v.replace(EMOJI, '');
    }

    function soloDigitos(v) {
        return v.replace(/\D+/g, '');
    }

    function soloLetras(v) {
        // \p{L} = cualquier letra (incluye tildes y ñ). Permitimos espacio, ' y -.
        return v.replace(/[^\p{L}\s'\-]/gu, '');
    }

    function esCampoTexto(el) {
        if (!el || (el.tagName !== 'INPUT' && el.tagName !== 'TEXTAREA')) {
            return false;
        }
        if (el.tagName === 'TEXTAREA') {
            return true;
        }
        var tipo = (el.getAttribute('type') || 'text').toLowerCase();
        return ['text', 'tel', 'search', 'email'].indexOf(tipo) !== -1 || el.hasAttribute('data-rule');
    }

    function aplicarReglas(el) {
        var original = el.value;
        var limpio = quitarEmojis(original);

        var regla = el.getAttribute('data-rule');
        if (regla === 'digits') {
            limpio = soloDigitos(limpio);
        } else if (regla === 'letters') {
            limpio = soloLetras(limpio);
        }

        if (limpio !== original) {
            var desplazamiento = original.length - limpio.length;
            var cursor = (el.selectionStart || 0) - desplazamiento;
            el.value = limpio;
            if (el.type !== 'email') { // email no soporta setSelectionRange
                try { el.setSelectionRange(cursor, cursor); } catch (e) {}
            }
        }
    }

    document.addEventListener('input', function (e) {
        if (esCampoTexto(e.target)) {
            aplicarReglas(e.target);
        }
    }, true);
})();
