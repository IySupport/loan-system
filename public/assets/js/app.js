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

// function fmtMoney(n) {
//     n = parseFloat(n) || 0;
//     return 'R' + n.toLocaleString('en-ZA', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
// }
function fmtMoney(n) {
    n = parseFloat(n) || 0;
    return 'R' + n.toLocaleString('en-ZA', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

// For plain calendar dates (e.g. action_date) that come through as a bare
// "YYYY-MM-DD" string with no time component. Deliberately avoids `new
// Date(str)` - a date-only string like that gets parsed as UTC midnight
// by the JS spec, and converting that back to the browser's local
// timezone for display can roll it back (or forward) a day depending on
// the viewer's offset. Reformatting the string directly sidesteps
// timezones entirely, so the date shown always matches what's stored.
function fmtDate(dateStr) {
    if (!dateStr) return '';
    const [y, m, d] = String(dateStr).slice(0, 10).split('-');
    if (!y || !m || !d) return '';
    return `${y}/${m}/${d}`;
}

function groupBadgeClass(group) {
    if (group === 'Group 1') return 'group-1';
    if (group === 'Group 2') return 'group-2';
    return 'group-3';
}

function statusBadgeClass(status) {
    return 'status-' + String(status).toLowerCase().replace(/\s+/g, '-');
}
