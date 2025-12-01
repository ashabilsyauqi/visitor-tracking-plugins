document.addEventListener('DOMContentLoaded', function () {
    const nonce = WA_DASHBOARD_API.nonce;

    const dailyCtx   = document.getElementById('dailyChart')?.getContext('2d');
    const weeklyCtx  = document.getElementById('weeklyChart')?.getContext('2d');
    const monthlyCtx = document.getElementById('monthlyChart')?.getContext('2d');

    const elDaily   = document.getElementById('totals-daily');
    const elWeekly  = document.getElementById('totals-weekly');
    const elMonthly = document.getElementById('totals-monthly');

    if (!dailyCtx || !weeklyCtx || !monthlyCtx || !elDaily || !elWeekly || !elMonthly) return;

    Promise.all([
        fetchSeries('daily', 30),
        fetchSeries('weekly', 12),
        fetchSeries('monthly', 12),
        fetchTotals('daily'),
        fetchTotals('weekly'),
        fetchTotals('monthly')
    ]).then(([daily, weekly, monthly, tDaily, tWeekly, tMonthly]) => {

        renderSmartChart(dailyCtx,   daily.data,   'Daily (last 30 days)');
        renderSmartChart(weeklyCtx,  weekly.data,  'Weekly (last 12 weeks)');
        renderSmartChart(monthlyCtx, monthly.data, 'Monthly (last 12 months)');

        elDaily.innerHTML   = `<strong>${tDaily.total_visitors}</strong> visitors · ${tDaily.pageviews} pageviews`;
        elWeekly.innerHTML  = `<strong>${tWeekly.total_visitors}</strong> visitors · ${tWeekly.pageviews} pageviews`;
        elMonthly.innerHTML = `<strong>${tMonthly.total_visitors}</strong> visitors · ${tMonthly.pageviews} pageviews`;

    }).catch(err => {
        console.error('Chart error:', err);
        [elDaily, elWeekly, elMonthly].forEach(el => el.innerHTML = 'Error');
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

    // INI RAHASIANYA — CHART YANG SUPER SMART
    function renderSmartChart(ctx, rawData, title) {
        const visitors  = rawData.map(d => parseInt(d.visitors)  || 0);
        const pageviews = rawData.map(d => parseInt(d.pageviews) || 0);
        const labels    = rawData.map(d => d.period);
    
        const allValues = [...visitors, ...pageviews];
        const maxData   = Math.max(...allValues, 1); // minimal 1 biar ga error
    
        // Paksa skala Y sesuai data asli + buffer kecil
        const suggestedMax = maxData <= 5 ? maxData + 2 : Math.ceil(maxData * 1.25);
        const stepSize     = maxData <= 10 ? 1 : (maxData <= 30 ? 2 : 5);
    
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Visitors',
                        data: visitors,
                        borderColor: '#2271b1',
                        backgroundColor: 'rgba(34,113,177,0.15)',
                        tension: 0.4,
                        fill: true,
                        pointRadius: 4,
                        pointHoverRadius: 7
                    },
                    {
                        label: 'Pageviews',
                        data: pageviews,
                        borderColor: '#46c27d',
                        backgroundColor: 'rgba(70,194,125,0.15)',
                        tension: 0.4,
                        fill: true,
                        pointRadius: 4,
                        pointHoverRadius: 7
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    title: { display: true, text: title, font: { size: 15 } },
                    legend: { position: 'bottom' },
                    tooltip: { mode: 'index', intersect: false }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        suggestedMin: 0,
                        suggestedMax: suggestedMax,
                        ticks: {
                            stepSize: stepSize,
                            maxTicksLimit: 8,
                            // INI YANG PALING PENTING — MATIIN SEMUA "NICE" SCALE
                            autoSkip: false,
                            callback: function(value) {
                                return value; // tampilin angka asli tanpa tambahan 0
                            }
                        },
                        grid: {
                            drawTicks: true,
                            tickLength: 8
                        }
                    },
                    x: {
                        ticks: {
                            maxRotation: 45,
                            minRotation: 45
                        }
                    }
                }
            }
        });
    }
});