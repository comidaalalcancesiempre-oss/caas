/**
 * C.A.A.S. — Sistema de Tema + Idioma
 * Se carga en el <head> de TODAS las páginas para evitar flash de tema incorrecto.
 * Requiere: lang.js cargado antes que este archivo.
 */

// ── 1. Aplicar tema ANTES de que se pinte la página ───────────────
(function () {
    const tema = localStorage.getItem('caas_tema') || 'light';
    if (tema === 'dark') {
        document.documentElement.classList.add('dark');
    } else {
        document.documentElement.classList.remove('dark');
    }
})();

// ── 2. Funciones públicas ──────────────────────────────────────────

/**
 * Obtiene el idioma actual guardado (default: 'es')
 */
function getLang() {
    return localStorage.getItem('caas_lang') || 'es';
}

/**
 * Obtiene el tema actual (default: 'light')
 */
function getTema() {
    return localStorage.getItem('caas_tema') || 'light';
}

/**
 * Cambia el tema y lo persiste
 * @param {'light'|'dark'} tema
 */
function setTema(tema) {
    localStorage.setItem('caas_tema', tema);
    if (tema === 'dark') {
        document.documentElement.classList.add('dark');
    } else {
        document.documentElement.classList.remove('dark');
    }
    // Actualizar botones de toggle si existen
    actualizarBotonesTema(tema);
}

/**
 * Cambia el idioma, persiste y re-aplica las traducciones
 * @param {'es'|'en'|'zh'|'ja'|'pt'} lang
 */
function setLang(lang) {
    if (!CAAS_LANG[lang]) return;
    localStorage.setItem('caas_lang', lang);
    aplicarIdioma(lang);
    // Actualizar atributo lang del html
    document.documentElement.lang = lang === 'pt' ? 'pt-BR' : lang;
}

/**
 * Aplica las traducciones a todos los elementos con data-i18n
 * @param {string} lang
 */
function aplicarIdioma(lang) {
    const t = CAAS_LANG[lang] || CAAS_LANG['es'];

    // Texto interno
    document.querySelectorAll('[data-i18n]').forEach(el => {
        const key = el.getAttribute('data-i18n');
        if (t[key] !== undefined) el.textContent = t[key];
    });

    // Placeholder
    document.querySelectorAll('[data-i18n-ph]').forEach(el => {
        const key = el.getAttribute('data-i18n-ph');
        if (t[key] !== undefined) el.placeholder = t[key];
    });

    // Title / aria-label
    document.querySelectorAll('[data-i18n-title]').forEach(el => {
        const key = el.getAttribute('data-i18n-title');
        if (t[key] !== undefined) el.title = t[key];
    });

    // Select options con data-i18n-val
    document.querySelectorAll('option[data-i18n]').forEach(el => {
        const key = el.getAttribute('data-i18n');
        if (t[key] !== undefined) el.textContent = t[key];
    });
}

/**
 * Actualiza el estado visual de los botones de tema
 */
function actualizarBotonesTema(tema) {
    const btnLight = document.getElementById('btnLight');
    const btnDark  = document.getElementById('btnDark');
    if (!btnLight || !btnDark) return;

    if (tema === 'dark') {
        btnDark.classList.add('ring-2', 'ring-orange-500');
        btnLight.classList.remove('ring-2', 'ring-orange-500');
    } else {
        btnLight.classList.add('ring-2', 'ring-orange-500');
        btnDark.classList.remove('ring-2', 'ring-orange-500');
    }
}

/**
 * Helper: obtiene una clave de traducción del idioma actual
 * @param {string} key
 * @returns {string}
 */
function t(key) {
    const lang = getLang();
    return (CAAS_LANG[lang] && CAAS_LANG[lang][key]) || (CAAS_LANG['es'][key]) || key;
}

// ── 3. Inicialización automática al cargar el DOM ─────────────────
document.addEventListener('DOMContentLoaded', function () {
    const lang = getLang();
    const tema = getTema();

    // Aplicar idioma
    aplicarIdioma(lang);

    // Aplicar lang al html
    document.documentElement.lang = lang === 'pt' ? 'pt-BR' : lang;

    // Actualizar botones de ajustes si existen
    actualizarBotonesTema(tema);

    // Marcar selector de idioma si existe
    const selectLang = document.getElementById('selectLang');
    if (selectLang) selectLang.value = lang;

    // Toggle dark mode rápido (ícono en navbar)
    const btnToggleTema = document.getElementById('btnToggleTema');
    if (btnToggleTema) {
        btnToggleTema.addEventListener('click', () => {
            setTema(getTema() === 'dark' ? 'light' : 'dark');
            actualizarIconoTema();
        });
        actualizarIconoTema();
    }
});

/**
 * Actualiza el ícono del toggle de tema en el navbar
 */
function actualizarIconoTema() {
    const btn = document.getElementById('btnToggleTema');
    if (!btn) return;
    btn.textContent = getTema() === 'dark' ? '☀️' : '🌙';
    btn.title       = getTema() === 'dark' ? 'Modo claro' : 'Modo oscuro';
}
