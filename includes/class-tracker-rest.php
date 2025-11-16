<?php

class Tracker_REST {

    public function register_routes() {

        // existing
        register_rest_route('analytics/v1', '/track', [
            'methods'  => 'POST',
            'callback' => [$this, 'track'],
            'permission_callback' => '__return_true'
        ]);
    
        // NEW: daily stats
        register_rest_route('analytics/v1', '/stats/daily', [
            'methods'  => 'GET',
            'callback' => [$this, 'daily_stats'],
            'permission_callback' => function () {
                return current_user_can('manage_options');
            }
        ]);
    }
    
    public function daily_stats() {
        global $wpdb;
    
        $table = $wpdb->prefix . 'tracker_pageviews';
    
        $rows = $wpdb->get_results("
            SELECT DATE(timestamp) AS date, COUNT(*) AS views
            FROM $table
            GROUP BY DATE(timestamp)
            ORDER BY date ASC
        ");
    
        return $rows;
    }
    

    public function track($request) {
        global $wpdb;

        $tablePageviews = $wpdb->prefix . 'tracker_pageviews';
        $tableVisitors  = $wpdb->prefix . 'tracker_visitors';

        $client_id = sanitize_text_field($request['client_id']);
        $page      = esc_url_raw($request['page']);
        $referrer  = esc_url_raw($request['referrer']);

        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $ip         = $_SERVER['REMOTE_ADDR'] ?? '';

        // Insert pageview
        $wpdb->insert($tablePageviews, [
            'client_id' => $client_id,
            'page_url'  => $page,
            'referrer'  => $referrer
        ]);

        // Check if visitor exists
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $tableVisitors WHERE client_id = %s",
            $client_id
        ));

        if (!$exists) {
            $wpdb->insert($tableVisitors, [
                'client_id'  => $client_id,
                'ip_address' => $ip,
                'user_agent' => $user_agent
            ]);
        }

        return ['status' => 'ok'];
    }

}
