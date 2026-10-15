/**
 * C.A.A.S. — Motor de Traducción Global
 * Combina Google Translate (cobertura total) + sistema propio (control y notranslate)
 *
 * Estrategia:
 * 1. Google Translate se inicializa en modo silencioso (sin barra visible)
 * 2. Nuestros botones de ajustes llaman a switchGoogleLang() para cambiar el idioma
 * 3. Los elementos con class="notranslate" quedan protegidos de la traducción
 * 4. La preferencia se guarda en localStorage y se aplica en cada carga de página
 */

// ── Mapa de códigos: nuestro sistema → Google Translate ───────────
const LANG_MAP = {
    es: 'es',   // Español
    en: 'en',   // English
    pt: 'pt',   // Português
    fr: 'fr',   // Français
    it: 'it',   // Italiano
    de: 'de',   // Deutsch
    zh: 'zh-CN',// 中文
    ja: 'ja',   // 日本語
};

// Idioma que NO necesita traducción (el original del sitio)
const LANG_ORIGINAL = 'es';

/**
 * Inicializa Google Translate en modo silencioso.
 * Se llama desde el callback que Google Translate define.
 */
window.googleTranslateElementInit = function () {
    new google.translate.TranslateElement(
        {
            pageLanguage: LANG_ORIGINAL,
            // Idiomas permitidos
            includedLanguages: Object.values(LANG_MAP).join(','),
            // NONE oculta el widget visual de Google
            layout: google.translate.TranslateElement.InlineLayout.SIMPLE,
            autoDisplay: false,
        },
        'google_translate_element'   // div oculto que definimos en head.php
    );

    // Aplicar idioma guardado al cargar la página
    const langGuardado = localStorage.getItem('caas_lang') || LANG_ORIGINAL;
    if (langGuardado !== LANG_ORIGINAL) {
        // Pequeño delay para que Google Translate termine de inicializar
        setTimeout(() => aplicarIdiomaGoogle(langGuardado), 600);
    }
};

/**
 * Cambia el idioma usando Google Translate programáticamente.
 * @param {string} lang — código de nuestro sistema (es, en, pt, fr, it, de, zh, ja)
 */
function switchGoogleLang(lang) {
    localStorage.setItem('caas_lang', lang);

    if (lang === LANG_ORIGINAL) {
        // Volver al español: restaurar la página original
        restaurarOriginal();
        return;
    }

    aplicarIdiomaGoogle(lang);
}

/**
 * Aplica el idioma a través del select oculto de Google Translate
 */
function aplicarIdiomaGoogle(lang) {
    const googleLang = LANG_MAP[lang] || lang;

    // Google Translate usa un select oculto con clase .goog-te-combo
    const select = document.querySelector('.goog-te-combo');
    if (select) {
        select.value = googleLang;
        select.dispatchEvent(new Event('change'));
        return;
    }

    // Si el select aún no cargó, reintentar
    setTimeout(() => {
        const sel = document.querySelector('.goog-te-combo');
        if (sel) {
            sel.value = googleLang;
            sel.dispatchEvent(new Event('change'));
        }
    }, 800);
}

/**
 * Restaura el idioma original (español) limpiando la cookie de Google Translate
 */
function restaurarOriginal() {
    // Borrar la cookie que usa Google Translate
    document.cookie = 'googtrans=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;';
    document.cookie = 'googtrans=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/; domain=.' + location.hostname + ';';

    const select = document.querySelector('.goog-te-combo');
    if (select) {
        select.value = LANG_ORIGINAL;
        select.dispatchEvent(new Event('change'));
    } else {
        // Recargar como último recurso solo si es necesario
        location.reload();
    }
}

/**
 * Obtiene el idioma actual guardado
 */
function getLang() {
    return localStorage.getItem('caas_lang') || LANG_ORIGINAL;
}

/**
 * Actualiza el indicador visual del idioma activo en los botones de ajustes
 */
function marcarIdiomaActivo() {
    const langActual = getLang();
    document.querySelectorAll('[data-lang-btn]').forEach(btn => {
        const lang = btn.getAttribute('data-lang-btn');
        const activo = lang === langActual;
        btn.classList.toggle('ring-2',              activo);
        btn.classList.toggle('ring-orange-500',     activo);
        btn.classList.toggle('bg-orange-50',        activo);
        btn.classList.toggle('dark:bg-orange-950',  activo);
        btn.classList.toggle('border-orange-500',   activo);
        // Quitar estilos de inactivo
        btn.classList.toggle('border-gray-200',     !activo);
        btn.classList.toggle('dark:border-gray-700',!activo);
    });

    // Actualizar el indicador del navbar si existe
    const navIndicator = document.getElementById('navLangIndicator');
    if (navIndicator) {
        const banderas = { es:'🇺🇾', en:'🇺🇸', pt:'🇧🇷', fr:'🇫🇷', it:'🇮🇹', de:'🇩🇪', zh:'🇨🇳', ja:'🇯🇵' };
        navIndicator.textContent = banderas[langActual] || '🌐';
    }
}

// ── Ocultar la barra de Google Translate con CSS ──────────────────
// Google inyecta un iframe y elementos que debemos ocultar visualmente
(function ocultarBarraGoogle() {
    const style = document.createElement('style');
    style.textContent = `
        /* Ocultar barra de Google Translate */
        .goog-te-banner-frame,
        .goog-te-balloon-frame,
        #goog-gt-tt,
        .goog-te-spinner-pos { display: none !important; }

        /* Compensar el desplazamiento que genera Google Translate en el body */
        body { top: 0 !important; position: static !important; }

        /* Ocultar el widget contenedor */
        #google_translate_element { display: none !important; }

        /* Evitar el subrayado de Google en textos traducidos */
        .goog-text-highlight { background: none !important; box-shadow: none !important; }

        /* Font rendering en idiomas CJK (chino/japonés) */
        :lang(zh), :lang(ja) { font-family: 'Noto Sans CJK', sans-serif; }
    `;
    document.head.appendChild(style);
})();

// ── Aplicar al cargar el DOM ──────────────────────────────────────
document.addEventListener('DOMContentLoaded', marcarIdiomaActivo);
