<?php
class Tracker_Collector {

    // server-side: log current request
    public function log_request() {
        global $wpdb;
        $table = $wpdb->prefix . 'visitor_logs';

        // IP detection dengan fallback
        $ip = '';
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
        } elseif (!empty($_SERVER['REMOTE_ADDR'])) {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        if (!$ip) $ip = '127.0.0.1'; // fallback default

        // ambil info halaman & browser
        $page_url = $_SERVER['REQUEST_URI'] ?? '';
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $referrer = $_SERVER['HTTP_REFERER'] ?? '';
        $client_id = null; // optional, bisa diisi JS nanti

        // insert ke DB
        $res = $wpdb->insert($table, [
            'ip_address' => $ip,
            'user_agent' => $user_agent,
            'page_url'   => $page_url,
            'client_id'  => $client_id,
            'referrer'   => $referrer,
            'visited_at' => current_time('mysql', 1)
        ], ['%s','%s','%s','%s','%s','%s']);

        // debug log
        if ($res === false) {
            error_log("TRACK FAILED: " . $wpdb->last_error . " | URL: $page_url | IP: $ip");
        } else {
            error_log("TRACK OK: $page_url | IP: $ip");
        }
    }

    // helper: get daily totals (for dashboard/chart)
    public static function get_daily_totals($days = 30) {
        global $wpdb;
        $table = $wpdb->prefix . 'visitor_logs';
        $sql = $wpdb->prepare("
            SELECT DATE(visited_at) AS day, COUNT(DISTINCT ip_address) AS visitors, COUNT(*) AS pageviews
            FROM $table
            WHERE visited_at >= DATE_SUB(CURDATE(), INTERVAL %d DAY)
            GROUP BY DATE(visited_at)
            ORDER BY DATE(visited_at) ASC
        ", $days);
        return $wpdb->get_results($sql);
    }
}
