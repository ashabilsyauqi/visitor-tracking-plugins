<?php
/**
 * Dashboard Widget Registration
 * @package WP Visitor Analytics
 */

if (!defined('ABSPATH')) exit;

// Register dashboard widget
function wpva_add_dashboard_widget() {
    wp_add_dashboard_widget(
        'wpva_visitor_analytics',
        'Visitor Analytics',
        'wpva_render_dashboard_widget'
    );
}
add_action('wp_dashboard_setup', 'wpva_add_dashboard_widget', 10);

/**
 * Render the widget content
 * Includes: widget-content.php
 */
function wpva_render_dashboard_widget() {
    // Pastikan file ada
    $content_file = plugin_dir_path(__FILE__) . 'widget-content.php';
    if (file_exists($content_file)) {
        include $content_file;
    } else {
        echo '<p style="color:red;">Error: widget-content.php not found!</p>';
    }
}