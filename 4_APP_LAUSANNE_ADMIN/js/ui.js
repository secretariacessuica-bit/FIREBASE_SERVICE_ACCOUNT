/**
 * Módulo UI Core
 * Responsabilidade: Interações transversais de interface.
 */

function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    if (sidebar) {
        sidebar.classList.toggle('open');
    }
}
