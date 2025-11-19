<?php
/**
 * Credit Management System
 *
 * Manages API credits and tracks usage
 *
 * @package    Kontent_Fire
 * @subpackage Kontent_Fire/includes/licensing
 */

class Kontent_Fire_Credit_Manager {

    /**
     * License server URL
     */
    private $license_server = 'https://your-license-server.com/api/';

    /**
     * Credit costs for different operations
     */
    private $credit_costs = array(
        // Content generation (per 1000 tokens estimate)
        'claude_content' => 5,      // Claude for research/final content
        'openai_content' => 4,      // OpenAI for content
        'gemini_content' => 2,      // Gemini for content
        'local_ai' => 0.5,          // Local AI (Gemma/Llama) - minimal cost

        // Image generation
        'imagen_4' => 10,           // Imagen 4 image
        'dalle_3' => 8,             // DALL-E 3 image

        // Video generation
        'veo_3' => 50,              // Veo 3 video (expensive)

        // Social posting
        'social_post' => 1,         // Per social media post

        // SEO operations
        'keyword_research' => 3,    // LSI keyword research
        'seo_analysis' => 2,        // SEO content analysis

        // Auto-blogging
        'auto_blog' => 30,          // Full auto-blog generation (includes multiple operations)
    );

    /**
     * Get current credit balance
     *
     * @return array Balance info
     */
    public function get_balance() {
        // Check if in test mode
        if (get_option('kf_test_mode', false)) {
            return array(
                'success' => true,
                'credits' => 999999,
                'plan' => 'enterprise',
                'renewal_date' => date('Y-m-d', strtotime('+10 years')),
                'usage_this_month' => 0,
                'test_mode' => true
            );
        }

        $license_key = get_option('kontent_fire_license_key');

        if (empty($license_key)) {
            return array(
                'success' => false,
                'message' => 'No license key found',
                'credits' => 0
            );
        }

        // Cache balance for 5 minutes
        $cache_key = 'kf_credit_balance_' . md5($license_key);
        $cached = get_transient($cache_key);

        if ($cached !== false) {
            return $cached;
        }

        $response = wp_remote_post($this->license_server . 'credits/balance', array(
            'body' => array(
                'license_key' => $license_key,
                'site_url' => get_site_url()
            ),
            'timeout' => 15
        ));

        if (is_wp_error($response)) {
            return array(
                'success' => false,
                'message' => $response->get_error_message(),
                'credits' => 0
            );
        }

        $data = json_decode(wp_remote_retrieve_body($response), true);

        if (isset($data['success']) && $data['success']) {
            $result = array(
                'success' => true,
                'credits' => $data['credits'],
                'plan' => $data['plan'] ?? 'basic',
                'renewal_date' => $data['renewal_date'] ?? '',
                'usage_this_month' => $data['usage_this_month'] ?? 0
            );

            // Cache for 5 minutes
            set_transient($cache_key, $result, 300);

            return $result;
        }

        return array(
            'success' => false,
            'message' => $data['message'] ?? 'Unknown error',
            'credits' => 0
        );
    }

    /**
     * Check if enough credits available
     *
     * @param string $operation Operation type
     * @param int $quantity Quantity (default 1)
     * @return bool
     */
    public function has_credits($operation, $quantity = 1) {
        $cost = $this->get_cost($operation, $quantity);
        $balance = $this->get_balance();

        if (!$balance['success']) {
            return false;
        }

        return $balance['credits'] >= $cost;
    }

    /**
     * Get cost for operation
     *
     * @param string $operation Operation type
     * @param int $quantity Quantity
     * @return int Cost in credits
     */
    public function get_cost($operation, $quantity = 1) {
        if (isset($this->credit_costs[$operation])) {
            return $this->credit_costs[$operation] * $quantity;
        }

        return 0;
    }

    /**
     * Deduct credits for operation
     *
     * @param string $operation Operation type
     * @param int $quantity Quantity
     * @param array $metadata Additional metadata
     * @return array Result
     */
    public function deduct_credits($operation, $quantity = 1, $metadata = array()) {
        // Test mode - always succeed without deducting
        if (get_option('kf_test_mode', false)) {
            $this->log_usage($operation, $quantity, 0, array_merge($metadata, array('test_mode' => true)));
            return array(
                'success' => true,
                'credits_deducted' => 0,
                'remaining_credits' => 999999,
                'test_mode' => true
            );
        }

        $license_key = get_option('kontent_fire_license_key');

        if (empty($license_key)) {
            return array(
                'success' => false,
                'message' => 'No license key found'
            );
        }

        $cost = $this->get_cost($operation, $quantity);

        if ($cost == 0) {
            // Free operation
            return array(
                'success' => true,
                'credits_deducted' => 0,
                'remaining_credits' => $this->get_balance()['credits']
            );
        }

        $response = wp_remote_post($this->license_server . 'credits/deduct', array(
            'body' => array(
                'license_key' => $license_key,
                'site_url' => get_site_url(),
                'operation' => $operation,
                'quantity' => $quantity,
                'cost' => $cost,
                'metadata' => json_encode($metadata)
            ),
            'timeout' => 15
        ));

        if (is_wp_error($response)) {
            return array(
                'success' => false,
                'message' => $response->get_error_message()
            );
        }

        $data = json_decode(wp_remote_retrieve_body($response), true);

        if (isset($data['success']) && $data['success']) {
            // Clear balance cache
            $cache_key = 'kf_credit_balance_' . md5($license_key);
            delete_transient($cache_key);

            // Log usage locally
            $this->log_usage($operation, $quantity, $cost, $metadata);

            return array(
                'success' => true,
                'credits_deducted' => $cost,
                'remaining_credits' => $data['remaining_credits']
            );
        }

        return array(
            'success' => false,
            'message' => $data['message'] ?? 'Failed to deduct credits'
        );
    }

    /**
     * Log usage locally
     *
     * @param string $operation Operation type
     * @param int $quantity Quantity
     * @param int $cost Cost
     * @param array $metadata Metadata
     */
    private function log_usage($operation, $quantity, $cost, $metadata) {
        global $wpdb;
        $table = $wpdb->prefix . 'kf_credit_usage';

        // Create table if doesn't exist
        $charset_collate = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE IF NOT EXISTS $table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            operation varchar(50) NOT NULL,
            quantity int(11) DEFAULT 1,
            cost int(11) NOT NULL,
            metadata longtext,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY operation (operation),
            KEY created_at (created_at)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);

        // Insert usage record
        $wpdb->insert($table, array(
            'user_id' => get_current_user_id(),
            'operation' => $operation,
            'quantity' => $quantity,
            'cost' => $cost,
            'metadata' => json_encode($metadata),
            'created_at' => current_time('mysql')
        ));
    }

    /**
     * Get usage statistics
     *
     * @param int $days Number of days to look back
     * @return array Usage stats
     */
    public function get_usage_stats($days = 30) {
        global $wpdb;
        $table = $wpdb->prefix . 'kf_credit_usage';

        $date_from = date('Y-m-d H:i:s', strtotime("-$days days"));

        $total_usage = $wpdb->get_var($wpdb->prepare(
            "SELECT SUM(cost) FROM $table WHERE user_id = %d AND created_at >= %s",
            get_current_user_id(),
            $date_from
        ));

        $usage_by_operation = $wpdb->get_results($wpdb->prepare(
            "SELECT operation, SUM(cost) as total_cost, COUNT(*) as count
             FROM $table
             WHERE user_id = %d AND created_at >= %s
             GROUP BY operation
             ORDER BY total_cost DESC",
            get_current_user_id(),
            $date_from
        ), ARRAY_A);

        return array(
            'total_usage' => intval($total_usage),
            'by_operation' => $usage_by_operation,
            'period_days' => $days
        );
    }

    /**
     * Get credit package info
     *
     * @return array Available packages
     */
    public function get_packages() {
        return array(
            array(
                'name' => 'Starter',
                'price' => 29,
                'credits' => 1000,
                'features' => array(
                    'Auto-blogging (30 credits per blog)',
                    'Social media posting (1 credit per post)',
                    'Image generation (10 credits per image)',
                    'SEO optimization included',
                    'Email support'
                )
            ),
            array(
                'name' => 'Professional',
                'price' => 99,
                'credits' => 4000,
                'features' => array(
                    'Everything in Starter',
                    'Priority content generation',
                    'Video generation (50 credits per video)',
                    'Advanced analytics',
                    'Priority support'
                )
            ),
            array(
                'name' => 'Enterprise',
                'price' => 299,
                'credits' => 15000,
                'features' => array(
                    'Everything in Professional',
                    'Unlimited social accounts',
                    'White-label options',
                    'Custom integrations',
                    'Dedicated support'
                )
            )
        );
    }

    /**
     * Format credits for display
     *
     * @param int $credits Credit amount
     * @return string Formatted string
     */
    public function format_credits($credits) {
        if ($credits >= 1000) {
            return number_format($credits / 1000, 1) . 'K';
        }
        return number_format($credits);
    }

    /**
     * Estimate operation cost
     *
     * @param string $operation Operation type
     * @param array $params Operation parameters
     * @return array Cost estimate
     */
    public function estimate_cost($operation, $params = array()) {
        $cost = $this->get_cost($operation, $params['quantity'] ?? 1);

        return array(
            'operation' => $operation,
            'cost' => $cost,
            'formatted' => $this->format_credits($cost) . ' credits',
            'description' => $this->get_operation_description($operation)
        );
    }

    /**
     * Get operation description
     *
     * @param string $operation Operation type
     * @return string Description
     */
    private function get_operation_description($operation) {
        $descriptions = array(
            'auto_blog' => 'Full auto-blog with SEO, images, and social posts',
            'imagen_4' => 'High-quality AI image generation',
            'dalle_3' => 'AI image generation',
            'veo_3' => 'AI video generation',
            'claude_content' => 'Premium AI content generation',
            'openai_content' => 'AI content generation',
            'social_post' => 'Social media post',
            'keyword_research' => 'SEO keyword research',
            'seo_analysis' => 'Content SEO analysis'
        );

        return $descriptions[$operation] ?? ucwords(str_replace('_', ' ', $operation));
    }

    /**
     * Check if low credits (less than 100)
     *
     * @return bool
     */
    public function is_low_credits() {
        $balance = $this->get_balance();
        return $balance['success'] && $balance['credits'] < 100;
    }

    /**
     * Get upgrade URL
     *
     * @return string Upgrade URL
     */
    public function get_upgrade_url() {
        $license_key = get_option('kontent_fire_license_key');
        return 'https://your-license-server.com/upgrade?license=' . urlencode($license_key);
    }
}
