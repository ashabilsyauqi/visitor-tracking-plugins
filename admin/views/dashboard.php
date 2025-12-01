<?php
// =========================
// Top 5 Pages – DENGAN FILTER BOT TEBAL
// =========================
global $wpdb;
$table = $wpdb->prefix . 'visitor_logs';

$bot_keywords = "bot|crawl|spider|slurp|mediapartners|adsbot|bingbot|yandex|baidu|duckduck|facebookexternalhit|whatsapp|telegram|twitterbot|headless|chrome-lighthouse|gtmetrix|pingdom|semrush|ahrefs|mj12bot|dotbot|petalbot|monitor|archive|scraper";

$top_pages = $wpdb->get_results("
    SELECT 
        page_url, 
        COUNT(*) AS total_views
    FROM $table
    WHERE page_url != ''
      AND user_agent NOT REGEXP '$bot_keywords'
      AND (is_bot IS NULL OR is_bot = 0)
    GROUP BY page_url
    ORDER BY total_views DESC
    LIMIT 5
");
?>

<div class="wrap">
    <h1>Visitor Analytics Dashboard</h1>

    <div class="wa-row" style="margin-bottom:16px;display:flex;gap:16px;flex-wrap:wrap;">
        <div class="wa-col wa-card" style="flex:1;min-width:200px;">
            <h3>Today</h3>
            <div id="totals-daily" class="wa-summary">Loading...</div>
        </div>
        <div class="wa-col wa-card" style="flex:1;min-width:200px;">
            <h3>This Week</h3>
            <div id="totals-weekly" class="wa-summary">Loading...</div>
        </div>
        <div class="wa-col wa-card" style="flex:1;min-width:200px;">
            <h3>This Month</h3>
            <div id="totals-monthly" class="wa-summary">Loading...</div>
        </div>
    </div>

    <div class="wa-card" style="margin-bottom:16px;">
        <h2>Daily (last 30 days)</h2>
        <canvas id="dailyChart" height="320"></canvas>
    </div>

    <div class="wa-row" style="display:flex;gap:16px;flex-wrap:wrap;">
        <div class="wa-col wa-card" style="flex:1;">
            <h2>Weekly (last 12 weeks)</h2>
            <canvas id="weeklyChart" height="280"></canvas>
        </div>
        <div class="wa-col wa-card" style="flex:1;">
            <h2>Monthly (last 12 months)</h2>
            <canvas id="monthlyChart" height="280"></canvas>
        </div>
    </div>

    <!-- TOP 5 HALAMAN -->
    <div class="wa-card" style="margin-top:20px;">
        <h2>Top 5 Halaman Paling Ramai Dikunjungi (Human Only)</h2>
        <?php if (!empty($top_pages)) : ?>
            <table class="widefat striped">
                <thead>
                    <tr>
                        <th>Halaman</th>
                        <th style="text-align:right;">Kunjungan</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($top_pages as $page) : ?>
                        <tr>
                            <td><a href="<?php echo esc_url($page->page_url); ?>" target="_blank"><?php echo esc_html($page->page_url); ?></a></td>
                            <td style="text-align:right;font-weight:bold;"><?php echo number_format($page->total_views); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else : ?>
            <p>Belum ada kunjungan manusia tercatat.</p>
        <?php endif; ?>
    </div>
</div>