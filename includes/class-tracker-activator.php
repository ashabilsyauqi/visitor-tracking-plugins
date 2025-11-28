<?php
class Tracker_Activator {

    public static function activate() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        // ==================================================================
        // 1. RAW LOG TABLE – Tetap seperti biasa (untuk debug & history)
        // ==================================================================
        $table_logs = $wpdb->prefix . 'visitor_logs';

        $sql_logs = "
        CREATE TABLE $table_logs (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            ip_address VARCHAR(45) NOT NULL,
            user_agent TEXT,
            page_url TEXT NOT NULL,
            client_id VARCHAR(64) DEFAULT NULL,
            referrer TEXT,
            visited_at DATETIME NOT NULL,
            last_visited DATETIME DEFAULT NULL,
            INDEX idx_visited_at (visited_at),
            INDEX idx_client_id (client_id),
            INDEX idx_ip (ip_address)
        ) $charset_collate;
        ";
        dbDelta($sql_logs);


        // ==================================================================
        // 2. NEW: Daily Unique Visitors – PAKAI client_id (COOKIE) sebagai kunci unik!
        // ==================================================================
        $table_daily_visitors = $wpdb->prefix . 'tracker_daily_visitors';

        $sql_daily_visitors = "
        CREATE TABLE $table_daily_visitors (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            client_id VARCHAR(64) NOT NULL,
            date DATE NOT NULL,
            ip VARCHAR(45) DEFAULT NULL,
            user_agent VARCHAR(500) DEFAULT NULL,
            first_seen DATETIME DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uniq_visitor_per_day (client_id, date),
            KEY idx_date (date),
            KEY idx_client (client_id)
        ) $charset_collate;
        ";
        dbDelta($sql_daily_visitors);


        // ==================================================================
        // 3. NEW: Daily Unique Pageviews – 1 page cuma dihitung 1x per visitor per hari
        // ==================================================================
        $table_daily_pageviews = $wpdb->prefix . 'tracker_daily_pageviews';

        $sql_daily_pageviews = "
        CREATE TABLE $table_daily_pageviews (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            client_id VARCHAR(64) NOT NULL,
            page VARCHAR(1000) NOT NULL,
            date DATE NOT NULL,
            first_seen DATETIME DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uniq_pageview_per_day (client_id, page(191), date),
            KEY idx_date (date),
            KEY idx_page (page(191)),
            KEY idx_client (client_id)
        ) $charset_collate;
        ";
        dbDelta($sql_daily_pageviews);


        // ==================================================================
        // OPTIONAL: Legacy tables (kalau masih mau dipertahankan)
        // ==================================================================
        $table_visitors  = $wpdb->prefix . 'tracker_visitors';
        $table_pageviews = $wpdb->prefix . 'tracker_pageviews';

        $sql_legacy_visitors = "
        CREATE TABLE $table_visitors (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            client_id VARCHAR(64),
            ip_address VARCHAR(45),
            user_agent TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uniq_client (client_id)
        ) $charset_collate;
        ";
        dbDelta($sql_legacy_visitors);

        $sql_legacy_pageviews = "
        CREATE TABLE $table_pageviews (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            client_id VARCHAR(64),
            page_url TEXT,
            referrer TEXT,
            timestamp DATETIME DEFAULT CURRENT_TIMESTAMP
        ) $charset_collate;
        ";
        dbDelta($sql_legacy_pageviews);

        // ==================================================================
        // Flush rewrite rules (just in case)
        // ==================================================================
        flush_rewrite_rules();
    }
}