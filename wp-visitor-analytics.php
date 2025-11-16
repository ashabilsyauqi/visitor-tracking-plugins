<?php
/**
 * Plugin Name: WP Visitor Analytics
 * Description: Custom visitor analytics plugin (tracking + admin charts).
 * Version: 1.1
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

// Deactivation / uninstall handled in uninstall.php

// Frontend: inject tracker
add_action('wp_enqueue_scripts', function () {
    wp_enqueue_script(
        'wa-tracker',
        plugin_dir_url(__FILE__) . 'assets/js/tracker.js',
        [],
        '1.1',
        true
    );

    wp_localize_script('wa-tracker', 'WA_API', [
        'endpoint' => rest_url('wpva/v1/track')
    ]);
});

// Admin: enqueue Chart.js + admin script for our dashboard only
add_action('admin_enqueue_scripts', function ($hook) {
    if ($hook !== 'toplevel_page_wa-dashboard') return;

    // Chart.js from CDN
    wp_enqueue_script('chartjs', 'https://cdn.jsdelivr.net/npm/chart.js', [], null);

    // admin charts script
    wp_enqueue_script(
        'wa-admin-charts',
        plugin_dir_url(__FILE__) . 'assets/js/admin-charts.js',
        ['chartjs'],
        '1.0',
        true
    );

    wp_localize_script('wa-admin-charts', 'WA_DASHBOARD_API', [
        'series' => rest_url('wpva/v1/stats/series'),
        'totals' => rest_url('wpva/v1/stats'),
        'nonce'  => wp_create_nonce('wp_rest')
    ]);

    // admin styles
    wp_enqueue_style('wa-admin-css', plugin_dir_url(__FILE__) . 'assets/css/admin.css');
});

// Register REST API
add_action('rest_api_init', function () {
    (new Tracker_REST())->register_routes();
});

// Register Admin Dashboard page
add_action('admin_menu', function () {
    add_menu_page(
        'Visitor Analytics',
        'Analytics',
        'manage_options',
        'wa-dashboard',
        ['Tracker_Dashboard', 'render'],
        'dashicons-chart-line',
        60
    );
});

// Server-side tracking fallback
add_action('template_redirect', function () {
    if (is_admin()) return; // jangan hit admin
    if (wp_doing_ajax()) return;
    if (defined('REST_REQUEST') && REST_REQUEST) return;

    $collector = new Tracker_Collector();
    $collector->log_request();
});

