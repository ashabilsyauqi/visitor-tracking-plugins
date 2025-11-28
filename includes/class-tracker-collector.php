<?php
class Tracker_Collector {

    public function __construct() {
        // 1. Session harus mulai SEBELUM headers dikirim → pindah ke init
        add_action('init', [$this, 'start_session'], 1);

        // 2. Inject JS tracker
        add_action('wp_head', [$this, 'inject_tracker_script'], 1);

        // 3. Log request (baik dari page load maupun AJAX beacon)
        add_action('wp', [$this, 'log_request'], 99);
        add_action('wp_ajax_track_visit', [$this, 'log_request']);
        add_action('wp_ajax_nopriv_track_visit', [$this, 'log_request']);
    }

    // Pastikan session mulai di awal
    public function start_session() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public function inject_tracker_script() {
        if (is_admin() || wp_doing_ajax() || wp_is_json_request()) return;
        ?>
        <script>
        (() => {
            const KEY = 'tracker_vid';
            let vid = localStorage.getItem(KEY);

            // Coba ambil dari cookie juga
            if (!vid) {
                const match = document.cookie.match(/(?:^|; )tracker_vid=([^;]*)/);
                vid = match ? match[1] : null;
            }

            // Kalau belum ada, buat baru
            if (!vid) {
                vid = 'cid_' + Math.random().toString(36).slice(2, 11) + Date.now().toString(36).slice(-6);
                try {
                    localStorage.setItem(KEY, vid);
                } catch(e) {}
                // Set cookie juga sebagai backup
                document.cookie = `tracker_vid=${vid};path=/;max-age=31536000;Secure;SameSite=Lax`;
            }

            // Kirim via beacon (paling reliable)
            const url = '<?php echo admin_url("admin-ajax.php"); ?>';
            const data = new FormData();
            data.append('action', 'track_visit');
            data.append('vid', vid);
            data.append('url', location.pathname + location.search);
            data.append('ref', document.referrer || '');

            if (navigator.sendBeacon) {
                navigator.sendBeacon(url, data);
            } else {
                // Fallback fetch (keepalive = bisa kirim setelah page unload)
                fetch(url, {
                    method: 'POST',
                    body: data,
                    keepalive: true,
                    credentials: 'same-origin'
                });
            }
        })();
        </script>
        <?php
    }

    public function log_request() {
        if (is_admin()) return;

        global $wpdb;

        $client_id = null;
        $page_url  = '/';
        $referrer  = '';

        // 1. Dari JS beacon (priority tertinggi)
        if (isset($_POST['action']) && $_POST['action'] === 'track_visit') {
            $client_id = sanitize_text_field($_POST['vid'] ?? '');
            $page_url  = sanitize_text_field($_POST['url'] ?? '/');
            $referrer  = sanitize_text_field($_POST['ref'] ?? '');
        }

        // 2. Dari pixel fallback (GET)
        elseif (isset($_GET['track'])) {
            $client_id = sanitize_text_field($_GET['vid'] ?? '');
            $page_url  = sanitize_text_field($_GET['url'] ?? '/');
            $referrer  = sanitize_text_field($_GET['ref'] ?? '');
        }

        // 3. Dari cookie (normal page load)
        elseif (!empty($_COOKIE['tracker_vid'])) {
            $client_id = $_COOKIE['tracker_vid'];
            $page_url  = $_SERVER['REQUEST_URI'] ?? '/';
            $referrer  = $_SERVER['HTTP_REFERER'] ?? '';
        }

        // 4. Fallback terakhir: Session + Fingerprint LEBIH UNIK
        if (!$client_id || strlen($client_id) < 12) {
            $client_id = $this->get_fingerprint_id();
            $page_url  = $page_url === '/' ? ($_SERVER['REQUEST_URI'] ?? '/') : $page_url;
            $referrer  = $referrer ?: ($_SERVER['HTTP_REFERER'] ?? '');
        }

        // Validasi minimal
        if (!$client_id || strlen($client_id) < 12) return;

        $ip   = $this->get_ip_address();
        $ua   = substr($_SERVER['HTTP_USER_AGENT'] ?? 'unknown', 0, 500);
        $date = gmdate('Y-m-d');
        $now  = current_time('mysql', 1);

        $tables = [
            'logs'     => $wpdb->prefix . 'visitor_logs',
            'visitors' => $wpdb->prefix . 'tracker_daily_visitors',
            'pages'    => $wpdb->prefix . 'tracker_daily_pageviews',
        ];

        // 1. Raw log
        $wpdb->insert($tables['logs'], [
            'ip_address' => $ip,
            'user_agent' => $ua,
            'page_url'   => $page_url,
            'client_id'  => $client_id,
            'referrer'   => $referrer,
            'visited_at' => $now,
        ]);

        // 2. Unique visitor per day
        $wpdb->query($wpdb->prepare(
            "INSERT IGNORE INTO {$tables['visitors']} (client_id, date, ip, user_agent)
             VALUES (%s, %s, %s, %s)",
            $client_id, $date, $ip, $ua
        ));

        // 3. Unique pageview per visitor per day
        $wpdb->query($wpdb->prepare(
            "INSERT IGNORE INTO {$tables['pages']} (client_id, page, date)
             VALUES (%s, %s, %s)",
            $client_id, $page_url, $date
        ));
    }

    // Fingerprint LEBIH UNIK (pakai random salt per session)
    private function get_fingerprint_id() {
        if (!empty($_SESSION['tracker_vid'])) {
            return $_SESSION['tracker_vid'];
        }

        $base = ($_SERVER['HTTP_USER_AGENT'] ?? '') .
                ($this->get_ip_address()) .
                ($_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '') .
                ($_SERVER['HTTP_ACCEPT_ENCODING'] ?? '') .
                (isset($_COOKIE['timezone_offset']) ? $_COOKIE['timezone_offset'] : '');

        // Tambah random salt biar beda tiap session
        $salt = bin2hex(random_bytes(8));
        $hash = hash('sha256', $base . $salt . 'tracker_salt_2025');
        $vid  = 'fp_' . substr($hash, 0, 18);

        $_SESSION['tracker_vid'] = $vid;
        return $vid;
    }

    private function get_ip_address() {
        $keys = ['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'REMOTE_ADDR'];
        foreach ($keys as $key) {
            if (!empty($_SERVER[$key])) {
                $ip = trim(explode(',', $_SERVER[$key])[0]);
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
        return '0.0.0.0';
    }
}

// INSTANSiasi
new Tracker_Collector();