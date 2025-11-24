<?php
class Tracker_Collector {

    public function __construct() {
        add_action('init', [$this, 'ensure_visitor_cookie'], 1);
        add_action('wp', [$this, 'log_request'], 99);
    }

    // create unique visitor cookie
    public function ensure_visitor_cookie() {
        if (!isset($_COOKIE['visitor_id'])) {

            $visitor_id = bin2hex(random_bytes(8));

            // FIX: modern cookie syntax
            setcookie(
                'visitor_id',
                $visitor_id,
                [
                    'expires'  => time() + 86400 * 30,
                    'path'     => '/',
                    'secure'   => is_ssl(),
                    'httponly' => false,
                    'samesite' => 'Lax',
                ]
            );

            $_COOKIE['visitor_id'] = $visitor_id; // make available immediately
        }
    }

    // log the request
    public function log_request() {
        global $wpdb;
        $table = $wpdb->prefix . 'visitor_logs';

        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $ip = explode(',', $ip)[0];

        $client_id  = $_COOKIE['visitor_id'] ?? null;
        if (!$client_id) return;

        $page_url   = $_SERVER['REQUEST_URI'] ?? '';
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $referrer   = $_SERVER['HTTP_REFERER'] ?? '';

        // prevent duplicate per day per page
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table 
             WHERE client_id = %s
               AND page_url = %s
               AND DATE(visited_at) = CURDATE()",
            $client_id,
            $page_url
        ));

        if (!$exists) {
            $wpdb->insert($table, [
                'ip_address' => $ip,
                'user_agent' => $user_agent,
                'page_url'   => $page_url,
                'client_id'  => $client_id,
                'referrer'   => $referrer,
                'visited_at' => current_time('mysql', 1)
            ]);
        }
    }

    // analytics
    public static function get_daily_totals($days = 30) {
        global $wpdb;
        $table = $wpdb->prefix . 'visitor_logs';

        return $wpdb->get_results($wpdb->prepare("
            SELECT 
                DATE(visited_at) AS day,
                COUNT(DISTINCT client_id) AS visitors,
                COUNT(*) AS pageviews
            FROM $table
            WHERE visited_at >= DATE_SUB(CURDATE(), INTERVAL %d DAY)
            GROUP BY DATE(visited_at)
            ORDER BY DATE(visited_at)
        ", $days));
    }
}
