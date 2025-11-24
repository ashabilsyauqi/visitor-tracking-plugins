<?php
// =========================
// Ambil Top 5 Page (langsung dari DB)
// =========================
global $wpdb;
$table = $wpdb->prefix . 'visitor_logs';

$top_pages = $wpdb->get_results("
    SELECT page_url, COUNT(*) AS total_views
    FROM $table
    WHERE page_url != ''
    GROUP BY page_url
    ORDER BY total_views DESC
    LIMIT 5
");
?>

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

    <!-- ============================== -->
    <!--       TOP 5 HALAMAN            -->
    <!-- ============================== -->
    <div class="wa-card" style="margin-top:20px;">
        <h2>Top 5 Halaman Paling Ramai Dikunjungi</h2>

        <?php if (!empty($top_pages)) : ?>
            <table class="widefat striped">
                <thead>
                    <tr>
                        <th>Halaman</th>
                        <th>Total Kunjungan</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($top_pages as $page) : ?>
                        <tr>
                            <td><?php echo esc_html($page->page_url); ?></td>
                            <td><?php echo esc_html($page->total_views); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else : ?>
            <p>Belum ada data kunjungan.</p>
        <?php endif; ?>
    </div>

</div>
