document.addEventListener('DOMContentLoaded', function () {
    const sidebar   = document.getElementById('sidebar');
    const overlay   = document.getElementById('sidebarOverlay');
    const hamburger = document.getElementById('hamburgerBtn');

    function openSidebar()  { sidebar.classList.add('open');  overlay.classList.add('show'); }
    function closeSidebar() { sidebar.classList.remove('open'); overlay.classList.remove('show'); }

    if (hamburger) hamburger.addEventListener('click', function () {
        sidebar.classList.contains('open') ? closeSidebar() : openSidebar();
    });
    if (overlay) overlay.addEventListener('click', closeSidebar);
});

function fmtMoney(n) {
    n = parseFloat(n) || 0;
    return 'R' + n.toLocaleString('en-ZA', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function groupBadgeClass(group) {
    if (group === 'Group 1') return 'group-1';
    if (group === 'Group 2') return 'group-2';
    return 'group-3';
}

function statusBadgeClass(status) {
    return 'status-' + String(status).toLowerCase().replace(/\s+/g, '-');
}
