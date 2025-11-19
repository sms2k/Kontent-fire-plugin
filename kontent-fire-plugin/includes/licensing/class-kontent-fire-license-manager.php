<?php
/**
 * License Manager
 *
 * Handles license key validation and management.
 *
 * @package    Kontent_Fire
 * @subpackage Kontent_Fire/includes/licensing
 */

class Kontent_Fire_License_Manager {

    /**
     * License server URL (replace with your actual server)
     */
    private $license_server = 'https://licensing.kynex.io/api/v1';

    /**
     * Product ID
     */
    private $product_id = 'kontent-fire';

    /**
     * Test/Demo license keys (for development and testing only)
     */
    private $test_keys = array(
        'TEST-FULL-ACCESS-2024' => 'enterprise',
        'TEST-PRO-LICENSE-2024' => 'pro',
        'TEST-BASIC-LICENSE-2024' => 'basic',
        'DEMO-LICENSE-KEY' => 'pro'
    );

    /**
     * Validate license key
     *
     * @param string $license_key
     * @return array Status and data
     */
    public function validate_license($license_key) {
        if (empty($license_key)) {
            return array(
                'valid' => false,
                'message' => 'License key is required.'
            );
        }

        // Check if it's a test license key
        if ($this->is_test_key($license_key)) {
            return $this->activate_test_license($license_key);
        }

        // Check local database first
        $local_check = $this->check_local_license($license_key);
        if ($local_check && $local_check['status'] === 'active') {
            // Check if it's time to revalidate with server (every 7 days)
            $last_check = get_option('kontent_fire_last_license_check', 0);
            if (time() - $last_check < (7 * DAY_IN_SECONDS)) {
                return array(
                    'valid' => true,
                    'data' => $local_check
                );
            }
        }

        // Validate with remote server
        $remote_validation = $this->validate_with_server($license_key);

        if ($remote_validation['valid']) {
            $this->store_license_locally($license_key, $remote_validation['data']);
            update_option('kontent_fire_last_license_check', time());
            update_option('kontent_fire_license_status', 'active');
        } else {
            update_option('kontent_fire_license_status', 'invalid');
        }

        return $remote_validation;
    }

    /**
     * Validate license with remote server
     *
     * @param string $license_key
     * @return array
     */
    private function validate_with_server($license_key) {
        $response = wp_remote_post($this->license_server . '/validate', array(
            'body' => array(
                'license_key' => $license_key,
                'product_id' => $this->product_id,
                'site_url' => get_site_url(),
                'version' => KONTENT_FIRE_VERSION
            ),
            'timeout' => 15
        ));

        if (is_wp_error($response)) {
            return array(
                'valid' => false,
                'message' => 'Unable to connect to license server. Please try again later.',
                'error' => $response->get_error_message()
            );
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (!$data) {
            return array(
                'valid' => false,
                'message' => 'Invalid response from license server.'
            );
        }

        return $data;
    }

    /**
     * Check license in local database
     *
     * @param string $license_key
     * @return array|null
     */
    private function check_local_license($license_key) {
        global $wpdb;
        $table = $wpdb->prefix . 'kf_licenses';

        $license = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE license_key = %s",
            $license_key
        ), ARRAY_A);

        if (!$license) {
            return null;
        }

        // Check if expired
        if ($license['expires_at'] && strtotime($license['expires_at']) < time()) {
            $license['status'] = 'expired';
        }

        return $license;
    }

    /**
     * Store license data locally
     *
     * @param string $license_key
     * @param array $data
     */
    private function store_license_locally($license_key, $data) {
        global $wpdb;
        $table = $wpdb->prefix . 'kf_licenses';

        $existing = $this->check_local_license($license_key);

        $license_data = array(
            'license_key' => $license_key,
            'user_id' => get_current_user_id(),
            'plan_type' => $data['plan_type'] ?? 'pro',
            'status' => $data['status'] ?? 'active',
            'max_activations' => $data['max_activations'] ?? 1,
            'expires_at' => $data['expires_at'] ?? null,
        );

        if ($existing) {
            $wpdb->update($table, $license_data, array('license_key' => $license_key));
        } else {
            $wpdb->insert($table, $license_data);
        }
    }

    /**
     * Activate license for current site
     *
     * @param string $license_key
     * @return array
     */
    public function activate_license($license_key) {
        $response = wp_remote_post($this->license_server . '/activate', array(
            'body' => array(
                'license_key' => $license_key,
                'product_id' => $this->product_id,
                'site_url' => get_site_url(),
                'site_name' => get_bloginfo('name')
            ),
            'timeout' => 15
        ));

        if (is_wp_error($response)) {
            return array(
                'success' => false,
                'message' => 'Unable to activate license. Please try again later.'
            );
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if ($data['success']) {
            update_option('kontent_fire_license_key', $license_key);
            update_option('kontent_fire_license_status', 'active');
            $this->store_license_locally($license_key, $data['data']);
        }

        return $data;
    }

    /**
     * Deactivate license for current site
     *
     * @param string $license_key
     * @return array
     */
    public function deactivate_license($license_key = null) {
        if (!$license_key) {
            $license_key = get_option('kontent_fire_license_key');
        }

        $response = wp_remote_post($this->license_server . '/deactivate', array(
            'body' => array(
                'license_key' => $license_key,
                'product_id' => $this->product_id,
                'site_url' => get_site_url()
            ),
            'timeout' => 15
        ));

        delete_option('kontent_fire_license_key');
        delete_option('kontent_fire_license_status');
        delete_option('kontent_fire_last_license_check');

        return array('success' => true);
    }

    /**
     * Check if license is active
     *
     * @return bool
     */
    public function is_license_active() {
        $status = get_option('kontent_fire_license_status', 'inactive');
        return $status === 'active';
    }

    /**
     * Get license data
     *
     * @return array|null
     */
    public function get_license_data() {
        $license_key = get_option('kontent_fire_license_key');
        if (!$license_key) {
            return null;
        }

        return $this->check_local_license($license_key);
    }

    /**
     * Check feature availability based on plan
     *
     * @param string $feature
     * @return bool
     */
    public function has_feature($feature) {
        if (!$this->is_license_active()) {
            return false;
        }

        $license = $this->get_license_data();
        if (!$license) {
            return false;
        }

        $plan_features = array(
            'basic' => array(
                'content_generation',
                'single_platform',
                'basic_seo'
            ),
            'pro' => array(
                'content_generation',
                'multi_platform',
                'advanced_seo',
                'image_generation',
                'video_generation',
                'analytics',
                'scheduling'
            ),
            'enterprise' => array(
                'content_generation',
                'multi_platform',
                'advanced_seo',
                'image_generation',
                'video_generation',
                'analytics',
                'scheduling',
                'team_collaboration',
                'white_label',
                'api_access',
                'priority_support'
            )
        );

        $plan_type = $license['plan_type'] ?? 'basic';
        $features = $plan_features[$plan_type] ?? array();

        return in_array($feature, $features);
    }

    /**
     * Generate a new license key (admin only)
     *
     * @param string $plan_type
     * @param int $max_activations
     * @param string $expires_at
     * @return string
     */
    public function generate_license_key($plan_type = 'pro', $max_activations = 1, $expires_at = null) {
        // Generate a secure license key
        $prefix = 'KF';
        $random = strtoupper(bin2hex(random_bytes(12)));
        $license_key = $prefix . '-' . substr($random, 0, 8) . '-' . substr($random, 8, 8) . '-' . substr($random, 16, 8);

        global $wpdb;
        $table = $wpdb->prefix . 'kf_licenses';

        $wpdb->insert($table, array(
            'license_key' => $license_key,
            'plan_type' => $plan_type,
            'status' => 'active',
            'max_activations' => $max_activations,
            'expires_at' => $expires_at
        ));

        return $license_key;
    }

    /**
     * Check if license key is a test key
     *
     * @param string $license_key
     * @return bool
     */
    private function is_test_key($license_key) {
        return array_key_exists($license_key, $this->test_keys);
    }

    /**
     * Activate test license (no server validation)
     *
     * @param string $license_key
     * @return array
     */
    private function activate_test_license($license_key) {
        $plan_type = $this->test_keys[$license_key];

        // Store test license data
        $test_data = array(
            'plan_type' => $plan_type,
            'status' => 'active',
            'max_activations' => 999,
            'expires_at' => date('Y-m-d H:i:s', strtotime('+10 years')) // Effectively unlimited
        );

        $this->store_license_locally($license_key, $test_data);
        update_option('kontent_fire_license_key', $license_key);
        update_option('kontent_fire_license_status', 'active');
        update_option('kontent_fire_last_license_check', time());
        update_option('kf_test_mode', true);

        return array(
            'valid' => true,
            'message' => 'Test license activated successfully! (Development Mode)',
            'data' => $test_data
        );
    }
}
