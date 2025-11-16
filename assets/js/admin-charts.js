document.addEventListener('DOMContentLoaded', function () {
    const nonce = WA_DASHBOARD_API.nonce;

    // Elements
    const dailyCtx = document.getElementById('dailyChart').getContext('2d');
    const weeklyCtx = document.getElementById('weeklyChart').getContext('2d');
    const monthlyCtx = document.getElementById('monthlyChart').getContext('2d');

    // summary cards
    const elDailyTotals = document.getElementById('totals-daily');
    const elWeeklyTotals = document.getElementById('totals-weekly');
    const elMonthlyTotals = document.getElementById('totals-monthly');

    // fetch and render
    Promise.all([
        fetchSeries('daily', 30),
        fetchSeries('weekly', 12),
        fetchSeries('monthly', 12),
        fetchTotals('daily'),
        fetchTotals('weekly'),
        fetchTotals('monthly')
    ]).then(([daily, weekly, monthly, totalsDaily, totalsWeekly, totalsMonthly]) => {
        renderLineChart(dailyCtx, daily.data, 'Daily (last ' + daily.limit + ' days)');
        renderLineChart(weeklyCtx, weekly.data, 'Weekly (last ' + weekly.limit + ' weeks)');
        renderLineChart(monthlyCtx, monthly.data, 'Monthly (last ' + monthly.limit + ' months)');

        elDailyTotals.innerHTML = `<strong>${totalsDaily.total_visitors}</strong> visitors · ${totalsDaily.pageviews} pageviews`;
        elWeeklyTotals.innerHTML = `<strong>${totalsWeekly.total_visitors}</strong> visitors · ${totalsWeekly.pageviews} pageviews`;
        elMonthlyTotals.innerHTML = `<strong>${totalsMonthly.total_visitors}</strong> visitors · ${totalsMonthly.pageviews} pageviews`;
    }).catch(err => {
        console.error(err);
    });

    function fetchSeries(range, limit) {
        const url = new URL(WA_DASHBOARD_API.series);
        url.searchParams.set('range', range);
        url.searchParams.set('limit', limit);
        return fetch(url.toString(), {
            headers: { 'X-WP-Nonce': nonce }
        }).then(res => res.json());
    }

    function fetchTotals(range) {
        const url = new URL(WA_DASHBOARD_API.totals);
        url.searchParams.set('range', range);
        return fetch(url.toString(), {
            headers: { 'X-WP-Nonce': nonce }
        }).then(res => res.json());
    }

    function renderLineChart(ctx, dataArr, label) {
        const labels = dataArr.map(i => i.period);
        const visitors = dataArr.map(i => i.visitors);
        const pageviews = dataArr.map(i => i.pageviews);

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Visitors (unique)',
                        data: visitors,
                        borderWidth: 2,
                        tension: 0.3,
                        fill: false
                    },
                    {
                        label: 'Pageviews',
                        data: pageviews,
                        borderWidth: 2,
                        tension: 0.3,
                        fill: false
                    }
                ]
            },
            options: {
                responsive: true,
                plugins: {
                    title: { display: true, text: label }
                },
                interaction: { mode: 'index', intersect: false }
            }
        });
    }
});
