<div class="wrap">
    <h1>Visitor Analytics Dashboard</h1>

    <div class="wa-row" style="margin-bottom:16px;">
        <div class="wa-col wa-card">
            <h3>Today</h3>
            <div id="totals-daily" class="wa-summary">Loading...</div>
        </div>
        <div class="wa-col wa-card">
            <h3>This Week</h3>
            <div id="totals-weekly" class="wa-summary">Loading...</div>
        </div>
        <div class="wa-col wa-card">
            <h3>This Month</h3>
            <div id="totals-monthly" class="wa-summary">Loading...</div>
        </div>
    </div>

    <div class="wa-card" style="margin-bottom:16px;">
        <h2>Daily (last 30 days)</h2>
        <canvas id="dailyChart"></canvas>
    </div>

    <div class="wa-row">
        <div class="wa-col wa-card">
            <h2>Weekly (last 12 weeks)</h2>
            <canvas id="weeklyChart"></canvas>
        </div>
        <div class="wa-col wa-card">
            <h2>Monthly (last 12 months)</h2>
            <canvas id="monthlyChart"></canvas>
        </div>
    </div>
</div>
