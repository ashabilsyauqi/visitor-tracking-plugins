document.addEventListener('DOMContentLoaded', function () {
    const ctx = document.getElementById('visitorLineChart').getContext('2d');
    const nonce = WA_DASHBOARD_API.nonce;

    fetch(WA_DASHBOARD_API.series + '?range=daily&limit=30', {
        headers: { 'X-WP-Nonce': nonce }
    })
    .then(res => res.json())
    .then(result => {
        const data = result.data;
        const labels = data.map(i => i.period);
        const visitors = data.map(i => i.visitors);
        const pageviews = data.map(i => i.pageviews);

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [
                    { label: 'Visitors', data: visitors, borderColor: 'rgb(54,162,235)', tension: 0.4, fill: true },
                    { label: 'Pageviews', data: pageviews, borderColor: 'rgb(255,99,132)', tension: 0.4, fill: true }
                ]
            },
            options: {
                responsive: true,
                plugins: { title: { display: true, text: 'Daily Visitor Analytics' } }
            }
        });
    })
    .catch(err => {
        console.error(err);
        ctx.canvas.parentNode.innerHTML = '<p style="color:red;">Gagal load data chart.</p>';
    });
});