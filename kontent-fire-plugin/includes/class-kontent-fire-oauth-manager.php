<?php
/**
 * OAuth Manager for Social Media Platforms
 *
 * Handles OAuth flows for Facebook, Instagram, Twitter, LinkedIn, TikTok, YouTube
 *
 * @package    Kontent_Fire
 * @subpackage Kontent_Fire/includes
 */

class Kontent_Fire_OAuth_Manager {

    /**
     * OAuth redirect base URL (your licensing server)
     */
    private $oauth_base_url = 'https://your-license-server.com/oauth/';

    /**
     * Initialize OAuth connections
     */
    public function __construct() {
        // Register OAuth callback handler
        add_action('init', array($this, 'handle_oauth_callback'));

        // Add OAuth routes
        add_action('init', array($this, 'register_oauth_routes'));
    }

    /**
     * Register OAuth callback routes
     */
    public function register_oauth_routes() {
        add_rewrite_rule(
            '^kontent-fire/oauth/([^/]+)/?',
            'index.php?kf_oauth_platform=$matches[1]',
            'top'
        );

        add_rewrite_tag('%kf_oauth_platform%', '([^&]+)');
    }

    /**
     * Handle OAuth callback from platforms
     */
    public function handle_oauth_callback() {
        $platform = get_query_var('kf_oauth_platform');

        if (empty($platform)) {
            return;
        }

        // Check if we have OAuth code or token
        $code = isset($_GET['code']) ? sanitize_text_field($_GET['code']) : '';
        $state = isset($_GET['state']) ? sanitize_text_field($_GET['state']) : '';
        $error = isset($_GET['error']) ? sanitize_text_field($_GET['error']) : '';

        // Verify state token
        if (!$this->verify_state($state)) {
            wp_die('Invalid OAuth state. Please try connecting again.');
        }

        if ($error) {
            wp_die('OAuth Error: ' . esc_html($error));
        }

        if (empty($code)) {
            wp_die('No OAuth code received. Please try connecting again.');
        }

        // Exchange code for access token
        $result = $this->exchange_code_for_token($platform, $code);

        if ($result['success']) {
            // Store connection
            $this->store_platform_connection($platform, $result['data']);

            // Redirect to admin page with success message
            wp_redirect(admin_url('admin.php?page=kontent-fire-platforms&oauth_success=' . $platform));
            exit;
        } else {
            wp_die('Failed to connect: ' . esc_html($result['message']));
        }
    }

    /**
     * Initiate OAuth flow for a platform
     *
     * @param string $platform Platform name
     * @return string OAuth URL
     */
    public function get_oauth_url($platform) {
        $license_key = get_option('kontent_fire_license_key');

        if (empty($license_key)) {
            return admin_url('admin.php?page=kontent-fire-settings&error=no_license');
        }

        $site_url = get_site_url();
        $callback_url = $site_url . '/kontent-fire/oauth/' . $platform;
        $state = $this->generate_state();

        // Store state for verification
        set_transient('kf_oauth_state_' . $state, $platform, 600); // 10 minutes

        $params = array(
            'license_key' => $license_key,
            'platform' => $platform,
            'callback_url' => $callback_url,
            'state' => $state,
            'site_url' => $site_url
        );

        // Call your licensing server to get OAuth URL
        $response = wp_remote_post($this->oauth_base_url . 'initiate', array(
            'body' => $params,
            'timeout' => 30
        ));

        if (is_wp_error($response)) {
            return '';
        }

        $data = json_decode(wp_remote_retrieve_body($response), true);

        if (isset($data['oauth_url'])) {
            return $data['oauth_url'];
        }

        return '';
    }

    /**
     * Exchange OAuth code for access token
     *
     * @param string $platform Platform name
     * @param string $code OAuth code
     * @return array Result with success status and data
     */
    private function exchange_code_for_token($platform, $code) {
        $license_key = get_option('kontent_fire_license_key');

        $response = wp_remote_post($this->oauth_base_url . 'exchange', array(
            'body' => array(
                'license_key' => $license_key,
                'platform' => $platform,
                'code' => $code,
                'site_url' => get_site_url()
            ),
            'timeout' => 30
        ));

        if (is_wp_error($response)) {
            return array(
                'success' => false,
                'message' => $response->get_error_message()
            );
        }

        $data = json_decode(wp_remote_retrieve_body($response), true);

        if (isset($data['success']) && $data['success']) {
            return array(
                'success' => true,
                'data' => $data['connection_data']
            );
        }

        return array(
            'success' => false,
            'message' => $data['message'] ?? 'Unknown error'
        );
    }

    /**
     * Store platform connection
     *
     * @param string $platform Platform name
     * @param array $data Connection data
     */
    private function store_platform_connection($platform, $data) {
        global $wpdb;
        $table = $wpdb->prefix . 'kf_platform_connections';

        // Check if connection exists
        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT id FROM $table WHERE user_id = %d AND platform = %s",
            get_current_user_id(),
            $platform
        ));

        $connection_data = array(
            'user_id' => get_current_user_id(),
            'platform' => $platform,
            'account_name' => $data['account_name'] ?? '',
            'platform_user_id' => $data['platform_user_id'] ?? '',
            'settings' => json_encode($data),
            'status' => 'active',
            'updated_at' => current_time('mysql')
        );

        if ($existing) {
            // Update existing
            $wpdb->update(
                $table,
                $connection_data,
                array('id' => $existing->id)
            );
        } else {
            // Insert new
            $connection_data['created_at'] = current_time('mysql');
            $wpdb->insert($table, $connection_data);
        }
    }

    /**
     * Generate state token for OAuth
     *
     * @return string State token
     */
    private function generate_state() {
        return wp_generate_password(32, false);
    }

    /**
     * Verify OAuth state token
     *
     * @param string $state State token
     * @return bool
     */
    private function verify_state($state) {
        if (empty($state)) {
            return false;
        }

        $stored_platform = get_transient('kf_oauth_state_' . $state);

        if ($stored_platform) {
            delete_transient('kf_oauth_state_' . $state);
            return true;
        }

        return false;
    }

    /**
     * Disconnect platform
     *
     * @param string $platform Platform name
     * @return bool Success
     */
    public function disconnect_platform($platform) {
        global $wpdb;
        $table = $wpdb->prefix . 'kf_platform_connections';

        $result = $wpdb->delete(
            $table,
            array(
                'user_id' => get_current_user_id(),
                'platform' => $platform
            )
        );

        // Notify licensing server
        $license_key = get_option('kontent_fire_license_key');

        wp_remote_post($this->oauth_base_url . 'disconnect', array(
            'body' => array(
                'license_key' => $license_key,
                'platform' => $platform,
                'site_url' => get_site_url()
            ),
            'timeout' => 10
        ));

        return $result !== false;
    }

    /**
     * Get connected platforms
     *
     * @return array Connected platforms
     */
    public function get_connected_platforms() {
        global $wpdb;
        $table = $wpdb->prefix . 'kf_platform_connections';

        $platforms = $wpdb->get_results($wpdb->prepare(
            "SELECT platform, account_name, status, created_at
             FROM $table
             WHERE user_id = %d AND status = 'active'
             ORDER BY created_at DESC",
            get_current_user_id()
        ));

        return $platforms;
    }

    /**
     * Check if platform is connected
     *
     * @param string $platform Platform name
     * @return bool
     */
    public function is_connected($platform) {
        global $wpdb;
        $table = $wpdb->prefix . 'kf_platform_connections';

        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table
             WHERE user_id = %d AND platform = %s AND status = 'active'",
            get_current_user_id(),
            $platform
        ));

        return $count > 0;
    }

    /**
     * Refresh expired tokens (called by cron)
     */
    public function refresh_expired_tokens() {
        global $wpdb;
        $table = $wpdb->prefix . 'kf_platform_connections';

        // Get connections that need refresh
        $connections = $wpdb->get_results(
            "SELECT * FROM $table WHERE status = 'active'"
        );

        foreach ($connections as $connection) {
            $settings = json_decode($connection->settings, true);

            // Check if token needs refresh (implement platform-specific logic)
            if ($this->needs_token_refresh($connection->platform, $settings)) {
                $this->refresh_platform_token($connection);
            }
        }
    }

    /**
     * Check if token needs refresh
     *
     * @param string $platform Platform name
     * @param array $settings Connection settings
     * @return bool
     */
    private function needs_token_refresh($platform, $settings) {
        if (!isset($settings['expires_at'])) {
            return false;
        }

        $expires_at = strtotime($settings['expires_at']);
        $now = time();

        // Refresh if expiring within 7 days
        return ($expires_at - $now) < (7 * DAY_IN_SECONDS);
    }

    /**
     * Refresh platform token
     *
     * @param object $connection Connection object
     */
    private function refresh_platform_token($connection) {
        $license_key = get_option('kontent_fire_license_key');

        $response = wp_remote_post($this->oauth_base_url . 'refresh', array(
            'body' => array(
                'license_key' => $license_key,
                'platform' => $connection->platform,
                'connection_id' => $connection->id,
                'site_url' => get_site_url()
            ),
            'timeout' => 30
        ));

        if (!is_wp_error($response)) {
            $data = json_decode(wp_remote_retrieve_body($response), true);

            if (isset($data['success']) && $data['success']) {
                $this->store_platform_connection($connection->platform, $data['connection_data']);
            }
        }
    }
}
