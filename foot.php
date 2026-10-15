<!-- ── Bootstrap 5 JS Bundle (incluye Popper) ───────────────────── -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
        crossorigin="anonymous"></script>

<!-- ── SweetAlert2 ──────────────────────────────────────────────── -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>

<!-- ── Lucide Icons ─────────────────────────────────────────────── -->
<script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js"></script>

<!-- ── Inicializar Lucide + SweetAlert2 tema ────────────────────── -->
<script>
// Renderizar todos los iconos Lucide en la página
// Uso: <i data-lucide="nombre-icono"></i>
lucide.createIcons();

// Configuración global de SweetAlert2
// Reemplaza todos los confirm() y alert() nativos
window.Swal = Swal;

// Toast de éxito global reutilizable
window.toastOk = (msg) => Swal.fire({
    toast: true, position: 'top-end', icon: 'success',
    title: msg, showConfirmButton: false, timer: 2500,
    timerProgressBar: true,
    background: document.documentElement.classList.contains('dark') ? '#111827' : '#fff',
    color:      document.documentElement.classList.contains('dark') ? '#f9fafb' : '#111827',
});

window.toastErr = (msg) => Swal.fire({
    toast: true, position: 'top-end', icon: 'error',
    title: msg, showConfirmButton: false, timer: 3000,
    timerProgressBar: true,
    background: document.documentElement.classList.contains('dark') ? '#111827' : '#fff',
    color:      document.documentElement.classList.contains('dark') ? '#f9fafb' : '#111827',
});

// Confirmar acción con SweetAlert2 (reemplaza confirm() nativo)
// Uso: await caasConfirm('¿Eliminar?', 'warning') → true/false
window.caasConfirm = async (msg, icon = 'question') => {
    const result = await Swal.fire({
        title: msg,
        icon,
        showCancelButton:    true,
        confirmButtonText:   'Confirmar',
        cancelButtonText:    'Cancelar',
        confirmButtonColor:  '#f97316',
        cancelButtonColor:   '#6b7280',
        background: document.documentElement.classList.contains('dark') ? '#111827' : '#fff',
        color:      document.documentElement.classList.contains('dark') ? '#f9fafb' : '#111827',
    });
    return result.isConfirmed;
};
</script>
