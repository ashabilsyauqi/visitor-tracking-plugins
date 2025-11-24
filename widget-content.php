<?php
if (!defined('ABSPATH')) exit;
?>
<div class="myplugin-analytics">

    <!-- TABS -->
    <div style="margin-bottom:15px; text-align:center;">
        <button class="tab-btn active" data-tab="daily">Daily</button>
        <button class="tab-btn" data-tab="weekly">Weekly</button>
        <button class="tab-btn" data-tab="monthly">Monthly</button>
    </div>

    <!-- DAILY -->
    <div id="daily-tab" class="tab-content active">
        <canvas id="dailyChart" height="180"></canvas>
        <div id="totals-daily" style="margin-top:10px; text-align:center; font-weight:bold; color:#2271b1;"></div>
    </div>

    <!-- WEEKLY -->
    <div id="weekly-tab" class="tab-content" style="display:none;">
        <canvas id="weeklyChart" height="180"></canvas>
        <div id="totals-weekly" style="margin-top:10px; text-align:center; font-weight:bold; color:#46c27d;"></div>
    </div>

    <!-- MONTHLY -->
    <div id="monthly-tab" class="tab-content" style="display:none;">
        <canvas id="monthlyChart" height="180"></canvas>
        <div id="totals-monthly" style="margin-top:10px; text-align:center; font-weight:bold; color:#c2185b;"></div>
    </div>

</div>

<?php
// ENQUEUE
wp_enqueue_script('chart-js', 'https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js', [], null, true);
wp_enqueue_script('myplugin-charts', plugin_dir_url(__FILE__) . 'assets/admin-charts.js', ['chart-js'], '1.0', true);
wp_localize_script('myplugin-charts', 'WA_DASHBOARD_API', [
    'series' => rest_url('myplugin/v1/analytics/series'),
    'totals' => rest_url('myplugin/v1/analytics/totals'),
    'nonce'  => wp_create_nonce('wp_rest')
]);
wp_enqueue_style('myplugin-admin-css', plugin_dir_url(__FILE__) . 'assets/admin.css');
?>

<style>
.myplugin-analytics { font-family: -apple-system, sans-serif; }
.tab-btn {
    padding: 8px 16px; margin: 0 4px; border: none; background: #f1f1f1; cursor: pointer; border-radius: 6px;
}
.tab-btn.active { background: #0073aa; color: white; }
</style>

<script>
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('.tab-content').forEach(tab => tab.style.display = 'none');
            document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
            document.getElementById(btn.dataset.tab + '-tab').style.display = 'block';
            btn.classList.add('active');
        });
    });
});
</script>