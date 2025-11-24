<?php
class Tracker_REST {

    public function register_routes() {
        // tracking endpoint (public)
        register_rest_route('wpva/v1', '/track', [
            'methods'  => 'POST',
            'callback' => [$this, 'track'],
            'permission_callback' => '__return_true'
        ]);

        // register_rest_route('wpva/v1', '/stats/top-pages', [
        //     'methods'  => 'GET',
        //     'callback' => [$this, 'top_pages'],
        //     'permission_callback' => function () {
        //         return current_user_can('manage_options');
        //     }
        // ]);
        

        // totals: returns totals for chosen range (daily/weekly/monthly)
        register_rest_route('wpva/v1', '/stats', [
            'methods'  => 'GET',
            'callback' => [$this, 'totals'],
            'permission_callback' => function () {
                return current_user_can('manage_options');
            },
            'args' => [
                'range' => [
                    'required' => false,
                    'default'  => 'daily',
                    'sanitize_callback' => 'sanitize_text_field'
                ]
            ]
        ]);

        // series: time-series data for charts
        register_rest_route('wpva/v1', '/stats/series', [
            'methods'  => 'GET',
            'callback' => [$this, 'series'],
            'permission_callback' => function () {
                return current_user_can('manage_options');
            },
            'args' => [
                'range' => [
                    'required' => false,
                    'default'  => 'daily',
                    'sanitize_callback' => 'sanitize_text_field'
                ],
                'limit' => [
                    'required' => false,
                    'default'  => 30,
                    'sanitize_callback' => 'absint'
                ]
            ]
        ]);
    }

    // TRACK: insert raw event to visitor_logs
    public function track($request) {
        global $wpdb;
        $table = $wpdb->prefix . 'visitor_logs';
    
        $body = json_decode($request->get_body(), true);
        $client_id = isset($body['client_id']) ? sanitize_text_field($body['client_id']) : null;
        $page = isset($body['page']) ? esc_url_raw($body['page']) : '';
        $referrer = isset($body['referrer']) ? esc_url_raw($body['referrer']) : '';
    
        // Best-effort IP detection
        $ip = '';
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
        } elseif (!empty($_SERVER['REMOTE_ADDR'])) {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
    
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    
        if (!$client_id || !$page) {
            return rest_ensure_response(['status'=>'error','message'=>'invalid client_id or page']);
        }
    
        // Cek duplicate: IP + page + client_id per hari
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table 
             WHERE ip_address = %s 
               AND page_url = %s 
               AND client_id = %s 
               AND DATE(visited_at) = CURDATE()",
            $ip,
            $page,
            $client_id
        ));
    
        if (!$exists) {
            $wpdb->insert($table, [
                'ip_address' => $ip,
                'user_agent' => $user_agent,
                'page_url'   => $page,
                'client_id'  => $client_id,
                'referrer'   => $referrer,
                'visited_at' => current_time('mysql', 1)
            ], ['%s','%s','%s','%s','%s','%s']);
    
            return rest_ensure_response(['status'=>'ok','message'=>'recorded']);
        } else {
            return rest_ensure_response(['status'=>'skipped','message'=>'already recorded today']);
        }
    }
    

    // TOTALS: total_visitors (unique ip) and pageviews for given single period
    public function totals($request) {
        global $wpdb;
        $table = $wpdb->prefix . 'visitor_logs';

        $range = $request->get_param('range') ?: 'daily';
        $range = in_array($range, ['daily','weekly','monthly']) ? $range : 'daily';

        switch ($range) {
            case 'weekly':
                // current ISO week (Monday-based)
                $sql_visitors = $wpdb->prepare(
                    "SELECT COUNT(DISTINCT ip_address) FROM $table WHERE YEARWEEK(visited_at, 1) = YEARWEEK(CURDATE(), 1)"
                );
                $sql_pageviews = $wpdb->prepare(
                    "SELECT COUNT(*) FROM $table WHERE YEARWEEK(visited_at, 1) = YEARWEEK(CURDATE(), 1)"
                );
                break;

            case 'monthly':
                $sql_visitors = $wpdb->prepare(
                    "SELECT COUNT(DISTINCT ip_address) FROM $table WHERE MONTH(visited_at) = MONTH(CURDATE()) AND YEAR(visited_at) = YEAR(CURDATE())"
                );
                $sql_pageviews = $wpdb->prepare(
                    "SELECT COUNT(*) FROM $table WHERE MONTH(visited_at) = MONTH(CURDATE()) AND YEAR(visited_at) = YEAR(CURDATE())"
                );
                break;

            case 'daily':
            default:
                $sql_visitors = $wpdb->prepare(
                    "SELECT COUNT(DISTINCT ip_address) FROM $table WHERE DATE(visited_at) = CURDATE()"
                );
                $sql_pageviews = $wpdb->prepare(
                    "SELECT COUNT(*) FROM $table WHERE DATE(visited_at) = CURDATE()"
                );
                break;
        }

        $total_visitors = (int) $wpdb->get_var($sql_visitors);
        $pageviews = (int) $wpdb->get_var($sql_pageviews);

        return rest_ensure_response([
            'range' => $range,
            'total_visitors' => $total_visitors,
            'pageviews' => $pageviews
        ]);
    }

    // SERIES: return timeseries for charts
    public function series($request) {
        global $wpdb;
        $table = $wpdb->prefix . 'visitor_logs';

        $range = $request->get_param('range') ?: 'daily';
        $limit = (int) $request->get_param('limit') ?: 30;
        $range = in_array($range, ['daily','weekly','monthly']) ? $range : 'daily';

        if ($range === 'daily') {
            // last N days
            $sql = $wpdb->prepare("
                SELECT DATE(visited_at) AS period,
                       COUNT(DISTINCT ip_address) AS visitors,
                       COUNT(*) AS pageviews
                FROM $table
                WHERE visited_at >= DATE_SUB(CURDATE(), INTERVAL %d DAY)
                GROUP BY DATE(visited_at)
                ORDER BY DATE(visited_at) ASC
            ", $limit);
            $rows = $wpdb->get_results($sql);
            // normalize: ensure continuity (fill missing dates)
            $data = $this->fill_daily_series($rows, $limit);
        } elseif ($range === 'weekly') {
            // last N weeks
            $sql = $wpdb->prepare("
                SELECT YEARWEEK(visited_at, 1) AS yw,
                       COUNT(DISTINCT ip_address) AS visitors,
                       COUNT(*) AS pageviews
                FROM $table
                WHERE visited_at >= DATE_SUB(CURDATE(), INTERVAL %d WEEK)
                GROUP BY YEARWEEK(visited_at,1)
                ORDER BY YEARWEEK(visited_at,1) ASC
            ", $limit);
            $rows = $wpdb->get_results($sql);
            $data = [];
            foreach ($rows as $r) {
                // convert YEARWEEK -> label YYYY-Www
                $yw = $r->yw;
                $year = intval(substr($yw, 0, 4));
                $week = intval(substr($yw, 4));
                // get start date of ISO week
                $label = $this->week_label_from_yearweek($year, $week);
                $data[] = [
                    'period' => $label,
                    'visitors' => intval($r->visitors),
                    'pageviews' => intval($r->pageviews)
                ];
            }
            // no strict continuity guarantee for weeks beyond DB content
        } else { // monthly
            $sql = $wpdb->prepare("
                SELECT DATE_FORMAT(visited_at, '%%Y-%%m') AS period,
                       COUNT(DISTINCT ip_address) AS visitors,
                       COUNT(*) AS pageviews
                FROM $table
                WHERE visited_at >= DATE_SUB(CURDATE(), INTERVAL %d MONTH)
                GROUP BY DATE_FORMAT(visited_at, '%%Y-%%m')
                ORDER BY DATE_FORMAT(visited_at, '%%Y-%%m') ASC
            ", $limit);
            $rows = $wpdb->get_results($sql);
            $data = [];
            foreach ($rows as $r) {
                $data[] = [
                    'period' => $r->period,
                    'visitors' => intval($r->visitors),
                    'pageviews' => intval($r->pageviews)
                ];
            }
        }

        return rest_ensure_response([
            'range' => $range,
            'limit' => $limit,
            'data' => $data
        ]);
    }

    // helper: fill last N days continuity
    private function fill_daily_series($rows, $limit) {
        $map = [];
        foreach ($rows as $r) {
            $map[$r->period] = [
                'period' => $r->period,
                'visitors' => intval($r->visitors),
                'pageviews' => intval($r->pageviews)
            ];
        }
        $data = [];
        for ($i = $limit - 1; $i >= 0; $i--) {
            $d = date('Y-m-d', strtotime("-{$i} days"));
            if (isset($map[$d])) $data[] = $map[$d];
            else $data[] = ['period' => $d, 'visitors' => 0, 'pageviews' => 0];
        }
        return $data;
    }

    // helper: produce label like "YYYY-Www (Mon start)" from year + week
    private function week_label_from_yearweek($year, $week) {
        // get Monday of that ISO week
        $dto = new DateTime();
        $dto->setISODate($year, $week);
        return $dto->format('Y-\WW'); // e.g. 2025-W03
    }
}
