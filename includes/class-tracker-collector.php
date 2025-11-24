<?php
class Tracker_Collector {

    public function __construct() {
        // pastikan visitor_id cookie ada sebelum log_request dipanggil
        add_action('init', [$this, 'ensure_visitor_cookie']);
    }

    // Buat cookie visitor_id jika belum ada
    public function ensure_visitor_cookie() {
        if (!isset($_COOKIE['visitor_id'])) {
            $visitor_id = bin2hex(random_bytes(8)); // 16 char unique id
            setcookie('visitor_id', $visitor_id, time() + 86400, "/"); // 1 hari
            $_COOKIE['visitor_id'] = $visitor_id; // langsung bisa dipakai
        }
    }

    // server-side: log current request
    public function log_request() {
        global $wpdb;
        $table = $wpdb->prefix . 'visitor_logs';

        // IP detection
        $ip = '';
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
        } elseif (!empty($_SERVER['REMOTE_ADDR'])) {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        if (!$ip) $ip = '127.0.0.1';

        // halaman & browser
        $page_url   = $_SERVER['REQUEST_URI'] ?? '';
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $referrer   = $_SERVER['HTTP_REFERER'] ?? '';
        $client_id  = $_COOKIE['visitor_id'] ?? null;

        if (!$client_id) return; // safety check

        // Cek apakah IP + page + client_id sudah ada hari ini
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table 
             WHERE ip_address = %s 
               AND page_url = %s 
               AND client_id = %s
               AND DATE(visited_at) = CURDATE()",
            $ip,
            $page_url,
            $client_id
        ));

        if (!$exists) {
            // insert baru karena hari ini belum ada record
            $res = $wpdb->insert($table, [
                'ip_address' => $ip,
                'user_agent' => $user_agent,
                'page_url'   => $page_url,
                'client_id'  => $client_id,
                'referrer'   => $referrer,
                'visited_at' => current_time('mysql', 1)
            ], ['%s','%s','%s','%s','%s','%s']);

            if ($res === false) {
                error_log("TRACK FAILED: " . $wpdb->last_error . " | URL: $page_url | IP: $ip | client_id: $client_id");
            } else {
                error_log("TRACK OK: $page_url | IP: $ip | client_id: $client_id");
            }
        } else {
            error_log("TRACK SKIP: $page_url | IP: $ip | client_id: $client_id (already recorded today)");
        }
    }

    // helper: get daily totals
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

    public static function get_top_pages($limit = 5) {
        global $wpdb;
        $table = $wpdb->prefix . 'visitor_logs';
    
        $sql = $wpdb->prepare("
            SELECT page_url, COUNT(*) AS total
            FROM $table
            GROUP BY page_url
            ORDER BY total DESC
            LIMIT %d
        ", $limit);
    
        return $wpdb->get_results($sql);
    }


}
