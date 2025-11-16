<?php
class Tracker_Activator {

    public static function activate() {
        global $wpdb;

        $charset = $wpdb->get_charset_collate();
        $table_logs = $wpdb->prefix . 'visitor_logs';
        $table_visitors = $wpdb->prefix . 'tracker_visitors';
        $table_pageviews = $wpdb->prefix . 'tracker_pageviews';

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        // 1. visitor_logs (raw events + last_visited)
        $sql_logs = "
        CREATE TABLE IF NOT EXISTS $table_logs (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            ip_address VARCHAR(45) NOT NULL,
            user_agent TEXT,
            page_url TEXT NOT NULL,
            client_id VARCHAR(255) DEFAULT NULL,
            referrer TEXT DEFAULT NULL,
            visited_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            last_visited DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            INDEX (visited_at),
            INDEX (ip_address)
        ) $charset;
        ";
        dbDelta($sql_logs);

        // 2. tracker_visitors (legacy, optional)
        $sql_visitors = "
        CREATE TABLE IF NOT EXISTS $table_visitors (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            client_id VARCHAR(255),
            ip_address VARCHAR(100),
            user_agent TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset;
        ";
        dbDelta($sql_visitors);

        // 3. tracker_pageviews (legacy, optional)
        $sql_pageviews = "
        CREATE TABLE IF NOT EXISTS $table_pageviews (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            client_id VARCHAR(255),
            page_url TEXT,
            referrer TEXT,
            timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset;
        ";
        dbDelta($sql_pageviews);
    }
}
