<?php
/**
 * C.A.A.S. — Cabecera HTML compartida
 * Incluir en cada página con: require_once 'head.php';
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
<!-- Se usa para: tooltips, badges, utilidades de grid y componentes -->
<link rel="stylesheet"
      href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
      crossorigin="anonymous">

<!-- ── Animate.css ──────────────────────────────────────────────── -->
<!-- Clases: animate__animated animate__fadeInUp, animate__bounceIn, etc. -->
<link rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"
      crossorigin="anonymous">

<!-- ── SweetAlert2 CSS ──────────────────────────────────────────── -->
<link rel="stylesheet"
      href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">

<!-- ── Tema C.A.A.S. — dark mode aplicado antes del paint ───────── -->
<script>
    (function() {
        const t = localStorage.getItem('caas_tema') || 'light';
        if (t === 'dark') document.documentElement.classList.add('dark');
    })();
</script>

<!-- ── Estilos globales C.A.A.S. ────────────────────────────────── -->
<style>
    /* Variables de color para consistencia */
    :root {
        --caas-orange:     #f97316;
        --caas-orange-dk:  #ea6c0a;
        --caas-bg-light:   #f8fafc;
        --caas-bg-dark:    #030712;
    }

    /* Scrollbar personalizada */
    ::-webkit-scrollbar { width: 6px; height: 6px; }
    ::-webkit-scrollbar-track { background: transparent; }
    ::-webkit-scrollbar-thumb { background: #f97316; border-radius: 99px; }

    /* Prevenir flash de Tailwind CDN */
    [x-cloak] { display: none !important; }

    /* Override Bootstrap para que no choque con Tailwind dark mode */
    .dark body   { background-color: var(--caas-bg-dark) !important; }
    .dark .card  { background-color: #111827 !important; border-color: #1f2937 !important; }

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

    /* Animaciones custom */
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
</style>

<?= $extraHead ?>

<!-- ── Traducciones + tema (carga síncrona antes del body) ───────── -->
<script src="lang.js"></script>
<script src="theme.js"></script>
