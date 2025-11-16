<?php

class Tracker_Collector {

    public static function get_daily_stats() {
        global $wpdb;
        $table = $wpdb->prefix . 'tracker_pageviews';

        return $wpdb->get_results("
            SELECT DATE(timestamp) as date, COUNT(*) as views
            FROM $table
            GROUP BY DATE(timestamp)
            ORDER BY date DESC
        ");
    }

}
