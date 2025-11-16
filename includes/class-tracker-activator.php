<?php

class Tracker_Activator {

    public static function activate() {
        global $wpdb;

        $charset = $wpdb->get_charset_collate();

        $table1 = $wpdb->prefix . 'tracker_visitors';
        $table2 = $wpdb->prefix . 'tracker_pageviews';

        $sql = "
        CREATE TABLE $table1 (
            id BIGINT AUTO_INCREMENT PRIMARY KEY,
            client_id VARCHAR(255),
            ip_address VARCHAR(100),
            user_agent TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        ) $charset;

        CREATE TABLE $table2 (
            id BIGINT AUTO_INCREMENT PRIMARY KEY,
            client_id VARCHAR(255),
            page_url TEXT,
            referrer TEXT,
            timestamp DATETIME DEFAULT CURRENT_TIMESTAMP
        ) $charset;
        ";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }

}
