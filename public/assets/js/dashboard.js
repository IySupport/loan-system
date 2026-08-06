document.addEventListener('DOMContentLoaded', function () {
    const tealPalette = ['#14a085', '#0f8069', '#57bfa8', '#8fd6c5', '#0a5c4c'];

    new Chart(document.getElementById('branchChart'), {
        type: 'bar',
        data: {
            labels: branchLabels,
            datasets: [{ label: 'Loans', data: branchData, backgroundColor: '#14a085', borderRadius: 6, maxBarThickness: 46 }]
        },
        options: {
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true, grid: { color: '#eef2f3' } }, x: { grid: { display: false } } }
        }
    });

    new Chart(document.getElementById('monthChart'), {
        type: 'line',
        data: {
            labels: monthLabels,
            datasets: [{
                label: 'Loans', data: monthData, borderColor: '#14a085',
                backgroundColor: 'rgba(20,160,133,0.12)', fill: true, tension: 0.35, pointRadius: 3,
            }]
        },
        options: {
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true, grid: { color: '#eef2f3' } }, x: { grid: { display: false } } }
        }
    });
});
