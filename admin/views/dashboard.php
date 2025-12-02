<?php
/**
 * Visitor Analytics Dashboard - FULL FIXED VERSION
 * Termasuk Top 5 Pages dengan filter bot yang benar & aman
 */

global $wpdb;
$table = $wpdb->prefix . 'visitor_logs';

// ====================================================================
// 1. TOP 5 HALAMAN (Human Only) – FILTER BOT YANG BENAR & AMAN
// ====================================================================

// Daftar keyword bot (di-update & ditambahin yang sering lolos)
$bot_keywords = 'bot|crawl|spider|slurp|mediapartners|adsbot|bingbot|yandex|baidu|duckduck|facebookexternalhit|whatsapp|telegram|twitterbot|headless|chrome-lighthouse|gtmetrix|pingdom|semrush|ahrefs|mj12bot|dotbot|petalbot|bytespider|monitor|archive|scraper|feedfetcher|apachebench|siege|locust|python|requests|curl|wget|java|go-http|node-fetch';

// Query utama dengan prepare() biar aman + LOWER() biar case-insensitive
$top_pages = $wpdb->get_results(
    $wpdb->prepare("
        SELECT 
            page_url, 
            COUNT(*) AS total_views
        FROM $table
        WHERE page_url != ''
          AND (
                user_agent IS NULL 
             OR user_agent = '' 
             OR LOWER(user_agent) NOT REGEXP %s
              )
          AND (is_bot IS NULL OR is_bot = 0)
        GROUP BY page_url
        ORDER BY total_views DESC
        LIMIT 5
    ", $bot_keywords)
);

// Kalau masih kosong semua (jarang banget), fallback tanpa filter user_agent
if (empty($top_pages)) {
    $top_pages = $wpdb->get_results("
        SELECT 
            page_url, 
            COUNT(*) AS total_views
        FROM $table
        WHERE page_url != ''
        GROUP BY page_url
        ORDER BY total_views DESC
        LIMIT 5
    ");
    $fallback_used = true; // buat kasih catatan
} else {
    $fallback_used = false;
}
?>

<div class="wrap">
    <h1>Visitor Analytics Dashboard</h1>

    <!-- Summary Cards -->
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

    <!-- Daily Chart -->
    <div class="wa-card" style="margin-bottom:16px;">
        <h2>Daily (last 30 days)</h2>
        <canvas id="dailyChart" height="320"></canvas>
    </div>

    <!-- Weekly + Monthly Charts -->
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

    <!-- TOP 5 HALAMAN (Human Only) -->
    <div class="wa-card" style="margin-top:20px;">
        <h2>Top 5 Halaman Paling Ramai Dikunjungi (Human Only)</h2>

        <?php if (!empty($top_pages)) : ?>
            <table class="widefat striped">
                <thead>
                    <tr>
                        <th width="70%">Halaman</th>
                        <th width="30%" style="text-align:right;">Kunjungan</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($top_pages as $page) : ?>
                        <tr>
                            <td>
                                <a href="<?php echo esc_url($page->page_url); ?>" target="_blank" style="word-break:break-all;">
                                    <?php 
                                    $display_url = $page->page_url;
                                    if (strlen($display_url) > 90) {
                                        $display_url = substr($display_url, 0, 87) . '...';
                                    }
                                    echo esc_html($display_url);
                                    ?>
                                </a>
                            </td>
                            <td style="text-align:right;font-weight:bold;">
                                <?php echo number_format($page->total_views); ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <?php if (!empty($fallback_used)) : ?>
                <p style="font-size:12px;color:#856404;background:#fff3cd;padding:8px;border:1px solid #ffe58f;border-radius:4px;margin-top:12px;">
                    Catatan: Data di atas termasuk kunjungan bot karena filter user_agent terlalu ketat atau banyak user_agent kosong.
                </p>
            <?php endif; ?>

        <?php else : ?>
            <p>Belum ada kunjungan tercatat sama sekali. Sabar ya, pengunjung pasti datang kok!</p>
        <?php endif; ?>
    </div>
</div>