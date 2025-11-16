document.addEventListener("DOMContentLoaded", async () => {

    const res = await fetch(WA_DASHBOARD_API.daily, {
        headers: { "X-WP-Nonce": WA_DASHBOARD_API.nonce }
    });

    const data = await res.json();

    let labels = data.map(i => i.date);
    let values = data.map(i => i.views);

    const ctx = document.getElementById("dailyChart");

    new Chart(ctx, {
        type: "line",
        data: {
            labels,
            datasets: [{
                label: "Daily Pageviews",
                data: values,
                borderWidth: 2,
                borderColor: "rgba(75, 192, 192, 1)",
                fill: false,
                tension: 0.3
            }]
        }
    });
});

