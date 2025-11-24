document.addEventListener('DOMContentLoaded', function () {
    const nonce = WA_DASHBOARD_API.nonce;

    // Canvas
    const dailyCtx = document.getElementById('dailyChart')?.getContext('2d');
    const weeklyCtx = document.getElementById('weeklyChart')?.getContext('2d');
    const monthlyCtx = document.getElementById('monthlyChart')?.getContext('2d');

    // Summary
    const elDailyTotals = document.getElementById('totals-daily');
    const elWeeklyTotals = document.getElementById('totals-weekly');
    const elMonthlyTotals = document.getElementById('totals-monthly');

    if (!dailyCtx || !weeklyCtx || !monthlyCtx || !elDailyTotals || !elWeeklyTotals || !elMonthlyTotals) {
        console.error('WPVA: Required elements not found');
        return;
    }

    // CHARTS
    Promise.all([
        fetchSeries('daily', 30),
        fetchSeries('weekly', 12),
        fetchSeries('monthly', 12),
        fetchTotals('daily'),
        fetchTotals('weekly'),
        fetchTotals('monthly')
    ]).then(([daily, weekly, monthly, totalsDaily, totalsWeekly, totalsMonthly]) => {
        renderLineChart(dailyCtx, daily.data, `Daily (last ${daily.limit} days)`);
        renderLineChart(weeklyCtx, weekly.data, `Weekly (last ${weekly.limit} weeks)`);
        renderLineChart(monthlyCtx, monthly.data, `Monthly (last ${monthly.limit} months)`);

        elDailyTotals.innerHTML = `<strong>${totalsDaily.total_visitors}</strong> visitors · ${totalsDaily.pageviews} pageviews`;
        elWeeklyTotals.innerHTML = `<strong>${totalsWeekly.total_visitors}</strong> visitors · ${totalsWeekly.pageviews} pageviews`;
        elMonthlyTotals.innerHTML = `<strong>${totalsMonthly.total_visitors}</strong> visitors · ${totalsMonthly.pageviews} pageviews`;
    }).catch(err => {
        console.error('Chart Error:', err);
        ['daily', 'weekly', 'monthly'].forEach(p => {
            document.getElementById(`totals-${p}`).innerHTML = '<span style="color:red;">Failed</span>';
        });
    });

    function fetchSeries(range, limit) {
        const url = new URL(WA_DASHBOARD_API.series);
        url.searchParams.set('range', range);
        url.searchParams.set('limit', limit);
        return fetch(url, { headers: { 'X-WP-Nonce': nonce } }).then(r => r.json());
    }

    function fetchTotals(range) {
        const url = new URL(WA_DASHBOARD_API.totals);
        url.searchParams.set('range', range);
        return fetch(url, { headers: { 'X-WP-Nonce': nonce } }).then(r => r.json());
    }

    function renderLineChart(ctx, data, title) {
        const labels = data.map(i => i.period);
        const visitors = data.map(i => i.visitors);
        const pageviews = data.map(i => i.pageviews);

        new Chart(ctx, {
            type: 'line',
            data: {
                labels,
                datasets: [
                    { label: 'Visitors', data: visitors, borderColor: '#2271b1', backgroundColor: 'rgba(34,113,177,0.1)', tension: 0.3, fill: true },
                    { label: 'Pageviews', data: pageviews, borderColor: '#46c27d', backgroundColor: 'rgba(70,194,125,0.1)', tension: 0.3, fill: true }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { title: { display: true, text: title } },
                scales: { y: { beginAtZero: true } }
            }
        });
    }

    // TABEL PENGUNJUNG
    let visitorsOffset = 0;
    const visitorsLimit = 50;

    function loadVisitors(replace = true) {
        const tbody = document.getElementById('visitors-table-body');
        const btn = document.getElementById('load-more-visitors');

        if (replace) {
            tbody.innerHTML = '<tr><td colspan="4">Loading...</td></tr>';
            visitorsOffset = 0;
        }

        fetch(`${WA_DASHBOARD_API.visitors}?offset=${visitorsOffset}&limit=${visitorsLimit}`, {
            headers: { 'X-WP-Nonce': nonce }
        })
        .then(r => r.json())
        .then(data => {
            if (replace) tbody.innerHTML = '';

            if (data.data.length === 0) {
                btn.style.display = 'none';
                if (visitorsOffset === 0) tbody.innerHTML = '<tr><td colspan="4">No data.</td></tr>';
                return;
            }

            data.data.forEach(row => {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td><code>${row.ip_address}</code></td>
                    <td><small>${row.user_agent.substring(0, 60)}...</small></td>
                    <td><a href="${row.url}" target="_blank">${row.url.substring(0, 50)}${row.url.length > 50 ? '...' : ''}</a></td>
                    <td>${new Date(row.timestamp).toLocaleString()}</td>
                `;
                tbody.appendChild(tr);
            });

            visitorsOffset += data.data.length;
            btn.style.display = data.data.length < visitorsLimit ? 'none' : 'block';
        })
        .catch(() => {
            tbody.innerHTML = '<tr><td colspan="4" style="color:red;">Failed to load.</td></tr>';
        });
    }

    loadVisitors();
    document.getElementById('load-more-visitors')?.addEventListener('click', () => loadVisitors(false));


    // =========================
    // TOP 5 PAGES CHART
    // =========================
    const topPagesCanvas = document.getElementById('topPagesChart')?.getContext('2d');

    if (topPagesCanvas && window.WA_TOP_PAGES) {
        const labels = window.WA_TOP_PAGES.map(i => i.page);
        const views = window.WA_TOP_PAGES.map(i => i.views);

        new Chart(topPagesCanvas, {
            type: 'bar',
            data: {
                labels,
                datasets: [{
                    label: 'Total Views',
                    data: views,
                    backgroundColor: 'rgba(34,113,177,0.3)',
                    borderColor: '#2271b1',
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    title: { display: true, text: 'Top 5 Pages' }
                },
                scales: { y: { beginAtZero: true } }
            }
        });
    }

});