<?php
/**
 * C.A.A.S. — Cabecera HTML compartida
 * Variables esperadas antes de incluir:
 *   $pageTitle (string) — título de la pestaña
 *   $extraHead (string) — HTML adicional opcional (ej: Leaflet CSS)
 */
$pageTitle = $pageTitle ?? 'C.A.A.S. - Comida Al Alcance Siempre';
$extraHead = $extraHead ?? '';
?>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($pageTitle) ?></title>

<!-- ── Tailwind CSS ──────────────────────────────────────────────── -->
<script src="https://cdn.tailwindcss.com"></script>
<script>tailwind.config = { darkMode: 'class' }</script>

<!-- ── Bootstrap 5.3 CSS ────────────────────────────────────────── -->
<link rel="stylesheet"
      href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
      crossorigin="anonymous">

<!-- ── Animate.css ──────────────────────────────────────────────── -->
<link rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"
      crossorigin="anonymous">

<!-- ── SweetAlert2 CSS ──────────────────────────────────────────── -->
<link rel="stylesheet"
      href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">

<!-- ── Google Translate (carga async, sin bloquear el render) ─────
     El div #google_translate_element queda oculto con CSS.
     Nuestros botones en ajustes.php/navbar llaman a switchGoogleLang()
     para controlar el idioma de forma programática.
──────────────────────────────────────────────────────────────────── -->
<script src="https://translate.google.com/translate_a/element.js?cb=googleTranslateElementInit"
        async defer></script>

<!-- ── Tema dark/light — aplicar ANTES del paint para evitar flash ─ -->
<script>
(function() {
    const t = localStorage.getItem('caas_tema') || 'light';
    if (t === 'dark') document.documentElement.classList.add('dark');
})();
</script>

<!-- ── Estilos globales C.A.A.S. ────────────────────────────────── -->
<style>
    :root {
        --caas-orange:    #f97316;
        --caas-orange-dk: #ea6c0a;
    }

    /* Scrollbar personalizada */
    ::-webkit-scrollbar { width: 6px; height: 6px; }
    ::-webkit-scrollbar-track { background: transparent; }
    ::-webkit-scrollbar-thumb { background: var(--caas-orange); border-radius: 99px; }

    /* Botón naranja global */
    .btn-caas {
        background-color: var(--caas-orange);
        color: white;
        font-weight: 700;
        border-radius: 0.75rem;
        border: none;
        transition: background-color .2s, transform .1s;
    }
    .btn-caas:hover  { background-color: var(--caas-orange-dk); color: white; }
    .btn-caas:active { transform: scale(0.97); }

    /* Animaciones personalizadas */
    @keyframes fadeInUp {
        from { opacity: 0; transform: translateY(18px); }
        to   { opacity: 1; transform: translateY(0); }
    }
    .fade-in   { animation: fadeInUp .35s ease both; }
    .fade-in-2 { animation: fadeInUp .35s ease .08s both; opacity: 0; animation-fill-mode: forwards; }
    .fade-in-3 { animation: fadeInUp .35s ease .16s both; opacity: 0; animation-fill-mode: forwards; }

    /* Skeleton loading */
    .skeleton {
        background: linear-gradient(90deg, #f1f5f9 25%, #e2e8f0 50%, #f1f5f9 75%);
        background-size: 200% 100%;
        animation: shimmer 1.4s infinite;
        border-radius: 0.5rem;
    }
    .dark .skeleton {
        background: linear-gradient(90deg, #1f2937 25%, #374151 50%, #1f2937 75%);
        background-size: 200% 100%;
    }
    @keyframes shimmer { 0% { background-position: 200% 0; } 100% { background-position: -200% 0; } }

    /* Ocultar barra de Google Translate */
    .goog-te-banner-frame,
    .goog-te-balloon-frame,
    #goog-gt-tt,
    .goog-te-spinner-pos { display: none !important; }
    body { top: 0 !important; position: static !important; }
    #google_translate_element { display: none !important; }
    .goog-text-highlight { background: none !important; box-shadow: none !important; }
</style>

<?= $extraHead ?>

<!-- Div oculto requerido por Google Translate -->
<div id="google_translate_element" style="display:none;"></div>

<!-- ── Sistema de idiomas C.A.A.S. ─────────────────────────────── -->
<script src="lang.js"></script>
<script src="theme.js"></script>
<script src="translate.js"></script>
