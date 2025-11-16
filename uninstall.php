<?php
if (!defined('WP_UNINSTALL_PLUGIN')) exit;

global $wpdb;
$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}visitor_logs");
$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}tracker_visitors");
$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}tracker_pageviews");
