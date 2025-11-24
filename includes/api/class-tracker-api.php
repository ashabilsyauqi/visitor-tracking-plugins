<?php

class Tracker_API {

    public function register_routes() {

        register_rest_route('wpva/v1', '/track', [
            'methods'  => 'POST',
            'callback' => [$this, 'track'],
            'permission_callback' => '__return_true'
        ]);

        register_rest_route('wpva/v1', '/stats/top-pages', [
            'methods'  => 'GET',
            'callback' => [$this, 'top_pages'],
            'permission_callback' => function () {
                return current_user_can('manage_options'); // hanya admin
            }
        ]);
    }

    public function track($request) {
        return ['status' => 'tracked'];
    }

    public function top_pages($request) {
        global $wpdb;

        $table = $wpdb->prefix . 'visitor_logs';

        $rows = $wpdb->get_results("
            SELECT page, COUNT(*) AS views
            FROM $table
            GROUP BY page
            ORDER BY views DESC
            LIMIT 10
        ");

        return $rows;
    }
}
