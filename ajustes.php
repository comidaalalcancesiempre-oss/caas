<?php require_once 'config.php'; ?>
<!DOCTYPE html>
<html lang="es" class="">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ajustes - C.A.A.S.</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>tailwind.config = { darkMode: 'class' }</script>
    <!-- Cargar tema ANTES del body para evitar flash -->
    <script>
        const _t = localStorage.getItem('caas_tema') || 'light';
        if (_t === 'dark') document.documentElement.classList.add('dark');
    </script>
    <script src="lang.js"></script>
    <script src="theme.js"></script>
</head>
<body class="bg-slate-100 dark:bg-gray-950 min-h-screen transition-colors duration-300">

<!-- ── NAVBAR ─────────────────────────────────────────────────── -->
<nav class="bg-white dark:bg-gray-900 border-b dark:border-gray-800 sticky top-0 z-50 shadow-sm">
    <div class="max-w-4xl mx-auto px-4 h-14 flex items-center justify-between">
        <a href="index.php" class="bg-orange-500 text-white font-black text-xl px-3 py-1 rounded-xl">C.A.A.S.</a>
        <div class="flex items-center gap-3">
            <!-- Toggle rápido -->
            <button id="btnToggleTema"
                    class="text-xl p-1.5 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-800 transition"
                    title="Cambiar tema">🌙</button>
            <a href="index.php"
               class="text-sm font-bold text-gray-500 dark:text-gray-400 hover:text-orange-500 transition">
                ← <span data-i18n="settings_back">Volver al inicio</span>
            </a>
        </div>
    </div>
</nav>

<!-- ── CONTENIDO ───────────────────────────────────────────────── -->
<main class="max-w-xl mx-auto px-4 py-10 space-y-6">

    <div class="text-center space-y-1">
        <h1 class="text-2xl font-black text-gray-900 dark:text-white" data-i18n="settings_title">Ajustes</h1>
        <p class="text-xs text-gray-400 dark:text-gray-500">Tus preferencias se guardan automáticamente en tu navegador.</p>
    </div>

    <!-- ── TEMA ─────────────────────────────────────────────────── -->
    <section class="bg-white dark:bg-gray-900 rounded-2xl border dark:border-gray-800 p-6 shadow-sm space-y-4">
        <h2 class="font-bold text-base text-gray-800 dark:text-gray-200 flex items-center gap-2">
            🎨 <span data-i18n="settings_theme">Apariencia</span>
        </h2>

        <div class="grid grid-cols-2 gap-4">
            <!-- Modo Claro -->
            <button id="btnLight" onclick="setTema('light')"
                    class="flex flex-col items-center gap-3 p-5 rounded-2xl border-2 border-gray-200 dark:border-gray-700 hover:border-orange-400 transition cursor-pointer">
                <div class="w-full h-20 rounded-xl bg-white border border-gray-200 flex items-center justify-center shadow-sm">
                    <div class="space-y-1.5 w-3/4">
                        <div class="h-2 bg-orange-400 rounded-full w-1/2"></div>
                        <div class="h-1.5 bg-gray-200 rounded-full"></div>
                        <div class="h-1.5 bg-gray-200 rounded-full w-3/4"></div>
                    </div>
                </div>
                <span class="text-sm font-bold text-gray-700 dark:text-gray-300" data-i18n="settings_light">Modo Claro</span>
            </button>

            <!-- Modo Oscuro -->
            <button id="btnDark" onclick="setTema('dark')"
                    class="flex flex-col items-center gap-3 p-5 rounded-2xl border-2 border-gray-200 dark:border-gray-700 hover:border-orange-400 transition cursor-pointer">
                <div class="w-full h-20 rounded-xl bg-gray-900 border border-gray-700 flex items-center justify-center shadow-sm">
                    <div class="space-y-1.5 w-3/4">
                        <div class="h-2 bg-orange-400 rounded-full w-1/2"></div>
                        <div class="h-1.5 bg-gray-700 rounded-full"></div>
                        <div class="h-1.5 bg-gray-700 rounded-full w-3/4"></div>
                    </div>
                </div>
                <span class="text-sm font-bold text-gray-700 dark:text-gray-300" data-i18n="settings_dark">Modo Oscuro</span>
            </button>
        </div>
    </section>

    <!-- ── IDIOMA ────────────────────────────────────────────────── -->
    <section class="bg-white dark:bg-gray-900 rounded-2xl border dark:border-gray-800 p-6 shadow-sm space-y-4">
        <h2 class="font-bold text-base text-gray-800 dark:text-gray-200 flex items-center gap-2">
            🌐 <span data-i18n="settings_lang">Idioma</span>
        </h2>

        <div class="grid grid-cols-1 gap-3" id="langGrid">

            <!-- Español -->
            <button onclick="elegirIdioma('es')" data-lang="es"
                    class="lang-btn flex items-center gap-4 p-4 rounded-2xl border-2 border-gray-200 dark:border-gray-700 hover:border-orange-400 transition text-left">
                <span class="text-3xl">🇺🇾</span>
                <div>
                    <p class="font-bold text-sm text-gray-900 dark:text-white">Español</p>
                    <p class="text-xs text-gray-400">Español (Uruguay)</p>
                </div>
            </button>

            <!-- English -->
            <button onclick="elegirIdioma('en')" data-lang="en"
                    class="lang-btn flex items-center gap-4 p-4 rounded-2xl border-2 border-gray-200 dark:border-gray-700 hover:border-orange-400 transition text-left">
                <span class="text-3xl">🇺🇸</span>
                <div>
                    <p class="font-bold text-sm text-gray-900 dark:text-white">English</p>
                    <p class="text-xs text-gray-400">English (US)</p>
                </div>
            </button>

            <!-- Português Brasil -->
            <button onclick="elegirIdioma('pt')" data-lang="pt"
                    class="lang-btn flex items-center gap-4 p-4 rounded-2xl border-2 border-gray-200 dark:border-gray-700 hover:border-orange-400 transition text-left">
                <span class="text-3xl">🇧🇷</span>
                <div>
                    <p class="font-bold text-sm text-gray-900 dark:text-white">Português</p>
                    <p class="text-xs text-gray-400">Português (Brasil)</p>
                </div>
            </button>

            <!-- 中文 -->
            <button onclick="elegirIdioma('zh')" data-lang="zh"
                    class="lang-btn flex items-center gap-4 p-4 rounded-2xl border-2 border-gray-200 dark:border-gray-700 hover:border-orange-400 transition text-left">
                <span class="text-3xl">🇨🇳</span>
                <div>
                    <p class="font-bold text-sm text-gray-900 dark:text-white">中文</p>
                    <p class="text-xs text-gray-400">Chino Simplificado</p>
                </div>
            </button>

            <!-- 日本語 -->
            <button onclick="elegirIdioma('ja')" data-lang="ja"
                    class="lang-btn flex items-center gap-4 p-4 rounded-2xl border-2 border-gray-200 dark:border-gray-700 hover:border-orange-400 transition text-left">
                <span class="text-3xl">🇯🇵</span>
                <div>
                    <p class="font-bold text-sm text-gray-900 dark:text-white">日本語</p>
                    <p class="text-xs text-gray-400">Japonés</p>
                </div>
            </button>

        </div>
    </section>

    <!-- Toast de confirmación -->
    <div id="toast"
         class="hidden fixed bottom-6 left-1/2 -translate-x-1/2 bg-gray-900 dark:bg-white text-white dark:text-gray-900 text-sm font-bold px-5 py-3 rounded-2xl shadow-2xl z-50 transition-all">
        ✓ <span data-i18n="settings_save">¡Preferencias guardadas!</span>
    </div>

</main>

<script>
function elegirIdioma(lang) {
    setLang(lang);
    marcarLangActivo(lang);
    mostrarToast();
}

function marcarLangActivo(lang) {
    document.querySelectorAll('.lang-btn').forEach(btn => {
        const activo = btn.getAttribute('data-lang') === lang;
        btn.classList.toggle('border-orange-500', activo);
        btn.classList.toggle('bg-orange-50',      activo);
        btn.classList.toggle('dark:bg-orange-950', activo);
        btn.classList.toggle('border-gray-200',   !activo);
        btn.classList.toggle('dark:border-gray-700', !activo);
    });
}

function mostrarToast() {
    const toast = document.getElementById('toast');
    toast.classList.remove('hidden');
    toast.classList.add('opacity-100');
    setTimeout(() => {
        toast.classList.add('hidden');
    }, 2500);
}

// Inicializar estado visual al cargar
document.addEventListener('DOMContentLoaded', () => {
    marcarLangActivo(getLang());
    actualizarBotonesTema(getTema());
});
</script>
</body>
</html>
