<?php
/**
 * Plugin Name: WP Visitor Analytics
 * Description: Custom visitor analytics plugin.
 * Version: 1.0
 * Author: Ashabil
 */

if (!defined('ABSPATH')) exit;

// Includes
require_once plugin_dir_path(__FILE__) . 'includes/class-tracker-activator.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-tracker-rest.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-tracker-dashboard.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-tracker-collector.php';
require_once plugin_dir_path(__FILE__) . 'includes/helpers.php';

// Activation
register_activation_hook(__FILE__, ['Tracker_Activator', 'activate']);

// Inject JS
add_action('wp_enqueue_scripts', function () {
    wp_enqueue_script(
        'wa-tracker',
        plugin_dir_url(__FILE__) . 'assets/js/tracker.js',
        [],
        '1.0',
        true
    );

    wp_localize_script('wa-tracker', 'WA_API', [
        'endpoint' => rest_url('analytics/v1/track')
    ]);
});

// Register REST API
add_action('rest_api_init', function () {
    (new Tracker_REST())->register_routes();
});

// Register Admin Dashboard
add_action('admin_menu', function () {
    add_menu_page(
        'Visitor Analytics',
        'Analytics',
        'manage_options',
        'wa-dashboard',
        ['Tracker_Dashboard', 'render'],
        'dashicons-chart-line'
    );
});

add_action('admin_enqueue_scripts', function ($hook) {
    if ($hook !== 'toplevel_page_wa-dashboard') return;

    wp_enqueue_script(
        'chartjs',
        'https://cdn.jsdelivr.net/npm/chart.js',
        [],
        null
    );

    wp_enqueue_script(
        'wa-admin-charts',
        plugin_dir_url(__FILE__) . 'assets/js/admin-charts.js',
        ['chartjs'],
        '1.0',
        true
    );

    wp_localize_script('wa-admin-charts', 'WA_DASHBOARD_API', [
        'daily' => rest_url('analytics/v1/stats/daily'),
        'nonce' => wp_create_nonce('wp_rest')
    ]);
});

